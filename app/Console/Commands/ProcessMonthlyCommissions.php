<?php

namespace App\Console\Commands;

use App\Services\CommissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessMonthlyCommissions extends Command
{
    protected $signature = 'commissions:process-monthly {--period= : Year-month in Y-m format (default: previous month)}';

    protected $description = 'Evaluate 3:1 ratio rule and approve/reject pending commissions for a given month';

    public function handle(CommissionService $service): int
    {
        $period = $this->option('period') ?? Carbon::now()->subMonth()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $period)) {
            $this->error("Invalid period format: {$period}. Use Y-m (e.g. 2026-05)");

            return self::FAILURE;
        }

        $this->info("Processing commissions for period: {$period}");

        $service->processMonthlyCommissions($period);

        $this->info('Done.');

        return self::SUCCESS;
    }
}
