<?php

namespace App\Http\Controllers;

use App\Events\LikaySeatStatusUpdated;
use App\Exports\LikayBookingsExport;
use App\Models\LikayBooking;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\LikaySeat;
use App\Models\User;
use App\Services\LikaySeatMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class LikayLiveInTheaterController extends Controller
{
    private const ALLOWED_ROLES = [User::ROLE_LIKAY, User::ROLE_MANAGER];
    private const SESSION_KEY   = 'likay_user_id';

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

        $prices = DB::table('likay_zone_prices')
            ->pluck('price', 'zone')
            ->all();
        $totalSeats = LikaySeatMap::totalSeats();

        return view('likay-public', compact('bookedSeats', 'prices', 'totalSeats'));
    }

    // ── Main Page ─────────────────────────────────────────────────

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $bookedSeats = LikaySeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $prices = DB::table('likay_zone_prices')
            ->pluck('price', 'zone')
            ->all();
        $totalSeats = LikaySeatMap::totalSeats();

        return view('likay-band', compact('bookedSeats', 'prices', 'user', 'totalSeats'));
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

        $prices    = DB::table('likay_zone_prices')->pluck('price', 'zone')->all();
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
        $slipPath = null;

        try {
            DB::transaction(function () use ($request, $data, $seatKeys, $totalPrice, &$alreadyBooked, &$slipPath) {
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
            if ($e->getMessage() !== 'likay-seats-already-booked') {
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

        try { broadcast(new LikaySeatStatusUpdated(bookedKeys: $seatKeys)); } catch (\Throwable) {}

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
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'LIKE', $like)
                  ->orWhere('last_name', 'LIKE', $like)
                  ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $like)
                  ->orWhere('phone', 'LIKE', $like);
            });
        }

        $bookings = $query->get();

        return view('likay-bookings', compact('bookings', 'user', 'search'));
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

        $booking = LikayBooking::with('seats')->findOrFail($id);

        $freedKeys = $booking->seats->pluck('seat_key')->all();

        DB::transaction(function () use ($booking) {
            LikaySeat::where('booking_id', $booking->id)
                ->update(['is_booked' => false, 'booked_at' => null, 'booking_id' => null]);

            if ($booking->slip_path) {
                Storage::disk('public')->delete($booking->slip_path);
            }

            $booking->delete();
        });

        try { broadcast(new LikaySeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}

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

        try { broadcast(new LikaySeatStatusUpdated(deselectingKeys: $data['seat_keys'])); } catch (\Throwable) {}

        return response()->json(['success' => true]);
    }

    // ── Update Prices (manager only) ──────────────────────────────

    public function updatePrices(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $user = $this->currentUser();
        if ($user->role !== User::ROLE_MANAGER) {
            return response()->json(['success' => false, 'error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'vvip'   => 'required|integer|min:0',
            'box'    => 'required|integer|min:0',
            'yellow' => 'required|integer|min:0',
            'blue'   => 'required|integer|min:0',
            'pink'   => 'required|integer|min:0',
            'green'  => 'required|integer|min:0',
            'purple' => 'required|integer|min:0',
        ]);

        foreach ($data as $zone => $price) {
            DB::table('likay_zone_prices')
                ->where('zone', $zone)
                ->update(['price' => $price, 'updated_at' => now()]);
        }

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

        $freedKeys = LikaySeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        // Collect slip paths INSIDE the transaction so concurrent bookings that
        // commit just before the delete are included and their slips are cleaned up.
        $slipPaths = [];

        DB::transaction(function () use (&$slipPaths) {
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
            Storage::disk('public')->delete($path);
        }

        if (!empty($freedKeys)) {
            try { broadcast(new LikaySeatStatusUpdated(freedKeys: $freedKeys)); } catch (\Throwable) {}
        }

        return response()->json(['success' => true]);
    }

    // ── Export Excel ──────────────────────────────────────────────

    public function exportBookings(): \Symfony\Component\HttpFoundation\BinaryFileResponse|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $filename = 'likay-bookings-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new LikayBookingsExport(), $filename);
    }
}
