<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusUpdated;
use App\Models\BookingActivityLog;
use App\Models\SuntarapornBooking;
use App\Models\SuntarapornZone;
use App\Models\SuntarapornSeat;
use App\Models\User;
use App\Services\SuntarapornSeatMap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class SuntarapornBandController extends Controller
{
    private const ALLOWED_ROLES    = [User::ROLE_SUNTARAPORN, User::ROLE_MANAGER];
    private const SESSION_KEY      = 'suntaraporn_user_id';
    private const SELECTING_CACHE  = 'suntaraporn_selecting_keys';
    private const SELECTING_TTL    = 90; // seconds

    // ── Show dates ───────────────────────────────────────────────
    // รอบการแสดง 2 วัน — เก้าอี้ผังเดียวกันแต่จองแยกกันได้คนละวัน
    public const SHOW_DATES = [
        '2026-10-31' => '31 ต.ค. 2569',
        '2026-11-01' => '1 พ.ย. 2569',
    ];

    /** วันแสดงที่กำลังดูอยู่ — อ่านจาก ?date=YYYY-MM-DD, ถ้าไม่ถูกต้องใช้วันแรก */
    private function resolveShowDate(Request $request): string
    {
        $date = (string) $request->input('date', '');
        return isset(self::SHOW_DATES[$date]) ? $date : array_key_first(self::SHOW_DATES);
    }

    /** Cache key ของ selecting keys แยกตามวันแสดง */
    private function selectingCacheKey(string $showDate): string
    {
        return self::SELECTING_CACHE . '_' . $showDate;
    }

    /**
     * เพิ่ม keys เข้า selecting cache แบบ atomic (กัน lost-update เมื่อหลาย admin เลือกพร้อมกัน)
     * ถ้าล็อกไม่ได้ (driver ไม่รองรับ/timeout) จะ fallback เขียนตรงๆ — ยอมเสี่ยง race เล็กน้อยดีกว่า request พัง
     */
    private function mergeSelectingCache(string $cacheKey, array $addKeys): void
    {
        $merge = function () use ($cacheKey, $addKeys) {
            $existing = Cache::get($cacheKey, []);
            Cache::put($cacheKey, array_values(array_unique(array_merge($existing, $addKeys))), self::SELECTING_TTL);
        };

        try {
            Cache::lock($cacheKey . '_lock', 5)->block(3, $merge);
        } catch (\Throwable) {
            $merge();
        }
    }

    // ── Auth helpers ─────────────────────────────────────────────

    private function currentUser(): ?User
    {
        $id = session(self::SESSION_KEY);
        return $id ? User::where('is_active', true)->find($id) : null;
    }

    private function guardRedirect(): ?RedirectResponse
    {
        $user = $this->currentUser();
        if (!$user || !in_array($user->role, self::ALLOWED_ROLES, true)) {
            return redirect()->route('suntaraporn.login');
        }
        return null;
    }

    // ── Login ────────────────────────────────────────────────────

    public function showLogin(): View|RedirectResponse
    {
        $user = $this->currentUser();
        if ($user && in_array($user->role, self::ALLOWED_ROLES, true)) {
            return redirect()->route('suntaraporn.index');
        }
        return view('suntaraporn-login');
    }

    public function doLogin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $data['username'])
            ->where('is_active', true)
            ->whereIn('role', self::ALLOWED_ROLES)
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['username' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'])->withInput();
        }

        $request->session()->regenerate();
        session([self::SESSION_KEY => $user->id]);

        return redirect()->route('suntaraporn.index');
    }

    public function doLogout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();
        return redirect()->route('suntaraporn.login');
    }

    // ── Public View (no auth required) ───────────────────────────

    public function publicView(Request $request): View
    {
        $showDate  = $this->resolveShowDate($request);
        $showDates = self::SHOW_DATES;

        $bookedSeats = SuntarapornSeat::where('show_date', $showDate)
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $zones = SuntarapornZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        $totalSeats     = SuntarapornSeatMap::totalSeats();
        $selectingSeats = Cache::get($this->selectingCacheKey($showDate), []);

        return view('suntaraporn-public', compact('bookedSeats', 'prices', 'totalSeats', 'selectingSeats', 'zones', 'rowZones', 'showDate', 'showDates'));
    }

    // ── Main Page ─────────────────────────────────────────────────

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $showDate  = $this->resolveShowDate($request);
        $showDates = self::SHOW_DATES;

        $bookedSeats = SuntarapornSeat::where('show_date', $showDate)
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        // ที่นั่ง Sponsor (booking ฿0) — แอดมินเห็นแยกเป็นสีทอง + โน้ต
        // (ยังคงอยู่ใน $bookedSeats ด้วย เพื่อกันการจองทับ)
        $sponsorSeats = SuntarapornSeat::where('show_date', $showDate)
            ->where('is_booked', true)
            ->whereHas('booking', fn ($q) => $q->where('is_sponsor', true))
            ->with('booking:id,first_name')
            ->get()
            ->mapWithKeys(fn (SuntarapornSeat $s) => [$s->seat_key => $s->booking?->first_name ?? 'Sponsor'])
            ->all();

        // ที่นั่ง "ยังไม่จ่ายตัง" — แอดมินเห็นเป็นสีเทาอ่อน, ลูกค้าเห็นเป็น "ขายแล้ว" ปกติ
        $unpaidSeats = SuntarapornSeat::where('show_date', $showDate)
            ->where('is_booked', true)
            ->whereHas('booking', fn ($q) => $q->where('is_unpaid', true))
            ->pluck('seat_key')
            ->all();

        $zones = SuntarapornZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        $totalSeats     = SuntarapornSeatMap::totalSeats();
        $selectingSeats = Cache::get($this->selectingCacheKey($showDate), []);

        return view('suntaraporn-band', compact('bookedSeats', 'sponsorSeats', 'unpaidSeats', 'prices', 'user', 'totalSeats', 'selectingSeats', 'zones', 'rowZones', 'showDate', 'showDates'));
    }

    // ── Book Seat(s) ──────────────────────────────────────────────

    public function bookSeat(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30|distinct',
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'phone'       => 'required|string|max:20',
            'slip'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'is_unpaid'   => 'nullable|boolean',
        ]);

        $showDate = $this->resolveShowDate($request);
        $isUnpaid = $request->boolean('is_unpaid');

        // booker = admin ที่ login อยู่
        $data['booker_name'] = $this->currentUser()->name;
        $seatKeys = array_values($data['seat_keys']);
        $seatZones = SuntarapornSeatMap::zonesFor($seatKeys);

        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        // Calculate total price from the trusted server-side seat map.
        $prices    = SuntarapornZone::pluck('price', 'slug')->all();
        $totalPrice = 0;
        foreach ($seatZones as $zone) {
            $totalPrice += $prices[$zone] ?? 0;
        }

        $now = now();
        DB::table('suntaraporn_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
                'show_date'  => $showDate,
                'is_booked'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $seatKeys
        ));

        $alreadyBooked = [];
        // Slip is stored INSIDE the transaction so any DB failure rolls back cleanly.
        // On collision the uploaded file is deleted before returning 409.
        $slipPath  = null;
        $bookingId = null;

        try {
            DB::transaction(function () use ($request, $data, $seatKeys, $showDate, $totalPrice, $isUnpaid, &$alreadyBooked, &$slipPath, &$bookingId) {
                $seats = SuntarapornSeat::where('show_date', $showDate)
                    ->whereIn('seat_key', $seatKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                $alreadyBooked = $seats
                    ->filter(fn (SuntarapornSeat $seat) => $seat->is_booked)
                    ->keys()
                    ->all();

                if (!empty($alreadyBooked)) {
                    throw new RuntimeException('suntaraporn-seats-already-booked');
                }

                if ($request->hasFile('slip')) {
                    $slipPath = $request->file('slip')->store('suntaraporn-slips', 'public');
                }

                $booking = SuntarapornBooking::create([
                    'show_date'   => $showDate,
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'phone'       => $data['phone'],
                    'booker_name' => $data['booker_name'],
                    'slip_path'   => $slipPath,
                    'total_price' => $totalPrice,
                    'is_unpaid'   => $isUnpaid,
                ]);
                $bookingId = $booking->id;

                foreach ($seatKeys as $key) {
                    $seats[$key]->update([
                        'is_booked'  => true,
                        'booked_at'  => now(),
                        'booking_id' => $booking->id,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // ลบไฟล์สลิปที่อัปโหลดไปแล้วทุกกรณีที่ transaction ล้มเหลว (กันไฟล์ค้างบน disk)
            if ($slipPath) {
                Storage::disk('public')->delete($slipPath);
            }

            if ($e instanceof RuntimeException && $e->getMessage() === 'suntaraporn-seats-already-booked') {
                return response()->json([
                    'success' => false,
                    'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
                ], 409);
            }

            throw $e;
        }

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        // public เห็นเป็น "ขายแล้ว" (แดง) → bookedKeys; แอดมินอื่นเห็นสีเทาอ่อนถ้ายังไม่จ่าย → unpaidKeys
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, bookedKeys: $seatKeys, unpaidKeys: $isUnpaid ? $seatKeys : [])); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'        => BookingActivityLog::ACTION_BOOK,
            'show_date'     => $showDate,
            'actor_name'    => $data['booker_name'],
            'booking_id'    => $bookingId,
            'seat_keys'     => $seatKeys,
            'customer_name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'phone'         => $data['phone'],
            'total_price'   => $totalPrice,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Update Booking (edit seats + customer info) ───────────────

    public function updateBooking(Request $request, int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30|distinct',
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'phone'       => 'required|string|max:20',
            'slip'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $booking = SuntarapornBooking::with('seats')->findOrFail($id);

        if ($booking->is_sponsor) {
            return response()->json(['success' => false, 'error' => 'ไม่สามารถแก้ไขที่นั่ง Sponsor ผ่านช่องทางนี้ได้'], 422);
        }

        $showDate  = $booking->show_date->format('Y-m-d');
        $seatKeys  = array_values($data['seat_keys']);
        $seatZones = SuntarapornSeatMap::zonesFor($seatKeys);

        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        $prices     = SuntarapornZone::pluck('price', 'slug')->all();
        $totalPrice = 0;
        foreach ($seatZones as $zone) {
            $totalPrice += $prices[$zone] ?? 0;
        }

        $oldKeys = $booking->seats->pluck('seat_key')->all();

        $now = now();
        DB::table('suntaraporn_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
                'show_date'  => $showDate,
                'is_booked'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $seatKeys
        ));

        $conflicts   = [];
        $newSlipPath = null;
        $oldSlipPath = $booking->slip_path;

        try {
            DB::transaction(function () use ($request, $data, $booking, $showDate, $seatKeys, $oldKeys, $totalPrice, &$conflicts, &$newSlipPath) {
                $lockKeys = array_values(array_unique(array_merge($oldKeys, $seatKeys)));
                $seats = SuntarapornSeat::where('show_date', $showDate)
                    ->whereIn('seat_key', $lockKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                $conflicts = collect($seatKeys)
                    ->filter(fn (string $k) => isset($seats[$k]) && $seats[$k]->is_booked && (int) $seats[$k]->booking_id !== $booking->id)
                    ->values()
                    ->all();

                if (!empty($conflicts)) {
                    throw new RuntimeException('suntaraporn-seats-already-booked');
                }

                if ($request->hasFile('slip')) {
                    $newSlipPath = $request->file('slip')->store('suntaraporn-slips', 'public');
                }

                $removed = array_values(array_diff($oldKeys, $seatKeys));
                if (!empty($removed)) {
                    SuntarapornSeat::where('show_date', $showDate)
                        ->whereIn('seat_key', $removed)
                        ->where('booking_id', $booking->id)
                        ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);
                }

                foreach ($seatKeys as $key) {
                    $seats[$key]->update([
                        'is_booked'  => true,
                        'booked_at'  => $seats[$key]->booked_at ?? now(),
                        'booking_id' => $booking->id,
                    ]);
                }

                $booking->update([
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'phone'       => $data['phone'],
                    'total_price' => $totalPrice,
                    'slip_path'   => $newSlipPath ?? $booking->slip_path,
                ]);
            });
        } catch (\Throwable $e) {
            if ($newSlipPath) {
                Storage::disk('public')->delete($newSlipPath);
            }

            if ($e instanceof RuntimeException && $e->getMessage() === 'suntaraporn-seats-already-booked') {
                return response()->json([
                    'success' => false,
                    'error'   => 'ที่นั่ง ' . implode(', ', $conflicts) . ' ถูกจองไปแล้ว',
                ], 409);
            }

            throw $e;
        }

        if ($newSlipPath && $oldSlipPath) {
            Storage::disk('public')->delete($oldSlipPath);
        }

        $freedKeys  = array_values(array_diff($oldKeys, $seatKeys));
        $bookedKeys = array_values(array_diff($seatKeys, $oldKeys));

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, bookedKeys: $bookedKeys, freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'        => BookingActivityLog::ACTION_EDIT,
            'show_date'     => $showDate,
            'actor_name'    => $this->currentUser()->name,
            'booking_id'    => $booking->id,
            'seat_keys'     => $seatKeys,
            'customer_name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'phone'         => $data['phone'],
            'total_price'   => $totalPrice,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Booking Info (for popup) ──────────────────────────────────

    public function bookingInfo(Request $request, string $seatKey): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $showDate = $this->resolveShowDate($request);

        $seat = SuntarapornSeat::with('booking.seats')
            ->where('show_date', $showDate)
            ->where('seat_key', $seatKey)
            ->where('is_booked', true)
            ->first();

        if (!$seat || !$seat->booking) {
            return response()->json(['success' => false, 'error' => 'ไม่พบข้อมูลการจอง'], 404);
        }

        $booking = $seat->booking;

        return response()->json([
            'success'     => true,
            'booking_id'  => $booking->id,
            'first_name'  => $booking->first_name,
            'last_name'   => $booking->last_name,
            'phone'       => $booking->phone,
            'booker_name' => $booking->booker_name,
            'total_price' => $booking->total_price,
            'is_sponsor'  => (bool) $booking->is_sponsor,
            'is_unpaid'   => (bool) $booking->is_unpaid,
            'booked_at'   => $seat->booked_at?->format('d/m/Y H:i'),
            'slip_url'    => $booking->slip_path ? asset('storage/' . $booking->slip_path) : null,
            'all_seats'   => $booking->seats->pluck('seat_key')->all(),
        ]);
    }

    // ── Booking List (dashboard) ──────────────────────────────────

    public function bookingList(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user   = $this->currentUser();
        $search = trim($request->input('search', ''));

        $showDate  = $this->resolveShowDate($request);
        $showDates = self::SHOW_DATES;

        $query = SuntarapornBooking::with('seats')
            ->where('show_date', $showDate)
            ->orderByDesc('created_at');

        if ($search !== '') {
            // Escape MySQL LIKE wildcards so '%' or '_' in input don't match unintended rows.
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like    = '%' . $escaped . '%';
            // ESCAPE '\\' = escape char คือ backslash หนึ่งตัว (PHP '\\\\'→SQL '\\' ซึ่ง MariaDB อ่านว่า '\')
            $query->where(function ($q) use ($like) {
                $q->whereRaw("first_name LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("last_name LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("phone LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereHas('seats', fn ($s) => $s->whereRaw("seat_key LIKE ? ESCAPE '\\\\'", [$like]));
            });

            BookingActivityLog::record([
                'system'       => BookingActivityLog::SYSTEM_SUNTARAPORN,
                'action'       => BookingActivityLog::ACTION_SEARCH,
                'show_date'    => $showDate,
                'actor_name'   => $user->name,
                'search_query' => $search,
            ]);
        }

        $bookings = $query->get();

        $zoneColors  = SuntarapornZone::all()->mapWithKeys(fn ($z) => [
            $z->slug => ['bg' => $z->color, 'text' => $z->text_color, 'border' => $z->border_color],
        ])->all();
        $seatZoneMap = SuntarapornSeatMap::seats();

        return view('suntaraporn-bookings', compact('bookings', 'user', 'search', 'showDate', 'showDates', 'zoneColors', 'seatZoneMap'));
    }

    // ── Cancel Booking (manager only) ─────────────────────────────

    public function cancelBooking(int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();

        $booking = SuntarapornBooking::with('seats')->findOrFail($id);

        // เก็บ seat keys + วันแสดง ก่อน delete เพื่อ broadcast
        $freedKeys = $booking->seats->pluck('seat_key')->all();
        $showDate  = $booking->show_date->format('Y-m-d');
        $slipPath  = $booking->slip_path;

        DB::transaction(function () use ($booking) {
            SuntarapornSeat::where('booking_id', $booking->id)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);
            $booking->delete();
        });

        if ($slipPath) {
            Storage::disk('public')->delete($slipPath);
        }

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $freedKeys)), self::SELECTING_TTL);
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'        => BookingActivityLog::ACTION_CANCEL,
            'show_date'     => $showDate,
            'actor_name'    => $user->name,
            'booking_id'    => $booking->id,
            'seat_keys'     => $freedKeys,
            'customer_name' => trim($booking->first_name . ' ' . $booking->last_name),
            'phone'         => $booking->phone,
            'total_price'   => $booking->total_price,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Mark Paid (ยืนยันรับเงินจากที่นั่งที่ค้างจ่าย) ─────────────────
    // เคลียร์ flag is_unpaid → ที่นั่งกลายเป็นจองจ่ายแล้วปกติ (แอดมินเห็นเทาเข้ม)

    public function markPaid(int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $booking = SuntarapornBooking::with('seats')->findOrFail($id);

        if (!$booking->is_unpaid) {
            return response()->json(['success' => false, 'error' => 'การจองนี้ไม่ได้อยู่ในสถานะค้างจ่าย'], 422);
        }

        $showDate = $booking->show_date->format('Y-m-d');
        $booking->update(['is_unpaid' => false]);

        $paidKeys = $booking->seats->pluck('seat_key')->all();
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, paidKeys: $paidKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'        => BookingActivityLog::ACTION_PAID,
            'show_date'     => $showDate,
            'actor_name'    => $this->currentUser()->name,
            'booking_id'    => $booking->id,
            'seat_keys'     => $paidKeys,
            'customer_name' => trim($booking->first_name . ' ' . $booking->last_name),
            'phone'         => $booking->phone,
            'total_price'   => $booking->total_price,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Sponsor Seats (กันที่นั่งให้ผู้สนับสนุน — ฿0) ────────────────
    // สร้าง booking ราคา 0 + flag is_sponsor → ลูกค้าเห็นเป็น "ขายแล้ว" (สีแดง)
    // แต่ฝั่งแอดมินเห็นเป็นสีทองพร้อมโน้ตชื่อ Sponsor

    public function markSponsor(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'seat_keys'    => 'required|array|min:1',
            'seat_keys.*'  => 'required|string|max:30|distinct',
            'sponsor_name' => 'nullable|string|max:100',
        ]);

        $showDate    = $this->resolveShowDate($request);
        $actor       = $this->currentUser()->name;
        $seatKeys    = array_values($data['seat_keys']);
        $sponsorName = trim((string) ($data['sponsor_name'] ?? '')) ?: 'Sponsor';

        // ตรวจรหัสที่นั่งกับผังจริง
        $seatZones    = SuntarapornSeatMap::zonesFor($seatKeys);
        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        $now = now();
        DB::table('suntaraporn_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
                'show_date'  => $showDate,
                'is_booked'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $seatKeys
        ));

        $alreadyBooked = [];
        $bookingId     = null;

        try {
            DB::transaction(function () use ($showDate, $seatKeys, $sponsorName, $actor, &$alreadyBooked, &$bookingId) {
                $seats = SuntarapornSeat::where('show_date', $showDate)
                    ->whereIn('seat_key', $seatKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                $alreadyBooked = $seats
                    ->filter(fn (SuntarapornSeat $seat) => $seat->is_booked)
                    ->keys()
                    ->all();

                if (!empty($alreadyBooked)) {
                    throw new RuntimeException('suntaraporn-seats-already-booked');
                }

                $booking = SuntarapornBooking::create([
                    'show_date'   => $showDate,
                    'first_name'  => $sponsorName,
                    'last_name'   => '',
                    'phone'       => '',
                    'booker_name' => $actor,
                    'slip_path'   => null,
                    'total_price' => 0,
                    'is_sponsor'  => true,
                ]);
                $bookingId = $booking->id;

                foreach ($seatKeys as $key) {
                    $seats[$key]->update([
                        'is_booked'  => true,
                        'booked_at'  => now(),
                        'booking_id' => $booking->id,
                    ]);
                }
            });
        } catch (RuntimeException $e) {
            if ($e->getMessage() !== 'suntaraporn-seats-already-booked') {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
            ], 409);
        }

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        // public เห็นเป็น "ขายแล้ว" (สีแดง) → bookedKeys; แอดมินอื่นเห็นสีทอง → sponsorKeys
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, bookedKeys: $seatKeys, sponsorKeys: $seatKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'        => BookingActivityLog::ACTION_SPONSOR,
            'show_date'     => $showDate,
            'actor_name'    => $actor,
            'booking_id'    => $bookingId,
            'seat_keys'     => $seatKeys,
            'customer_name' => $sponsorName,
            'total_price'   => 0,
        ]);

        return response()->json(['success' => true]);
    }

    public function unmarkSponsor(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30',
        ]);

        $showDate = $this->resolveShowDate($request);
        $actor    = $this->currentUser()->name;

        // หา booking sponsor ที่ครอบคลุมที่นั่งที่เลือก แล้วปลดทั้งกลุ่ม
        $bookingIds = SuntarapornSeat::where('show_date', $showDate)
            ->whereIn('seat_key', $data['seat_keys'])
            ->whereNotNull('booking_id')
            ->whereHas('booking', fn ($q) => $q->where('is_sponsor', true))
            ->pluck('booking_id')
            ->unique()
            ->values()
            ->all();

        if (empty($bookingIds)) {
            return response()->json(['success' => false, 'error' => 'ไม่พบที่นั่ง Sponsor'], 404);
        }

        $freedKeys = [];
        DB::transaction(function () use ($bookingIds, &$freedKeys) {
            $freedKeys = SuntarapornSeat::whereIn('booking_id', $bookingIds)
                ->pluck('seat_key')
                ->all();

            SuntarapornSeat::whereIn('booking_id', $bookingIds)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);

            SuntarapornBooking::whereIn('id', $bookingIds)->where('is_sponsor', true)->delete();
        });

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $freedKeys)), self::SELECTING_TTL);
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'     => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'     => BookingActivityLog::ACTION_UNSPONSOR,
            'show_date'  => $showDate,
            'actor_name' => $actor,
            'seat_keys'  => $freedKeys,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Seat Selecting (real-time hover state) ────────────────────

    public function selectSeat(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30',
        ]);

        $showDate = $this->resolveShowDate($request);

        // ไม่ broadcast ที่นั่งที่จองแล้ว
        $alreadyBooked = SuntarapornSeat::where('show_date', $showDate)
            ->whereIn('seat_key', $data['seat_keys'])
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $selectingKeys = array_values(array_diff($data['seat_keys'], $alreadyBooked));

        if (!empty($selectingKeys)) {
            $this->mergeSelectingCache($this->selectingCacheKey($showDate), $selectingKeys);
            try { broadcast(new SeatStatusUpdated(showDate: $showDate, selectingKeys: $selectingKeys)); } catch (\Throwable) {}
        }

        return response()->json(['success' => true]);
    }

    public function deselectSeat(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30',
        ]);

        $showDate = $this->resolveShowDate($request);

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $data['seat_keys'])), self::SELECTING_TTL);
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, deselectingKeys: $data['seat_keys'])); } catch (\Throwable) {}

        return response()->json(['success' => true]);
    }

    // ── Zone Management (manager only) ────────────────────────────

    public function listZones(): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $zones = SuntarapornZone::orderBy('sort_order')->get()->map(fn ($z) => [
            'id'           => $z->id,
            'slug'         => $z->slug,
            'label'        => $z->label,
            'color'        => $z->color,
            'text_color'   => $z->text_color,
            'border_color' => $z->border_color,
            'price'        => $z->price,
            'sort_order'   => $z->sort_order,
        ])->values();

        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        return response()->json(['success' => true, 'zones' => $zones, 'row_zones' => $rowZones]);
    }

    public function createZone(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'slug'       => ['required', 'string', 'max:30', 'regex:/^[a-z0-9\-]+$/', 'unique:suntaraporn_zones,slug'],
            'label'      => 'required|string|max:50',
            'color'      => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'price'      => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $zone = SuntarapornZone::create([
            'slug'       => $data['slug'],
            'label'      => $data['label'],
            'color'      => $data['color'],
            'price'      => $data['price'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        SuntarapornSeatMap::flushCache();
        $this->broadcastZoneUpdate();

        return response()->json(['success' => true, 'zone' => [
            'id'           => $zone->id,
            'slug'         => $zone->slug,
            'label'        => $zone->label,
            'color'        => $zone->color,
            'text_color'   => $zone->text_color,
            'border_color' => $zone->border_color,
            'price'        => $zone->price,
            'sort_order'   => $zone->sort_order,
        ]]);
    }

    public function updateZone(Request $request, int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $zone = SuntarapornZone::findOrFail($id);

        $data = $request->validate([
            'label'      => 'required|string|max:50',
            'color'      => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'price'      => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $zone->update([
            'label'      => $data['label'],
            'color'      => $data['color'],
            'price'      => $data['price'],
            'sort_order' => $data['sort_order'] ?? $zone->sort_order,
        ]);

        SuntarapornSeatMap::flushCache();
        $this->broadcastZoneUpdate();

        return response()->json(['success' => true, 'zone' => [
            'id'           => $zone->id,
            'slug'         => $zone->slug,
            'label'        => $zone->label,
            'color'        => $zone->color,
            'text_color'   => $zone->text_color,
            'border_color' => $zone->border_color,
            'price'        => $zone->price,
            'sort_order'   => $zone->sort_order,
        ]]);
    }

    public function deleteZone(int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $zone = SuntarapornZone::findOrFail($id);

        // Check if any rows are assigned to this zone
        $rowCount = DB::table('suntaraporn_row_zones')->where('zone_id', $id)->count();
        if ($rowCount > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'ไม่สามารถลบ Zone ที่มีแถวที่นั่งอยู่ กรุณาย้ายแถวทั้งหมดออกก่อน',
            ], 422);
        }

        $zone->delete();

        SuntarapornSeatMap::flushCache();
        $this->broadcastZoneUpdate();

        return response()->json(['success' => true]);
    }

    public function updateRowZones(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'assignments'   => 'required|array',
            'assignments.*' => 'required|integer|exists:suntaraporn_zones,id',
        ]);

        $now = now();
        foreach ($data['assignments'] as $rowKey => $zoneId) {
            DB::table('suntaraporn_row_zones')
                ->where('row_key', $rowKey)
                ->update(['zone_id' => $zoneId, 'updated_at' => $now]);
        }

        SuntarapornSeatMap::flushCache();
        $this->broadcastZoneUpdate();

        return response()->json(['success' => true]);
    }

    // ── Reset (manager only) ──────────────────────────────────────

    public function resetSeats(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $showDate = $this->resolveShowDate($request);

        $freedKeys = [];
        $slipPaths = [];

        DB::transaction(function () use ($showDate, &$freedKeys, &$slipPaths) {
            $freedKeys = SuntarapornSeat::where('show_date', $showDate)
                ->where('is_booked', true)
                ->pluck('seat_key')
                ->all();

            $slipPaths = SuntarapornBooking::where('show_date', $showDate)
                ->whereNotNull('slip_path')
                ->pluck('slip_path')
                ->all();

            SuntarapornSeat::where('show_date', $showDate)->update([
                'is_booked'  => false,
                'booked_at'  => null,
                'booking_id' => null,
            ]);

            SuntarapornBooking::where('show_date', $showDate)->delete();
        });

        foreach ($slipPaths as $path) {
            try { Storage::disk('public')->delete($path); } catch (\Throwable) {}
        }

        Cache::forget($this->selectingCacheKey($showDate));

        if (!empty($freedKeys)) {
            try { broadcast(new SeatStatusUpdated(showDate: $showDate, freedKeys: $freedKeys)); } catch (\Throwable) {}

            BookingActivityLog::record([
                'system'     => BookingActivityLog::SYSTEM_SUNTARAPORN,
                'action'     => BookingActivityLog::ACTION_RESET,
                'show_date'  => $showDate,
                'actor_name' => $user->name,
                'seat_keys'  => $freedKeys,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ── Live State (polling fallback) ─────────────────────────────

    public function liveState(Request $request): JsonResponse
    {
        $showDate = $this->resolveShowDate($request);

        return response()->json([
            'booked'    => SuntarapornSeat::where('show_date', $showDate)
                ->where('is_booked', true)
                ->pluck('seat_key')
                ->all(),
            'selecting' => Cache::get($this->selectingCacheKey($showDate), []),
            'sponsor'   => SuntarapornSeat::where('show_date', $showDate)
                ->where('is_booked', true)
                ->whereHas('booking', fn ($q) => $q->where('is_sponsor', true))
                ->pluck('seat_key')
                ->all(),
            'unpaid'    => SuntarapornSeat::where('show_date', $showDate)
                ->where('is_booked', true)
                ->whereHas('booking', fn ($q) => $q->where('is_unpaid', true))
                ->pluck('seat_key')
                ->all(),
        ]);
    }

    // ── Export CSV ──────────────────────────────────────────────

    public function exportBookings(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $showDate = $this->resolveShowDate($request);
        $filename = 'suntaraporn-bookings-' . $showDate . '.csv';
        $bookings = SuntarapornBooking::with('seats')->where('show_date', $showDate)->orderBy('id')->get();
        $prices   = SuntarapornZone::pluck('price', 'slug')->all();
        $allSeats = SuntarapornSeatMap::seats();

        return response()->streamDownload(function () use ($bookings, $showDate, $prices, $allSeats) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — ให้ Excel แสดงภาษาไทยได้ถูกต้อง
            fwrite($out, "\xEF\xBB\xBF");

            // รายการจอง
            fputcsv($out, ['ลำดับ', 'รหัสการจอง', 'รอบการแสดง', 'ชื่อ', 'นามสกุล', 'เบอร์โทร',
                           'ที่นั่ง', 'จำนวนที่นั่ง', 'ราคารวม (฿)', 'ผู้บันทึก', 'มีสลิป', 'วันที่จอง']);

            foreach ($bookings as $i => $booking) {
                fputcsv($out, [
                    $i + 1,
                    $booking->id,
                    $booking->show_date->format('d/m/Y'),
                    $booking->first_name,
                    $booking->last_name,
                    $booking->phone,
                    $booking->seats->pluck('seat_key')->sort()->join(', '),
                    $booking->seats->count(),
                    $booking->total_price,
                    $booking->booker_name,
                    $booking->slip_path ? 'มี' : 'ไม่มี',
                    $booking->created_at->format('d/m/Y H:i'),
                ]);
            }

            // สรุปโซน (ส่วนที่ 2 ของไฟล์เดียวกัน)
            fputcsv($out, []);
            fputcsv($out, ['สรุปโซน — รอบ ' . $showDate]);
            fputcsv($out, ['โซน', 'ที่นั่งทั้งหมด', 'จองแล้ว', 'ว่าง', 'ราคา/ที่นั่ง (฿)', 'รายได้รวม (฿)']);

            $zoneLabels    = ['vip' => 'VIP', 'yellow' => 'เหลือง', 'blue' => 'ฟ้า',
                              'pink' => 'ชมพู', 'green' => 'เขียว', 'purple' => 'ม่วง', 'box' => 'BOX'];
            $totalPerZone  = array_count_values(array_values($allSeats));
            $bookedKeys    = SuntarapornSeat::where('show_date', $showDate)->where('is_booked', true)->pluck('seat_key')->all();
            $bookedPerZone = [];
            foreach ($bookedKeys as $key) {
                if (isset($allSeats[$key])) {
                    $z = $allSeats[$key];
                    $bookedPerZone[$z] = ($bookedPerZone[$z] ?? 0) + 1;
                }
            }

            $grandTotal = $grandBooked = $grandRevenue = 0;
            foreach ($zoneLabels as $zone => $label) {
                $total   = $totalPerZone[$zone]  ?? 0;
                $booked  = $bookedPerZone[$zone] ?? 0;
                $price   = $prices[$zone] ?? 0;
                $revenue = $booked * $price;
                fputcsv($out, [$label, $total, $booked, $total - $booked, $price, $revenue]);
                $grandTotal   += $total;
                $grandBooked  += $booked;
                $grandRevenue += $revenue;
            }

            fputcsv($out, ['รวมทั้งหมด', $grandTotal, $grandBooked, $grandTotal - $grandBooked, '', $grandRevenue]);
            fclose($out);

        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Activity History (manager only) ───────────────────────────

    public function history(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return redirect()->route('suntaraporn.index');
        }

        $showDate  = $this->resolveShowDate($request);
        $showDates = self::SHOW_DATES;

        $action = (string) $request->input('action', '');
        $from   = (string) $request->input('from', '');
        $to     = (string) $request->input('to', '');

        // base query ผูกกับรอบการแสดง + filter วันที่ (stats เห็นครบทุกประเภท ไม่ผูกกับ action filter)
        // ใช้ range เทียบ created_at ตรงๆ (ไม่ใช้ whereDate) เพื่อให้ index ทำงาน
        $base = BookingActivityLog::where('system', BookingActivityLog::SYSTEM_SUNTARAPORN)
            ->where('show_date', $showDate);
        if ($from !== '') {
            try { $base->where('created_at', '>=', Carbon::parse($from)->startOfDay()); } catch (\Throwable) {}
        }
        if ($to !== '') {
            try { $base->where('created_at', '<', Carbon::parse($to)->addDay()->startOfDay()); } catch (\Throwable) {}
        }

        $counts = (clone $base)
            ->selectRaw('action, COUNT(*) as c')
            ->groupBy('action')
            ->pluck('c', 'action');

        $query = (clone $base)->orderByDesc('created_at');
        if (in_array($action, BookingActivityLog::ACTIONS, true)) {
            $query->where('action', $action);
        }

        $logs   = $query->paginate(50)->withQueryString();
        $system = 'suntaraporn';

        return view('booking-activity-history', compact('logs', 'user', 'system', 'action', 'from', 'to', 'showDate', 'showDates', 'counts'));
    }

    // ── Broadcast Zone Update ─────────────────────────────────────

    private function broadcastZoneUpdate(): void
    {
        try {
            $zones = SuntarapornZone::orderBy('sort_order')->get()->map(fn ($z) => [
                'id'           => $z->id,
                'slug'         => $z->slug,
                'label'        => $z->label,
                'color'        => $z->color,
                'text_color'   => $z->text_color,
                'border_color' => $z->border_color,
                'price'        => $z->price,
            ])->all();

            $rowZones = DB::table('suntaraporn_row_zones')
                ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
                ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
                ->all();

            $pusher = new \Pusher\Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                ['cluster' => config('broadcasting.connections.pusher.options.cluster'), 'useTLS' => true]
            );

            // โซนใช้ร่วมกันทุกวัน → ส่งไปทุก channel ของแต่ละรอบการแสดง
            foreach (array_keys(self::SHOW_DATES) as $date) {
                $pusher->trigger('suntaraporn-concert-' . $date, 'zone-config-updated', compact('zones', 'rowZones'));
            }
        } catch (\Throwable) {}
    }
}
