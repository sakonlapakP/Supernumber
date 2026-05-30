<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use App\Traits\UnixTimestampSerializable;

class User extends Authenticatable
{
    use UnixTimestampSerializable;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MANAGER          = 'manager';
    public const ROLE_ADMIN            = 'admin';
    public const ROLE_STAFF            = 'staff';
    public const ROLE_DOCUMENT_OFFICER = 'document_officer';
    public const ROLE_SALE             = 'sale';
    public const ROLE_SUNTARAPORN      = 'suntaraporn';

    public const SALE_STATUS_PENDING  = 'pending';
    public const SALE_STATUS_APPROVED = 'approved';
    public const SALE_STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'is_active',
        'password',
        'phone',
        'national_id',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'parent_id',
        'referral_code',
        'quota_tokens',
        'sale_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }


    public function statusLogs(): HasMany
    {
        return $this->hasMany(PhoneNumberStatusLog::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(SaleKycDocument::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(CustomerOrder::class, 'seller_user_id');
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_MANAGER,
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_DOCUMENT_OFFICER,
            self::ROLE_SUNTARAPORN,
        ];
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->is_active
            && in_array($this->role, self::roleOptions(), true);
    }

    /**
     * Highest priority role (Manager)
     */
    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    /**
     * Specifically Admin role (General Management)
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Basic Read-only role (Staff)
     */
    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    /**
     * Document Officer role (Invoice & Quotation only)
     */
    public function isDocumentOfficer(): bool
    {
        return $this->role === self::ROLE_DOCUMENT_OFFICER;
    }

    /**
     * Check if user is at least Admin level (Admin or Manager)
     */
    public function isAtLeastAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER], true);
    }

    public function isSale(): bool
    {
        return $this->role === self::ROLE_SALE;
    }

    public function isSuntaraporn(): bool
    {
        return $this->role === self::ROLE_SUNTARAPORN;
    }

    public function isSaleApproved(): bool
    {
        return $this->isSale() && $this->sale_status === self::SALE_STATUS_APPROVED;
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get role display label in Thai
     */
    public static function roleLabelMap(): array
    {
        return [
            self::ROLE_MANAGER          => 'ผู้จัดการ (Manager)',
            self::ROLE_ADMIN            => 'แอดมิน (Admin)',
            self::ROLE_STAFF            => 'พนักงาน (Staff)',
            self::ROLE_DOCUMENT_OFFICER => 'เจ้าหน้าที่เอกสาร (Document Officer)',
            self::ROLE_SALE             => 'เซลล์ (Sale)',
            self::ROLE_SUNTARAPORN      => 'สุนทราภรณ์ (Suntaraporn)',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleLabelMap()[$this->role] ?? $this->role;
    }
}
