<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use App\Traits\UnixTimestampSerializable;

class User extends Authenticatable
{
    use UnixTimestampSerializable;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_DOCUMENT_OFFICER = 'document_officer';

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

    public static function roleOptions(): array
    {
        return [
            self::ROLE_MANAGER,
            self::ROLE_ADMIN,
            self::ROLE_STAFF,
            self::ROLE_DOCUMENT_OFFICER,
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

    /**
     * Get role display label in Thai
     */
    public static function roleLabelMap(): array
    {
        return [
            self::ROLE_MANAGER => 'ผู้จัดการ (Manager)',
            self::ROLE_ADMIN => 'แอดมิน (Admin)',
            self::ROLE_STAFF => 'พนักงาน (Staff)',
            self::ROLE_DOCUMENT_OFFICER => 'เจ้าหน้าที่เอกสาร (Document Officer)',
        ];
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleLabelMap()[$this->role] ?? $this->role;
    }
}
