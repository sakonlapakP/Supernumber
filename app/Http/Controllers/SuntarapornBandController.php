<?php

namespace App\Http\Controllers;

use App\Events\SeatStatusUpdated;
use App\Models\SuntarapornBooking;
use App\Models\SuntarapornSeat;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SuntarapornBandController extends Controller
{
    private const ALLOWED_ROLES = [User::ROLE_SUNTARAPORN, User::ROLE_MANAGER];
    private const SESSION_KEY   = 'suntaraporn_user_id';

    // ── Auth helpers ─────────────────────────────────────────────

    private function currentUser(): ?User
    {
        $id = session(self::SESSION_KEY);
        return $id ? User::find($id) : null;
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
        if ($this->currentUser()) {
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

        session([self::SESSION_KEY => $user->id]);

        return redirect()->route('suntaraporn.index');
    }

    public function doLogout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);
        return redirect()->route('suntaraporn.login');
    }

    // ── Public View (no auth required) ───────────────────────────

    public function publicView(): View
    {
        $bookedSeats = SuntarapornSeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $prices = DB::table('suntaraporn_zone_prices')
            ->pluck('price', 'zone')
            ->all();

        return view('suntaraporn-public', compact('bookedSeats', 'prices'));
    }

    // ── Main Page ─────────────────────────────────────────────────

    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $bookedSeats = SuntarapornSeat::where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        $prices = DB::table('suntaraporn_zone_prices')
            ->pluck('price', 'zone')
            ->all();

        return view('suntaraporn-band', compact('bookedSeats', 'prices', 'user'));
    }

    // ── Book Seat(s) ──────────────────────────────────────────────

    public function bookSeat(Request $request): JsonResponse
    {
        if ($this->guardRedirect()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'seat_keys'   => 'required|array|min:1',
            'seat_keys.*' => 'required|string|max:30',
            'zones'       => 'required|array|min:1',
            'zones.*'     => 'required|string|max:20',
            'first_name'  => 'required|string|max:100',
            'last_name'   => 'required|string|max:100',
            'phone'       => 'required|string|max:20',
            'slip'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // booker = admin ที่ login อยู่
        $data['booker_name'] = $this->currentUser()->name;

        // Check none of the seats are already booked
        $alreadyBooked = SuntarapornSeat::whereIn('seat_key', $data['seat_keys'])
            ->where('is_booked', true)
            ->pluck('seat_key')
            ->all();

        if (!empty($alreadyBooked)) {
            return response()->json([
                'success' => false,
                'error'   => 'ที่นั่ง ' . implode(', ', $alreadyBooked) . ' ถูกจองไปแล้ว',
            ], 409);
        }

        // Calculate total price
        $prices    = DB::table('suntaraporn_zone_prices')->pluck('price', 'zone')->all();
        $totalPrice = 0;
        foreach ($data['zones'] as $zone) {
            $totalPrice += $prices[$zone] ?? 0;
        }

        // Handle slip upload
        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store('suntaraporn-slips', 'public');
        }

        DB::transaction(function () use ($data, $slipPath, $totalPrice) {
            $booking = SuntarapornBooking::create([
                'first_name'  => $data['first_name'],
                'last_name'   => $data['last_name'],
                'phone'       => $data['phone'],
                'booker_name' => $data['booker_name'],
                'slip_path'   => $slipPath,
                'total_price' => $totalPrice,
            ]);

            foreach ($data['seat_keys'] as $key) {
                SuntarapornSeat::updateOrCreate(
                    ['seat_key' => $key],
                    [
                        'is_booked'  => true,
                        'booked_at'  => now(),
                        'booking_id' => $booking->id,
                    ]
                );
            }
        });

        // Broadcast real-time update ให้ทุก client ที่เปิดอยู่
        broadcast(new SeatStatusUpdated(bookedKeys: $data['seat_keys']));

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

    public function bookingList(): View|RedirectResponse
    {
        if ($redirect = $this->guardRedirect()) return $redirect;

        $user = $this->currentUser();

        $bookings = SuntarapornBooking::with('seats')
            ->orderByDesc('created_at')
            ->get();

        return view('suntaraporn-bookings', compact('bookings', 'user'));
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

        // Broadcast real-time — ที่นั่งกลับมาว่าง
        broadcast(new SeatStatusUpdated(freedKeys: $freedKeys));

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
            broadcast(new SeatStatusUpdated(selectingKeys: $selectingKeys));
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

        broadcast(new SeatStatusUpdated(deselectingKeys: $data['seat_keys']));

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
            'vip'    => 'required|integer|min:0',
            'box_b'  => 'required|integer|min:0',
            'yellow' => 'required|integer|min:0',
            'blue'   => 'required|integer|min:0',
            'pink'   => 'required|integer|min:0',
            'green'  => 'required|integer|min:0',
            'purple' => 'required|integer|min:0',
        ]);

        foreach ($data as $zone => $price) {
            DB::table('suntaraporn_zone_prices')
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

        SuntarapornSeat::query()->update([
            'is_booked'  => false,
            'booked_at'  => null,
            'booking_id' => null,
        ]);

        return response()->json(['success' => true]);
    }
}
