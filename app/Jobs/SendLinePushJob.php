<?php

namespace App\Jobs;

use App\Models\LineNotificationLog;
use App\Services\LineNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendLinePushJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $notificationLogId,
    ) {
        $this->queue = 'notifications';
    }

    public function handle(LineNotifier $lineNotifier): void
    {
        $lineNotifier->deliverLog($this->notificationLogId, $this->attempts());
    }

    public function failed(Throwable $exception): void
    {
        $log = LineNotificationLog::query()->find($this->notificationLogId);

        if (! $log) {
            return;
        }

        $log->forceFill([
            'status' => LineNotificationLog::STATUS_FAILED,
            'attempts' => max((int) $log->attempts, (int) $this->attempts()),
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
        ])->save();
    }
}
