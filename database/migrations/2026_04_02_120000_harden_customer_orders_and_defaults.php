<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORDER_STATUS_SUBMITTED = 'submitted';
    private const ORDER_STATUS_PENDING_REVIEW = 'pending_review';
    private const ORDER_STATUS_REVIEWING = 'reviewing';
    private const ORDER_STATUS_PAID = 'paid';
    private const ORDER_STATUS_COMPLETED = 'completed';
    private const ORDER_STATUS_SOLD = 'sold';
    private const ORDER_STATUS_REJECTED = 'rejected';
    private const ORDER_STATUS_CANCELLED = 'cancelled';
    private const PHONE_STATUS_ACTIVE = 'active';
    private const ROLE_MANAGER = 'manager';
    private const ROLE_STAFF = 'staff';
    private const SERVICE_TYPE_POSTPAID = 'postpaid';
    private const SERVICE_TYPE_PREPAID = 'prepaid';

    public function up(): void
    {
        if (Schema::hasTable('customer_orders') && ! Schema::hasColumn('customer_orders', 'phone_number_id')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->foreignId('phone_number_id')
                    ->nullable()
                    ->after('ordered_number')
                    ->constrained('phone_numbers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('customer_orders')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->index(['phone_number_id', 'created_at'], 'customer_orders_phone_number_created_at_index');
                $table->index(['status', 'created_at'], 'customer_orders_status_created_at_index');
            });
        }

        if (Schema::hasTable('phone_number_status_logs')) {
            Schema::table('phone_number_status_logs', function (Blueprint $table): void {
                $table->index(['phone_number_id', 'created_at'], 'phone_number_status_logs_phone_number_created_at_index');
                $table->index('created_at', 'phone_number_status_logs_created_at_index');
            });
        }

        $this->normalizeExistingPhoneNumbers();
        $this->normalizeExistingUsers();
        $this->backfillCustomerOrderRelationsAndStatuses();
    }

    public function down(): void
    {
        if (Schema::hasTable('phone_number_status_logs')) {
            Schema::table('phone_number_status_logs', function (Blueprint $table): void {
                $table->dropIndex('phone_number_status_logs_phone_number_created_at_index');
                $table->dropIndex('phone_number_status_logs_created_at_index');
            });
        }

        if (Schema::hasTable('customer_orders')) {
            Schema::table('customer_orders', function (Blueprint $table): void {
                $table->dropIndex('customer_orders_phone_number_created_at_index');
                $table->dropIndex('customer_orders_status_created_at_index');
                $table->dropConstrainedForeignId('phone_number_id');
            });
        }
    }

    private function normalizeExistingPhoneNumbers(): void
    {
        if (! Schema::hasTable('phone_numbers')) {
            return;
        }

        DB::table('phone_numbers')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => self::PHONE_STATUS_ACTIVE]);
    }

    private function normalizeExistingUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')
            ->where('role', self::ROLE_STAFF)
            ->update([
                'role' => self::ROLE_MANAGER,
                'updated_at' => Carbon::now(),
            ]);
    }

    private function backfillCustomerOrderRelationsAndStatuses(): void
    {
        if (! Schema::hasTable('customer_orders') || ! Schema::hasTable('phone_numbers')) {
            return;
        }

        $phoneNumbersByValue = DB::table('phone_numbers')
            ->pluck('id', 'phone_number')
            ->all();

        DB::table('customer_orders')
            ->select(['id', 'ordered_number', 'phone_number_id', 'service_type', 'status'])
            ->orderBy('id')
            ->chunkById(1000, function ($orders) use ($phoneNumbersByValue): void {
                foreach ($orders as $order) {
                    $orderedNumber = preg_replace('/\D+/', '', (string) $order->ordered_number) ?? '';
                    $serviceType = $this->normalizeServiceType($order->service_type);
                    $status = $this->normalizeOrderStatus($order->status, $serviceType);
                    $phoneNumberId = $orderedNumber !== '' ? ($phoneNumbersByValue[$orderedNumber] ?? null) : null;

                    $updates = [];

                    if ((int) ($order->phone_number_id ?? 0) !== (int) ($phoneNumberId ?? 0)) {
                        $updates['phone_number_id'] = $phoneNumberId;
                    }

                    if ((string) $order->service_type !== $serviceType) {
                        $updates['service_type'] = $serviceType;
                    }

                    if ((string) $order->status !== $status) {
                        $updates['status'] = $status;
                    }

                    if ($updates !== []) {
                        DB::table('customer_orders')
                            ->where('id', $order->id)
                            ->update($updates);
                    }
                }
            });
    }

    private function normalizeServiceType(mixed $serviceType): string
    {
        return strtolower(trim((string) $serviceType)) === self::SERVICE_TYPE_PREPAID
            ? self::SERVICE_TYPE_PREPAID
            : self::SERVICE_TYPE_POSTPAID;
    }

    private function normalizeOrderStatus(mixed $status, string $serviceType): string
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, [
            self::ORDER_STATUS_SUBMITTED,
            self::ORDER_STATUS_PENDING_REVIEW,
            self::ORDER_STATUS_REVIEWING,
            self::ORDER_STATUS_PAID,
            self::ORDER_STATUS_COMPLETED,
            self::ORDER_STATUS_SOLD,
            self::ORDER_STATUS_REJECTED,
            self::ORDER_STATUS_CANCELLED,
        ], true)
            ? $status
            : ($serviceType === self::SERVICE_TYPE_PREPAID
                ? self::ORDER_STATUS_PENDING_REVIEW
                : self::ORDER_STATUS_SUBMITTED);
    }
};
