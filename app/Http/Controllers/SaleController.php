<?php

namespace App\Http\Controllers;

use App\Models\SaleKycDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SaleController extends Controller
{
    // ─── Auth ──────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        if (session('sale_authenticated')) {
            return redirect()->route('sale.dashboard');
        }

        return view('sale.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])
            ->where('role', User::ROLE_SALE)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }

        session([
            'sale_authenticated' => true,
            'sale_user_id'       => $user->id,
        ]);

        return redirect()->route('sale.dashboard');
    }

    public function logout()
    {
        session()->forget(['sale_authenticated', 'sale_user_id']);

        return redirect()->route('sale.login')->with('success', 'ออกจากระบบแล้ว');
    }

    // ─── Registration ──────────────────────────────────────────────────────────

    public function showRegister(Request $request)
    {
        $referrerCode = $request->query('ref');

        return view('sale.register', compact('referrerCode'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'email'              => 'required|email|unique:users,email',
            'password'           => ['required', 'confirmed', Password::min(8)],
            'phone'              => 'required|string|max:20',
            'national_id'        => 'required|string|size:13|unique:users,national_id',
            'bank_name'          => 'required|string|max:100',
            'bank_account_number'=> 'required|string|max:30',
            'bank_account_name'  => 'required|string|max:100',
            'ref_code'           => 'nullable|string|exists:users,referral_code',
            'id_card_file'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_book_file'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'national_id.size'   => 'เลขบัตรประชาชนต้องมี 13 หลัก',
            'national_id.unique' => 'เลขบัตรประชาชนนี้มีผู้ใช้งานแล้ว',
            'email.unique'       => 'อีเมลนี้มีผู้ใช้งานแล้ว',
        ]);

        $parentId = null;
        if (! empty($data['ref_code'])) {
            $parent = User::where('referral_code', $data['ref_code'])
                ->where('role', User::ROLE_SALE)
                ->where('sale_status', User::SALE_STATUS_APPROVED)
                ->first();
            $parentId = $parent?->id;
        }

        $user = User::create([
            'name'                => $data['name'],
            'email'               => $data['email'],
            'password'            => Hash::make($data['password']),
            'role'                => User::ROLE_SALE,
            'phone'               => $data['phone'],
            'national_id'         => $data['national_id'],
            'bank_name'           => $data['bank_name'],
            'bank_account_number' => $data['bank_account_number'],
            'bank_account_name'   => $data['bank_account_name'],
            'parent_id'           => $parentId,
            'referral_code'       => User::generateReferralCode(),
            'sale_status'         => User::SALE_STATUS_PENDING,
            'is_active'           => false,
        ]);

        $this->storeKycFile($request, $user->id, 'id_card_file',   SaleKycDocument::TYPE_NATIONAL_ID);
        $this->storeKycFile($request, $user->id, 'bank_book_file',  SaleKycDocument::TYPE_BANK_BOOK);

        return redirect()->route('sale.pending')->with('success', 'สมัครสำเร็จ! รอการอนุมัติจากทีมงาน');
    }

    public function pending()
    {
        $userId = session('sale_user_id');
        $user   = $userId ? User::with('kycDocuments')->find((int) $userId) : null;

        return view('sale.pending', compact('user'));
    }

    // ─── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('sale_user');

        if ($user->sale_status !== User::SALE_STATUS_APPROVED) {
            return redirect()->route('sale.pending');
        }

        $month  = $request->query('month', now()->format('Y-m'));
        $period = $month;

        $commissions = $user->commissions()
            ->where('period', $period)
            ->with('order')
            ->latest()
            ->get();

        $directSales   = $commissions->where('tier_level', 1);
        $overrideSales = $commissions->whereIn('tier_level', [2, 3]);

        $summary = [
            'total_orders'    => $directSales->count(),
            'direct_amount'   => $directSales->where('status', '!=', 'rejected')->sum('calculated_amount'),
            'override_amount' => $overrideSales->where('status', '!=', 'rejected')->sum('calculated_amount'),
            'pending_amount'  => $commissions->where('status', 'pending')->sum('calculated_amount'),
            'approved_amount' => $commissions->where('status', 'approved')->sum('calculated_amount'),
        ];

        $downlineLevel1 = $user->children()->where('sale_status', User::SALE_STATUS_APPROVED)->count();
        $downlineLevel2 = User::whereIn('parent_id', $user->children()->pluck('id'))
            ->where('sale_status', User::SALE_STATUS_APPROVED)
            ->count();

        $months = $this->availableMonths();

        return view('sale.dashboard', compact(
            'user', 'commissions', 'summary',
            'downlineLevel1', 'downlineLevel2',
            'period', 'months'
        ));
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function storeKycFile(Request $request, int $userId, string $inputName, string $docType): void
    {
        if (! $request->hasFile($inputName)) {
            return;
        }

        $file = $request->file($inputName);
        $path = $file->store("kyc/{$userId}", 'local');

        SaleKycDocument::create([
            'user_id'       => $userId,
            'document_type' => $docType,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'status'        => SaleKycDocument::STATUS_PENDING,
        ]);
    }

    private function availableMonths(): array
    {
        $months = [];
        $start  = now()->subMonths(11)->startOfMonth();

        for ($i = 0; $i <= 11; $i++) {
            $m          = $start->copy()->addMonths($i);
            $months[$m->format('Y-m')] = $m->locale('th')->isoFormat('MMMM YYYY');
        }

        return array_reverse($months, true);
    }
}
