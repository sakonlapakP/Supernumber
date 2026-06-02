<?php

namespace App\Exports;

use App\Models\SuntarapornBooking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SuntarapornBookingsExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new class implements FromCollection, WithHeadings, WithTitle, WithStyles {
                public function collection()
                {
                    return SuntarapornBooking::with('seats')
                        ->orderBy('id')
                        ->get()
                        ->values()
                        ->map(fn ($booking, $i) => [
                            $i + 1,
                            $booking->id,
                            $booking->first_name,
                            $booking->last_name,
                            $booking->phone,
                            $booking->seats->pluck('seat_key')->sort()->join(', '),
                            $booking->seats->count(),
                            $booking->total_price,
                            $booking->booker_name,
                            $booking->slip_path ? 'มี' : 'ไม่มี',
                            $booking->created_at->format('d/m/Y H:i'),
                        ]);
                }

                public function headings(): array
                {
                    return [
                        'ลำดับ', 'รหัสการจอง', 'ชื่อ', 'นามสกุล', 'เบอร์โทร',
                        'ที่นั่ง', 'จำนวนที่นั่ง', 'ราคารวม (฿)', 'ผู้บันทึก',
                        'มีสลิป', 'วันที่จอง',
                    ];
                }

                public function title(): string { return 'รายการจอง'; }

                public function styles(Worksheet $sheet): array
                {
                    return [1 => ['font' => ['bold' => true, 'size' => 12]]];
                }
            },
            new SuntarapornZoneSheet(),
        ];
    }
}
