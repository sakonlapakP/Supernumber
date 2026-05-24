<?php

namespace App\Console\Commands;

use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnosePostpaidNumbersCommand extends Command
{
    protected $signature = 'numbers:diagnose-postpaid {--fix : Fix missing initial_payment_price from phone_packages}';

    protected $description = 'Diagnose why postpaid numbers are not showing, optionally fix them';

    public function handle(): int
    {
        $this->info('=== Postpaid Numbers Diagnosis ===');
        $this->newLine();

        // Check phone_packages table
        if (! Schema::hasTable('phone_packages')) {
            $this->error('phone_packages table does NOT exist — run: php artisan migrate');
            return 1;
        }
        $this->line('✓ phone_packages table exists');

        // Check columns
        foreach (['initial_payment_price', 'package_id', 'service_type', 'status'] as $col) {
            if (! Schema::hasColumn('phone_numbers', $col)) {
                $this->error("phone_numbers.{$col} column missing — run: php artisan migrate");
                return 1;
            }
        }
        $this->line('✓ All required columns exist');
        $this->newLine();

        // Counts
        $total      = PhoneNumber::postpaid()->count();
        $active     = PhoneNumber::postpaid()->where('status', PhoneNumber::STATUS_ACTIVE)->count();
        $inactive   = PhoneNumber::postpaid()->where('status', PhoneNumber::STATUS_UNACTIVE)->count();
        $available  = PhoneNumber::postpaid()->available()->count();
        $supported  = PhoneNumber::postpaid()->available()->supportedNetwork()->count();

        $noPrice = PhoneNumber::postpaid()
            ->where('status', PhoneNumber::STATUS_ACTIVE)
            ->whereNull('initial_payment_price')
            ->whereNull('sale_price')
            ->count();

        $noPackage = PhoneNumber::postpaid()
            ->where('status', PhoneNumber::STATUS_ACTIVE)
            ->whereNull('package_id')
            ->count();

        $packagesCount = DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total postpaid numbers',         $total],
                ['  ↳ Active',                     $active],
                ['  ↳ Inactive (retired/sold)',     $inactive],
                ['Pass available() scope',          $available],
                ['Pass supportedNetwork() scope',   $supported],
                ['Active but NO price (hidden)',     $noPrice],
                ['Active but no package_id',        $noPackage],
                ['phone_packages (postpaid)',        $packagesCount],
            ]
        );

        $this->newLine();

        if ($supported > 0) {
            $this->info("✓ {$supported} postpaid numbers should be visible on homepage.");
            $this->line('  If still not showing, try: php artisan cache:clear');
            return 0;
        }

        // Drill down
        if ($packagesCount === 0) {
            $this->error('phone_packages has no postpaid rows — data migration may have failed.');
            $this->line('  Fix: re-run migration or import packages manually.');
            return 1;
        }

        if ($noPrice > 0) {
            $this->warn("{$noPrice} active postpaid numbers have no price → hidden from site.");

            if ($this->option('fix')) {
                $this->fixMissingPrices();
            } else {
                $this->line('  Run with --fix to repair: php artisan numbers:diagnose-postpaid --fix');
            }
        }

        if ($active === 0) {
            $this->error('All postpaid numbers are inactive.');
            $this->line('  Check who set them inactive (import, migration, or manual update).');
        }

        return 0;
    }

    private function fixMissingPrices(): void
    {
        $this->info('Fixing missing initial_payment_price from phone_packages...');

        $updated = 0;

        PhoneNumber::postpaid()
            ->where('status', PhoneNumber::STATUS_ACTIVE)
            ->whereNull('initial_payment_price')
            ->whereNotNull('package_id')
            ->with('package')
            ->chunkById(200, function ($numbers) use (&$updated) {
                foreach ($numbers as $number) {
                    $package = $number->package;
                    if (! $package || $package->monthly_price === null) {
                        continue;
                    }

                    $initialPayment = PhoneNumber::postpaidInitialPaymentForMonthlyPrice(
                        (int) $package->monthly_price
                    );

                    if ($initialPayment === null) {
                        continue;
                    }

                    $number->update([
                        'initial_payment_price' => $initialPayment,
                        'sale_price'            => $package->monthly_price,
                    ]);
                    $updated++;
                }
            });

        $this->line("  → Fixed {$updated} numbers.");

        $nowAvailable = PhoneNumber::postpaid()->available()->supportedNetwork()->count();
        $this->info("  ✓ Postpaid numbers now available on site: {$nowAvailable}");
    }
}
