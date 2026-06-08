<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SeatStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $showDate       = '',
        public readonly array $bookedKeys      = [],
        public readonly array $freedKeys       = [],
        public readonly array $selectingKeys   = [],
        public readonly array $deselectingKeys = [],
        public readonly array $sponsorKeys     = [],
    ) {}

    public function broadcastOn(): Channel
    {
        // แยก channel ตามรอบการแสดง เพื่อไม่ให้สถานะของวันหนึ่งไปอัปเดตอีกวัน
        return new Channel('suntaraporn-concert-' . $this->showDate);
    }

    public function broadcastAs(): string
    {
        return 'seat-status-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'booked_keys'      => $this->bookedKeys,
            'freed_keys'       => $this->freedKeys,
            'selecting_keys'   => $this->selectingKeys,
            'deselecting_keys' => $this->deselectingKeys,
            'sponsor_keys'     => $this->sponsorKeys,
        ];
    }
}
