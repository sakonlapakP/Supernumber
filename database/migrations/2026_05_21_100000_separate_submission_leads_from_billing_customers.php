<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billing_customers') || ! Schema::hasTable('customer_submissions')) {
            return;
        }

        Schema::create('archived_lead_billing_customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('original_customer_id')->unique();
            $table->string('company_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('tax_id', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('payment_term')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
        });

        Schema::create('archived_lead_billing_customer_submission_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('original_customer_id');
            $table->unsignedBigInteger('customer_submission_id')->unique();
            $table->timestamp('archived_at')->nullable();

            $table->index('original_customer_id', 'archived_lead_customer_id_index');
        });

        $now = now();
        $lastId = 0;

        while (true) {
            $ids = $this->leadCustomerCandidateQuery()
                ->where('b.id', '>', $lastId)
                ->orderBy('b.id')
                ->limit(500)
                ->pluck('b.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($ids === []) {
                break;
            }

            $lastId = max($ids);

            $customers = DB::table('billing_customers')
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get();

            $archiveRows = $customers->map(fn ($customer) => [
                'original_customer_id' => $customer->id,
                'company_name' => $customer->company_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_term' => $customer->payment_term,
                'notes' => $customer->notes,
                'is_active' => (bool) $customer->is_active,
                'original_created_at' => $customer->created_at,
                'original_updated_at' => $customer->updated_at,
                'archived_at' => $now,
            ])->all();

            if ($archiveRows !== []) {
                DB::table('archived_lead_billing_customers')->insertOrIgnore($archiveRows);
            }

            $links = DB::table('customer_submissions')
                ->whereIn('customer_id', $ids)
                ->get(['id', 'customer_id'])
                ->map(fn ($submission) => [
                    'original_customer_id' => $submission->customer_id,
                    'customer_submission_id' => $submission->id,
                    'archived_at' => $now,
                ])
                ->all();

            if ($links !== []) {
                DB::table('archived_lead_billing_customer_submission_links')->insertOrIgnore($links);
            }

            DB::table('customer_submissions')
                ->whereIn('customer_id', $ids)
                ->update(['customer_id' => null]);

            DB::table('billing_customers')
                ->whereIn('id', $ids)
                ->delete();
        }
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('billing_customers')
            || ! Schema::hasTable('customer_submissions')
            || ! Schema::hasTable('archived_lead_billing_customers')
        ) {
            return;
        }

        $archives = DB::table('archived_lead_billing_customers')
            ->orderBy('original_customer_id')
            ->get();

        foreach ($archives->chunk(500) as $chunk) {
            $rows = $chunk->map(fn ($customer) => [
                'id' => $customer->original_customer_id,
                'company_name' => $customer->company_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'tax_id' => $customer->tax_id,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'payment_term' => $customer->payment_term,
                'notes' => $customer->notes,
                'is_active' => (bool) $customer->is_active,
                'created_at' => $customer->original_created_at,
                'updated_at' => $customer->original_updated_at,
            ])->all();

            DB::table('billing_customers')->insertOrIgnore($rows);
        }

        if (Schema::hasTable('archived_lead_billing_customer_submission_links')) {
            DB::table('archived_lead_billing_customer_submission_links')
                ->orderBy('id')
                ->chunk(500, function ($links): void {
                    foreach ($links as $link) {
                        DB::table('customer_submissions')
                            ->where('id', $link->customer_submission_id)
                            ->whereNull('customer_id')
                            ->update(['customer_id' => $link->original_customer_id]);
                    }
                });

            Schema::dropIfExists('archived_lead_billing_customer_submission_links');
        }

        Schema::dropIfExists('archived_lead_billing_customers');
    }

    private function leadCustomerCandidateQuery()
    {
        return DB::table('billing_customers as b')
            ->join('customer_submissions as s', 's.customer_id', '=', 'b.id')
            ->leftJoin('sales_documents as d', 'd.customer_id', '=', 'b.id')
            ->whereNull('d.id')
            ->where(function ($query): void {
                $query->whereNull('b.company_name')->orWhere('b.company_name', '');
            })
            ->where(function ($query): void {
                $query->whereNull('b.tax_id')->orWhere('b.tax_id', '');
            })
            ->where(function ($query): void {
                $query->whereNull('b.address')->orWhere('b.address', '');
            })
            ->where(function ($query): void {
                $query->whereNull('b.payment_term')->orWhere('b.payment_term', '');
            })
            ->where(function ($query): void {
                $query->whereNull('b.notes')->orWhere('b.notes', '');
            })
            ->select('b.id')
            ->distinct();
    }
};
