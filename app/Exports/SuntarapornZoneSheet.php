<?php

namespace App\Exports;

use App\Models\SuntarapornSeat;
use App\Models\SuntarapornZone;
use App\Services\SuntarapornSeatMap;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuntarapornZoneSheet implements FromArray, WithHeadings, WithTitle, WithStyles
{
    private const ZONE_LABELS = [
        'vip'    => 'VIP',
        'yellow' => 'เหลือง',
        'blue'   => 'ฟ้า',
        'pink'   => 'ชมพู',
        'green'  => 'เขียว',
        'purple' => 'ม่วง',
        'box'    => 'BOX',
    ];

    public function array(): array
    {
        $prices      = SuntarapornZone::pluck('price', 'slug')->all();
        $allSeats    = SuntarapornSeatMap::seats();

        // count total seats per zone
        $totalPerZone = [];
        foreach ($allSeats as $zone) {
            $totalPerZone[$zone] = ($totalPerZone[$zone] ?? 0) + 1;
        }

        // count booked seats per zone
        $bookedKeys = SuntarapornSeat::where('is_booked', true)->pluck('seat_key')->all();
        $bookedPerZone = [];
        foreach ($bookedKeys as $key) {
            if (isset($allSeats[$key])) {
                $z = $allSeats[$key];
                $bookedPerZone[$z] = ($bookedPerZone[$z] ?? 0) + 1;
            }
        }

        $rows = [];
        foreach (self::ZONE_LABELS as $zone => $label) {
            $total  = $totalPerZone[$zone] ?? 0;
            $booked = $bookedPerZone[$zone] ?? 0;
            $price  = $prices[$zone] ?? 0;
            $rows[] = [
                $label,
                $total,
                $booked,
                $total - $booked,
                $price,
                $booked * $price,
            ];
        }

        // totals row
        $rows[] = [
            'รวมทั้งหมด',
            array_sum(array_column($rows, 1)),
            array_sum(array_column($rows, 2)),
            array_sum(array_column($rows, 3)),
            '',
            array_sum(array_column($rows, 5)),
        ];

        return $rows;
    }

    public function headings(): array
    {
        return ['โซน', 'ที่นั่งทั้งหมด', 'จองแล้ว', 'ว่าง', 'ราคา/ที่นั่ง (฿)', 'รายได้รวม (฿)'];
    }

    public function title(): string
    {
        return 'สรุปโซน';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array()) + 1; // +1 for heading

        return [
            1          => ['font' => ['bold' => true, 'size' => 12]],
            $lastRow   => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'FFF9C4']]],
        ];
    }
}
