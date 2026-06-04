<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusUpdated;
use App\Exports\SuntarapornBookingsExport;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class SuntarapornBandController extends Controller
{
    private const ALLOWED_ROLES = [User::ROLE_SUNTARAPORN, User::ROLE_MANAGER];
    private const SESSION_KEY   = 'suntaraporn_user_id';

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

    public function publicView(): View
    {
        $bookedSeats = SuntarapornSeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $zones = SuntarapornZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        $totalSeats = SuntarapornSeatMap::totalSeats();

        return view('suntaraporn-public', compact('bookedSeats', 'prices', 'totalSeats', 'zones', 'rowZones'));
    }

    // ── Main Page ─────────────────────────────────────────────────

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $bookedSeats = SuntarapornSeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $zones = SuntarapornZone::orderBy('sort_order')->get();
        $prices = $zones->pluck('price', 'slug')->all();
        $rowZones = DB::table('suntaraporn_row_zones')
            ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
            ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
            ->all();

        $totalSeats = SuntarapornSeatMap::totalSeats();

        return view('suntaraporn-band', compact('bookedSeats', 'prices', 'user', 'totalSeats', 'zones', 'rowZones'));
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
                'is_booked'  => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $seatKeys
        ));

        $alreadyBooked = [];
        // Slip is stored INSIDE the transaction so any DB failure rolls back cleanly.
        // On collision the uploaded file is deleted before returning 409.
        $slipPath = null;

        try {
            DB::transaction(function () use ($request, $data, $seatKeys, $totalPrice, &$alreadyBooked, &$slipPath) {
                $seats = SuntarapornSeat::whereIn('seat_key', $seatKeys)
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
                    'first_name'  => $data['first_name'],
                    'last_name'   => $data['last_name'],
                    'phone'       => $data['phone'],
                    'booker_name' => $data['booker_name'],
                    'slip_path'   => $slipPath,
                    'total_price' => $totalPrice,
                ]);

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

        try { broadcast(new SeatStatusUpdated(bookedKeys: $seatKeys)); } catch (\Throwable) {}

        return response()->json(['success' => true]);
    }

    // ── Booking Info (for popup) ──────────────────────────────────

    public function bookingInfo(string $seatKey): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $seat = SuntarapornSeat::with('booking.seats')
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

        $query = SuntarapornBooking::with('seats')->orderByDesc('created_at');

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
        }

        $bookings = $query->get();

        return view('suntaraporn-bookings', compact('bookings', 'user', 'search'));
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

        // เก็บ seat keys ก่อน delete เพื่อ broadcast
        $freedKeys = $booking->seats->pluck('seat_key')->all();

        DB::transaction(function () use ($booking) {
            SuntarapornSeat::where('booking_id', $booking->id)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);

            if ($booking->slip_path) {
                Storage::disk('public')->delete($booking->slip_path);
            }

            $booking->delete();
        });

        try { broadcast(new SeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}

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

        // ไม่ broadcast ที่นั่งที่จองแล้ว
        $alreadyBooked = SuntarapornSeat::whereIn('seat_key', $data['seat_keys'])
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $selectingKeys = array_values(array_diff($data['seat_keys'], $alreadyBooked));

        if (!empty($selectingKeys)) {
            try { broadcast(new SeatStatusUpdated(selectingKeys: $selectingKeys)); } catch (\Throwable) {}
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

        try { broadcast(new SeatStatusUpdated(deselectingKeys: $data['seat_keys'])); } catch (\Throwable) {}

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

        $freedKeys = SuntarapornSeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        // Collect slip paths INSIDE the transaction so concurrent bookings that
        // commit just before the delete are included and their slips are cleaned up.
        $slipPaths = [];

        DB::transaction(function () use (&$slipPaths) {
            $slipPaths = SuntarapornBooking::whereNotNull('slip_path')
                ->pluck('slip_path')
                ->all();

            SuntarapornSeat::query()->update([
                'is_booked'  => false,
                'booked_at'  => null,
                'booking_id' => null,
            ]);

            SuntarapornBooking::query()->delete();
        });

        foreach ($slipPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        if (!empty($freedKeys)) {
            try { broadcast(new SeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}
        }

        return response()->json(['success' => true]);
    }

    // ── Export Excel ──────────────────────────────────────────────

    public function exportBookings(): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $filename = 'suntaraporn-bookings-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new SuntarapornBookingsExport(), $filename);
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

            $pusher->trigger('suntaraporn-concert', 'zone-config-updated', compact('zones', 'rowZones'));
        } catch (\Throwable) {}
    }
}
