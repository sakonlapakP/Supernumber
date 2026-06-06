<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusUpdated;
use App\Exports\SuntarapornBookingsExport;
use App\Models\BookingActivityLog;
use App\Models\SuntarapornBooking;
use App\Models\SuntarapornZone;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SuntarapornSeat;
use App\Models\User;
use App\Services\SuntarapornSeatMap;
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
    private const SELECTING_TTL    = 180; // seconds

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

        $zones = SuntarapornZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        $totalSeats     = SuntarapornSeatMap::totalSeats();
        $selectingSeats = Cache::get($this->selectingCacheKey($showDate), []);

        return view('suntaraporn-band', compact('bookedSeats', 'prices', 'user', 'totalSeats', 'selectingSeats', 'zones', 'rowZones', 'showDate', 'showDates'));
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
        ]);

        $showDate = $this->resolveShowDate($request);

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
            DB::transaction(function () use ($request, $data, $seatKeys, $showDate, $totalPrice, &$alreadyBooked, &$slipPath, &$bookingId) {
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

            if ($slipPath) {
                Storage::disk('public')->delete($slipPath);
            }

            return response()->json([
                'success' => false,
                'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
            ], 409);
        }

        $cacheKey = $this->selectingCacheKey($showDate);
        $existing = Cache::get($cacheKey, []);
        Cache::put($cacheKey, array_values(array_diff($existing, $seatKeys)), self::SELECTING_TTL);
        try { broadcast(new SeatStatusUpdated(showDate: $showDate, bookedKeys: $seatKeys)); } catch (\Throwable) {}

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
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'LIKE', $like)
                  ->orWhere('last_name', 'LIKE', $like)
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $like)
                  ->orWhere('phone', 'LIKE', $like);
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
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'เฉพาะ Manager เท่านั้น'], 403);
        }

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
            $cacheKey = $this->selectingCacheKey($showDate);
            $existing = Cache::get($cacheKey, []);
            Cache::put($cacheKey, array_values(array_unique(array_merge($existing, $selectingKeys))), self::SELECTING_TTL);
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
            Storage::disk('public')->delete($path);
        }

        Cache::forget($this->selectingCacheKey($showDate));

        if (!empty($freedKeys)) {
            try { broadcast(new SeatStatusUpdated(showDate: $showDate, freedKeys: $freedKeys)); } catch (\Throwable) {}
        }

        BookingActivityLog::record([
            'system'     => BookingActivityLog::SYSTEM_SUNTARAPORN,
            'action'     => BookingActivityLog::ACTION_RESET,
            'show_date'  => $showDate,
            'actor_name' => $user->name,
            'seat_keys'  => $freedKeys,
        ]);

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
        ]);
    }

    // ── Export Excel ──────────────────────────────────────────────

    public function exportBookings(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $showDate = $this->resolveShowDate($request);
        $filename = 'suntaraporn-bookings-' . $showDate . '.xlsx';

        return Excel::download(new SuntarapornBookingsExport($showDate), $filename);
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
        $base = BookingActivityLog::where('system', BookingActivityLog::SYSTEM_SUNTARAPORN)
            ->where('show_date', $showDate);
        if ($from !== '') {
            $base->whereDate('created_at', '>=', $from);
        }
        if ($to !== '') {
            $base->whereDate('created_at', '<=', $to);
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
