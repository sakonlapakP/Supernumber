<?php

namespace App\Http\Controllers;

use App\Events\LikaySeatStatusUpdated;
use App\Models\BookingActivityLog;
use App\Models\LikayBooking;
use App\Models\LikayZone;
use App\Models\LikaySeat;
use App\Models\User;
use App\Services\LikaySeatMap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class LikayLiveAtTheTheaterController extends Controller
{
    private const ALLOWED_ROLES   = [User::ROLE_LIKAY, User::ROLE_MANAGER];
    private const SESSION_KEY     = 'likay_user_id';
    private const SELECTING_CACHE = 'likay_selecting_keys';
    private const SELECTING_TTL   = 90; // seconds

    /**
     * เพิ่ม keys เข้า selecting cache แบบ atomic (กัน lost-update เมื่อหลาย admin เลือกพร้อมกัน)
     * ถ้าล็อกไม่ได้ (driver ไม่รองรับ/timeout) จะ fallback เขียนตรงๆ — ยอมเสี่ยง race เล็กน้อยดีกว่า request พัง
     */
    private function mergeSelectingCache(array $addKeys): void
    {
        $merge = function () use ($addKeys) {
            $existing = Cache::get(self::SELECTING_CACHE, []);
            Cache::put(self::SELECTING_CACHE, array_values(array_unique(array_merge($existing, $addKeys))), self::SELECTING_TTL);
        };

        try {
            Cache::lock(self::SELECTING_CACHE . '_lock', 5)->block(3, $merge);
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
            return redirect()->route('likay.login');
        }
        return null;
    }

    // ── Login ────────────────────────────────────────────────────

    public function showLogin(): View|RedirectResponse
    {
        $user = $this->currentUser();
        if ($user && in_array($user->role, self::ALLOWED_ROLES, true)) {
            return redirect()->route('likay.index');
        }
        return view('likay-login');
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

        return redirect()->route('likay.index');
    }

    public function doLogout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        $request->session()->regenerateToken();
        return redirect()->route('likay.login');
    }

    // ── Public View (no auth required) ───────────────────────────

    public function publicView(): View
    {
        $bookedSeats = LikaySeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $zones = LikayZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('likay_row_zones')
            ->join('likay_zones', 'likay_row_zones.zone_id', '=', 'likay_zones.id')
            ->pluck('likay_zones.slug', 'likay_row_zones.row_key')
            ->all();

        $totalSeats     = LikaySeatMap::totalSeats();
        $selectingSeats = Cache::get(self::SELECTING_CACHE, []);

        return view('likay-public', compact('bookedSeats', 'prices', 'totalSeats', 'selectingSeats', 'zones', 'rowZones'));
    }

    // ── Main Page ─────────────────────────────────────────────────

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $bookedSeats = LikaySeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        // ที่นั่ง Sponsor (booking ฿0) — แอดมินเห็นแยกเป็นสีทอง + โน้ต
        // (ยังคงอยู่ใน $bookedSeats ด้วย เพื่อกันการจองทับ)
        $sponsorSeats = LikaySeat::where('is_booked', true)
            ->whereHas('booking', fn ($q) => $q->where('is_sponsor', true))
            ->with('booking:id,first_name')
            ->get()
            ->mapWithKeys(fn (LikaySeat $s) => [$s->seat_key => $s->booking?->first_name ?? 'Sponsor'])
            ->all();

        // ที่นั่ง "ยังไม่จ่ายตัง" — แอดมินเห็นเป็นสีเทาอ่อน, ลูกค้าเห็นเป็น "ขายแล้ว" ปกติ
        $unpaidSeats = LikaySeat::where('is_booked', true)
            ->whereHas('booking', fn ($q) => $q->where('is_unpaid', true))
            ->pluck('seat_key')
            ->all();

        $zones = LikayZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('likay_row_zones')
            ->join('likay_zones', 'likay_row_zones.zone_id', '=', 'likay_zones.id')
            ->pluck('likay_zones.slug', 'likay_row_zones.row_key')
            ->all();

        $totalSeats = LikaySeatMap::totalSeats();

        $allSeats = LikaySeatMap::seats();
        $potentialRevenue = array_sum(array_map(fn ($zone) => $prices[$zone] ?? 0, $allSeats));
        $bookedSet = array_flip($bookedSeats);
        // รายได้นับเฉพาะที่นั่งจองจริง — ที่นั่ง Sponsor (฿0) ไม่นับเป็นรายได้
        $bookedRevenue = array_sum(array_map(
            fn ($key, $zone) => (isset($bookedSet[$key]) && !isset($sponsorSeats[$key])) ? ($prices[$zone] ?? 0) : 0,
            array_keys($allSeats),
            $allSeats
        ));

        $selectingSeats = Cache::get(self::SELECTING_CACHE, []);

        return view('likay-band', compact('bookedSeats', 'sponsorSeats', 'unpaidSeats', 'prices', 'user', 'totalSeats', 'potentialRevenue', 'bookedRevenue', 'zones', 'rowZones', 'selectingSeats'));
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

        $isUnpaid = $request->boolean('is_unpaid');
        $data['booker_name'] = $this->currentUser()->name;
        $seatKeys = array_values($data['seat_keys']);
        $seatZones = LikaySeatMap::zonesFor($seatKeys);

        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        $prices    = LikayZone::pluck('price', 'slug')->all();
        $totalPrice = 0;
        foreach ($seatZones as $zone) {
            $totalPrice += $prices[$zone] ?? 0;
        }

        $now = now();
        DB::table('likay_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
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
            DB::transaction(function () use ($request, $data, $seatKeys, $totalPrice, $isUnpaid, &$alreadyBooked, &$slipPath, &$bookingId) {
                $seats = LikaySeat::whereIn('seat_key', $seatKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                $alreadyBooked = $seats
                    ->filter(fn (LikaySeat $seat) => $seat->is_booked)
                    ->keys()
                    ->all();

                if (!empty($alreadyBooked)) {
                    throw new RuntimeException('likay-seats-already-booked');
                }

                if ($request->hasFile('slip')) {
                    $slipPath = $request->file('slip')->store('likay-slips', 'public');
                }

                $booking = LikayBooking::create([
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

            if ($e instanceof RuntimeException && $e->getMessage() === 'likay-seats-already-booked') {
                return response()->json([
                    'success' => false,
                    'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
                ], 409);
            }

            throw $e;
        }

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        // public เห็นเป็น "ขายแล้ว" (แดง) → bookedKeys; แอดมินอื่นเห็นสีเทาอ่อนถ้ายังไม่จ่าย → unpaidKeys
        try { broadcast(new LikaySeatStatusUpdated(bookedKeys: $seatKeys, unpaidKeys: $isUnpaid ? $seatKeys : [])); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_LIKAY,
            'action'        => BookingActivityLog::ACTION_BOOK,
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

        $booking = LikayBooking::with('seats')->findOrFail($id);

        if ($booking->is_sponsor) {
            return response()->json(['success' => false, 'error' => 'ไม่สามารถแก้ไขที่นั่ง Sponsor ผ่านช่องทางนี้ได้'], 422);
        }

        $seatKeys  = array_values($data['seat_keys']);
        $seatZones = LikaySeatMap::zonesFor($seatKeys);

        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        $prices     = LikayZone::pluck('price', 'slug')->all();
        $totalPrice = 0;
        foreach ($seatZones as $zone) {
            $totalPrice += $prices[$zone] ?? 0;
        }

        $oldKeys = $booking->seats->pluck('seat_key')->all();

        $now = now();
        DB::table('likay_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
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
            DB::transaction(function () use ($request, $data, $booking, $seatKeys, $oldKeys, $totalPrice, &$conflicts, &$newSlipPath) {
                $lockKeys = array_values(array_unique(array_merge($oldKeys, $seatKeys)));
                $seats = LikaySeat::whereIn('seat_key', $lockKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                // ที่นั่งใหม่ที่ถูกจองโดย booking อื่นอยู่แล้ว → ชน
                $conflicts = collect($seatKeys)
                    ->filter(fn (string $k) => isset($seats[$k]) && $seats[$k]->is_booked && (int) $seats[$k]->booking_id !== $booking->id)
                    ->values()
                    ->all();

                if (!empty($conflicts)) {
                    throw new RuntimeException('likay-seats-already-booked');
                }

                if ($request->hasFile('slip')) {
                    $newSlipPath = $request->file('slip')->store('likay-slips', 'public');
                }

                // ปล่อยที่นั่งเดิมที่ถูกเอาออก
                $removed = array_values(array_diff($oldKeys, $seatKeys));
                if (!empty($removed)) {
                    LikaySeat::whereIn('seat_key', $removed)
                        ->where('booking_id', $booking->id)
                        ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);
                }

                // จองที่นั่งใหม่ + คงที่นั่งเดิมไว้ผูกกับ booking นี้
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

            if ($e instanceof RuntimeException && $e->getMessage() === 'likay-seats-already-booked') {
                return response()->json([
                    'success' => false,
                    'error'   => 'ที่นั่ง ' . implode(', ', $conflicts) . ' ถูกจองไปแล้ว',
                ], 409);
            }

            throw $e;
        }

        // ลบสลิปเก่าหลัง commit สำเร็จ (เฉพาะเมื่ออัปโหลดใหม่)
        if ($newSlipPath && $oldSlipPath) {
            Storage::disk('public')->delete($oldSlipPath);
        }

        $freedKeys  = array_values(array_diff($oldKeys, $seatKeys));
        $bookedKeys = array_values(array_diff($seatKeys, $oldKeys));

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        try { broadcast(new LikaySeatStatusUpdated(bookedKeys: $bookedKeys, freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_LIKAY,
            'action'        => BookingActivityLog::ACTION_EDIT,
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

    public function bookingInfo(string $seatKey): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $seat = LikaySeat::with('booking.seats')
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

        $query = LikayBooking::with('seats')->orderByDesc('created_at');

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
                'system'       => BookingActivityLog::SYSTEM_LIKAY,
                'action'       => BookingActivityLog::ACTION_SEARCH,
                'actor_name'   => $user->name,
                'search_query' => $search,
            ]);
        }

        $bookings = $query->get();

        $zoneColors  = LikayZone::all()->mapWithKeys(fn ($z) => [
            $z->slug => ['bg' => $z->color, 'text' => $z->text_color, 'border' => $z->border_color],
        ])->all();
        $seatZoneMap = LikaySeatMap::seats();

        return view('likay-bookings', compact('bookings', 'user', 'search', 'zoneColors', 'seatZoneMap'));
    }

    // ── Cancel Booking (manager only) ─────────────────────────────

    public function cancelBooking(int $id): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();

        $booking = LikayBooking::with('seats')->findOrFail($id);

        $freedKeys = $booking->seats->pluck('seat_key')->all();
        $slipPath  = $booking->slip_path;

        DB::transaction(function () use ($booking) {
            LikaySeat::where('booking_id', $booking->id)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);
            $booking->delete();
        });

        if ($slipPath) {
            Storage::disk('public')->delete($slipPath);
        }

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $freedKeys)), self::SELECTING_TTL);
        try { broadcast(new LikaySeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_LIKAY,
            'action'        => BookingActivityLog::ACTION_CANCEL,
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

        $booking = LikayBooking::with('seats')->findOrFail($id);

        if (!$booking->is_unpaid) {
            return response()->json(['success' => false, 'error' => 'การจองนี้ไม่ได้อยู่ในสถานะค้างจ่าย'], 422);
        }

        $booking->update(['is_unpaid' => false]);

        $paidKeys = $booking->seats->pluck('seat_key')->all();
        try { broadcast(new LikaySeatStatusUpdated(paidKeys: $paidKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_LIKAY,
            'action'        => BookingActivityLog::ACTION_PAID,
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

        $actor       = $this->currentUser()->name;
        $seatKeys    = array_values($data['seat_keys']);
        $sponsorName = trim((string) ($data['sponsor_name'] ?? '')) ?: 'Sponsor';

        // ตรวจรหัสที่นั่งกับผังจริง
        $seatZones    = LikaySeatMap::zonesFor($seatKeys);
        $invalidSeats = array_values(array_diff($seatKeys, array_keys($seatZones)));
        if (!empty($invalidSeats)) {
            return response()->json([
                'success' => false,
                'error'   => 'รหัสที่นั่งไม่ถูกต้อง: ' . implode(', ', $invalidSeats),
            ], 422);
        }

        $now = now();
        DB::table('likay_seats')->insertOrIgnore(array_map(
            fn (string $key) => [
                'seat_key'   => $key,
                'is_booked'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $seatKeys
        ));

        $alreadyBooked = [];
        $bookingId     = null;

        try {
            DB::transaction(function () use ($seatKeys, $sponsorName, $actor, &$alreadyBooked, &$bookingId) {
                $seats = LikaySeat::whereIn('seat_key', $seatKeys)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('seat_key');

                $alreadyBooked = $seats
                    ->filter(fn (LikaySeat $seat) => $seat->is_booked)
                    ->keys()
                    ->all();

                if (!empty($alreadyBooked)) {
                    throw new RuntimeException('likay-seats-already-booked');
                }

                $booking = LikayBooking::create([
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
            if ($e->getMessage() !== 'likay-seats-already-booked') {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
            ], 409);
        }

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        // public เห็นเป็น "ขายแล้ว" (สีแดง) → bookedKeys; แอดมินอื่นเห็นสีทอง → sponsorKeys
        try { broadcast(new LikaySeatStatusUpdated(bookedKeys: $seatKeys, sponsorKeys: $seatKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'        => BookingActivityLog::SYSTEM_LIKAY,
            'action'        => BookingActivityLog::ACTION_SPONSOR,
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

        $actor = $this->currentUser()->name;

        // หา booking sponsor ที่ครอบคลุมที่นั่งที่เลือก แล้วปลดทั้งกลุ่ม
        $bookingIds = LikaySeat::whereIn('seat_key', $data['seat_keys'])
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
            $freedKeys = LikaySeat::whereIn('booking_id', $bookingIds)
                ->pluck('seat_key')
                ->all();

            LikaySeat::whereIn('booking_id', $bookingIds)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);

            LikayBooking::whereIn('id', $bookingIds)->where('is_sponsor', true)->delete();
        });

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $freedKeys)), self::SELECTING_TTL);
        try { broadcast(new LikaySeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}

        BookingActivityLog::record([
            'system'     => BookingActivityLog::SYSTEM_LIKAY,
            'action'     => BookingActivityLog::ACTION_UNSPONSOR,
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

        $alreadyBooked = LikaySeat::whereIn('seat_key', $data['seat_keys'])
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $selectingKeys = array_values(array_diff($data['seat_keys'], $alreadyBooked));

        if (!empty($selectingKeys)) {
            $this->mergeSelectingCache($selectingKeys);
            try { broadcast(new LikaySeatStatusUpdated(selectingKeys: $selectingKeys)); } catch (\Throwable) {}
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

        $existing = Cache::get(self::SELECTING_CACHE, []);
        Cache::put(self::SELECTING_CACHE, array_values(array_diff($existing, $data['seat_keys'])), self::SELECTING_TTL);
        try { broadcast(new LikaySeatStatusUpdated(deselectingKeys: $data['seat_keys'])); } catch (\Throwable) {}

        return response()->json(['success' => true]);
    }

    // ── Zone Management (manager only) ────────────────────────────

    public function listZones(): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $zones = LikayZone::orderBy('sort_order')->get()->map(fn ($z) => [
            'id'           => $z->id,
            'slug'         => $z->slug,
            'label'        => $z->label,
            'color'        => $z->color,
            'text_color'   => $z->text_color,
            'border_color' => $z->border_color,
            'price'        => $z->price,
            'sort_order'   => $z->sort_order,
        ])->values();

        $rowZones = DB::table('likay_row_zones')
            ->join('likay_zones', 'likay_row_zones.zone_id', '=', 'likay_zones.id')
            ->pluck('likay_zones.slug', 'likay_row_zones.row_key')
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
            'slug'       => ['required', 'string', 'max:30', 'regex:/^[a-z0-9\-]+$/', 'unique:likay_zones,slug'],
            'label'      => 'required|string|max:50',
            'color'      => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'price'      => 'required|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $zone = LikayZone::create([
            'slug'       => $data['slug'],
            'label'      => $data['label'],
            'color'      => $data['color'],
            'price'      => $data['price'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        LikaySeatMap::flushCache();
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

        $zone = LikayZone::findOrFail($id);

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

        LikaySeatMap::flushCache();
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

        $zone = LikayZone::findOrFail($id);

        // Check if any rows are assigned to this zone
        $rowCount = DB::table('likay_row_zones')->where('zone_id', $id)->count();
        if ($rowCount > 0) {
            return response()->json([
                'success' => false,
                'error'   => 'ไม่สามารถลบ Zone ที่มีแถวที่นั่งอยู่ กรุณาย้ายแถวทั้งหมดออกก่อน',
            ], 422);
        }

        $zone->delete();

        LikaySeatMap::flushCache();
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
            'assignments.*' => 'required|integer|exists:likay_zones,id',
        ]);

        $now = now();
        foreach ($data['assignments'] as $rowKey => $zoneId) {
            DB::table('likay_row_zones')
                ->where('row_key', $rowKey)
                ->update(['zone_id' => $zoneId, 'updated_at' => $now]);
        }

        LikaySeatMap::flushCache();
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

        $freedKeys = [];
        $slipPaths = [];

        DB::transaction(function () use (&$freedKeys, &$slipPaths) {
            $freedKeys = LikaySeat::where('is_booked', true)
                ->pluck('seat_key')
                ->all();

            $slipPaths = LikayBooking::whereNotNull('slip_path')
                ->pluck('slip_path')
                ->all();

            LikaySeat::query()->update([
                'is_booked'  => false,
                'booked_at'  => null,
                'booking_id' => null,
            ]);

            LikayBooking::query()->delete();
        });

        foreach ($slipPaths as $path) {
            try { Storage::disk('public')->delete($path); } catch (\Throwable) {}
        }

        Cache::forget(self::SELECTING_CACHE);

        if (!empty($freedKeys)) {
            try { broadcast(new LikaySeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}

            BookingActivityLog::record([
                'system'     => BookingActivityLog::SYSTEM_LIKAY,
                'action'     => BookingActivityLog::ACTION_RESET,
                'actor_name' => $user->name,
                'seat_keys'  => $freedKeys,
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ── Live State (polling fallback) ─────────────────────────────

    public function liveState(): JsonResponse
    {
        return response()->json([
            'booked'     => LikaySeat::where('is_booked', true)->pluck('seat_key')->all(),
            'selecting'  => Cache::get(self::SELECTING_CACHE, []),
            'sponsor'    => LikaySeat::where('is_booked', true)
                ->whereHas('booking', fn ($q) => $q->where('is_sponsor', true))
                ->pluck('seat_key')
                ->all(),
            'unpaid'     => LikaySeat::where('is_booked', true)
                ->whereHas('booking', fn ($q) => $q->where('is_unpaid', true))
                ->pluck('seat_key')
                ->all(),
        ]);
    }

    // ── Export CSV ──────────────────────────────────────────────

    public function exportBookings(): \Symfony\Component\HttpFoundation\StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $filename  = 'likay-bookings-' . now()->format('Y-m-d') . '.csv';
        $bookings  = LikayBooking::with('seats')->orderBy('id')->get();
        $prices    = LikayZone::pluck('price', 'slug')->all();
        $allSeats  = LikaySeatMap::seats();

        return response()->streamDownload(function () use ($bookings, $prices, $allSeats) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM — ให้ Excel แสดงภาษาไทยได้ถูกต้อง
            fwrite($out, "\xEF\xBB\xBF");

            // รายการจอง
            fputcsv($out, ['ลำดับ', 'รหัสการจอง', 'ชื่อ', 'นามสกุล', 'เบอร์โทร',
                           'ที่นั่ง', 'จำนวนที่นั่ง', 'ราคารวม (฿)', 'ผู้บันทึก', 'มีสลิป', 'วันที่จอง']);

            foreach ($bookings as $i => $booking) {
                fputcsv($out, [
                    $i + 1,
                    $booking->id,
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
            fputcsv($out, ['สรุปโซน']);
            fputcsv($out, ['โซน', 'ที่นั่งทั้งหมด', 'จองแล้ว', 'ว่าง', 'ราคา/ที่นั่ง (฿)', 'รายได้รวม (฿)']);

            $zoneLabels    = ['yellow' => 'เหลือง', 'blue' => 'เขียว', 'pink' => 'ฟ้า',
                              'green' => 'แดง', 'purple' => 'ม่วง', 'box' => 'BOX'];
            $totalPerZone  = array_count_values(array_values($allSeats));
            $bookedKeys    = LikaySeat::where('is_booked', true)->pluck('seat_key')->all();
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
            return redirect()->route('likay.index');
        }

        $action = (string) $request->input('action', '');
        $from   = (string) $request->input('from', '');
        $to     = (string) $request->input('to', '');
        $search = trim((string) $request->input('search', ''));

        // base query ใช้ filter วันที่ร่วมกัน (stats เห็นครบทุกประเภท ไม่ผูกกับ action filter)
        // ใช้ range เทียบ created_at ตรงๆ (ไม่ใช้ whereDate) เพื่อให้ index ทำงาน
        $base = BookingActivityLog::where('system', BookingActivityLog::SYSTEM_LIKAY);
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
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw("actor_name LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("customer_name LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("phone LIKE ? ESCAPE '\\\\'", [$like])
                  ->orWhereRaw("search_query LIKE ? ESCAPE '\\\\'", [$like]);
            });
        }

        $logs   = $query->paginate(50)->withQueryString();
        $system = 'likay';

        $bookingIds = $logs->pluck('booking_id')->filter()->unique()->values()->all();
        $slips = $bookingIds
            ? LikayBooking::whereIn('id', $bookingIds)->whereNotNull('slip_path')->pluck('slip_path', 'id')->all()
            : [];

        return view('booking-activity-history', compact('logs', 'user', 'system', 'action', 'from', 'to', 'search', 'counts', 'slips'));
    }

    // ── Broadcast Zone Update ─────────────────────────────────────

    private function broadcastZoneUpdate(): void
    {
        try {
            $zones = LikayZone::orderBy('sort_order')->get()->map(fn ($z) => [
                'id'           => $z->id,
                'slug'         => $z->slug,
                'label'        => $z->label,
                'color'        => $z->color,
                'text_color'   => $z->text_color,
                'border_color' => $z->border_color,
                'price'        => $z->price,
            ])->all();

            $rowZones = DB::table('likay_row_zones')
                ->join('likay_zones', 'likay_row_zones.zone_id', '=', 'likay_zones.id')
                ->pluck('likay_zones.slug', 'likay_row_zones.row_key')
                ->all();

            $pusher = new \Pusher\Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                ['cluster' => config('broadcasting.connections.pusher.options.cluster'), 'useTLS' => true]
            );

            $pusher->trigger('likay-concert', 'zone-config-updated', compact('zones', 'rowZones'));
        } catch (\Throwable) {}
    }
}
