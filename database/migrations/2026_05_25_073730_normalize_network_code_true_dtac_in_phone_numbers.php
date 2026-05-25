<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Normalize legacy 'true' and 'dtac' network codes to 'true_dtac'.
 *
 * The model's normalizeNetworkCode() already maps true/dtac → true_dtac,
 * but the DB rows were never updated, causing supportedNetwork() scope to
 * return 0 results on production (postpaid numbers invisible on site).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('phone_numbers')
            ->whereIn('network_code', ['true', 'dtac'])
            ->update(['network_code' => 'true_dtac']);

        if (DB::getSchemaBuilder()->hasTable('phone_packages')) {
            DB::table('phone_packages')
                ->whereIn('network_code', ['true', 'dtac'])
                ->update(['network_code' => 'true_dtac']);
        }
    }

    public function down(): void
    {
        // Cannot reliably split true_dtac back — intentionally a no-op.
    }
};
