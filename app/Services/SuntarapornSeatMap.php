<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SuntarapornSeatMap
{
    /**
     * Seat keys and zones mirrored from the Blade seating chart.
     *
     * @return array<string, string>
     */
    public static function seats(): array
    {
        return Cache::remember('suntaraporn_seats_map', 300, function (): array {
            $rowZones = self::rowZones();
            $seats = [];

            foreach (range(1, 16) as $number) {
                $seats['V_' . $number] = $rowZones['V'] ?? 'vip';
            }

            foreach (range(1, 18) as $number) {
                $seats['W_' . $number] = $rowZones['W'] ?? 'vip';
            }

            $rows = self::rows($rowZones);

            foreach ($rows as [$row, $zone, $left, $centerLeft, $centerRight, $right, $boxLeft, $boxRight]) {
                foreach (array_merge($left, $centerLeft, $centerRight, $right) as $number) {
                    $seats[$row . '_' . $number] = $zone;
                }

                foreach ([$boxLeft, $boxRight] as $boxSeat) {
                    if ($boxSeat) {
                        $seats[$boxSeat['k']] = $boxSeat['z'];
                    }
                }
            }

            return $seats;
        });
    }

    public static function totalSeats(): int
    {
        return count(self::seats());
    }

    /**
     * @param  array<int, string> $seatKeys
     * @return array<string, string>
     */
    public static function zonesFor(array $seatKeys): array
    {
        $seatMap = self::seats();
        $zones = [];

        foreach ($seatKeys as $key) {
            if (! isset($seatMap[$key])) {
                continue;
            }

            $zones[$key] = $seatMap[$key];
        }

        return $zones;
    }

    public static function flushCache(): void
    {
        Cache::forget('suntaraporn_seats_map');
        Cache::forget('suntaraporn_row_zones');
    }

    /**
     * @return array<string, string>
     */
    private static function rowZones(): array
    {
        return Cache::remember('suntaraporn_row_zones', 300, function (): array {
            return DB::table('suntaraporn_row_zones')
                ->join('suntaraporn_zones', 'suntaraporn_row_zones.zone_id', '=', 'suntaraporn_zones.id')
                ->pluck('suntaraporn_zones.slug', 'suntaraporn_row_zones.row_key')
                ->all();
        });
    }

    /**
     * @param  array<string, string> $rowZones
     * @return array
     */
    private static function rows(array $rowZones): array
    {
        $r = fn (int $start, int $end): array => range($start, $end);
        $rz = fn (string $key): string => $rowZones[$key] ?? 'yellow';
        $bz = fn (string $box): string => $rowZones[$box] ?? 'box';

        // For Suntaraporn, BOXF seats are individually assigned the BOXF zone (which defaults to 'box')
        // and BOXE seats use the BOXE zone. BOXA/BOXD use green zone.
        return [
            ['U', $rz('U'), [],      $r(1, 6),   $r(7, 13),  [],        null,                                null],
            ['T', $rz('T'), [],      $r(1, 11),  $r(12, 23), [],        null,                                null],
            ['S', $rz('S'), [],      $r(1, 10),  $r(11, 21), [],        ['k' => 'BOXC_14', 'n' => 14, 'z' => $bz('BOXC')], ['k' => 'BOXF_15', 'n' => 15, 'z' => $bz('BOXF')]],
            ['R', $rz('R'), [],      $r(1, 10),  $r(11, 21), [],        ['k' => 'BOXC_13', 'n' => 13, 'z' => $bz('BOXC')], ['k' => 'BOXF_16', 'n' => 16, 'z' => $bz('BOXF')]],
            ['Q', $rz('Q'), $r(1, 4), $r(5, 14), $r(15, 24), $r(25, 28), ['k' => 'BOXC_12', 'n' => 12, 'z' => $bz('BOXC')], ['k' => 'BOXF_17', 'n' => 17, 'z' => $bz('BOXF')]],
            ['P', $rz('P'), $r(1, 5), $r(6, 15), $r(16, 24), $r(25, 29), ['k' => 'BOXC_11', 'n' => 11, 'z' => $bz('BOXC')], ['k' => 'BOXF_18', 'n' => 18, 'z' => $bz('BOXF')]],
            ['N', $rz('N'), $r(1, 6), $r(7, 15), $r(16, 24), $r(25, 30), ['k' => 'BOXC_10', 'n' => 10, 'z' => $bz('BOXC')], ['k' => 'BOXF_19', 'n' => 19, 'z' => $bz('BOXF')]],
            ['M', $rz('M'), $r(1, 6), $r(7, 14), $r(15, 23), $r(24, 29), ['k' => 'BOXB_9',  'n' => 9,  'z' => $bz('BOXB')], ['k' => 'BOXE_20', 'n' => 20, 'z' => $bz('BOXE')]],
            ['L', $rz('L'), $r(1, 7), $r(8, 16), $r(17, 24), $r(25, 31), ['k' => 'BOXB_8',  'n' => 8,  'z' => $bz('BOXB')], ['k' => 'BOXE_21', 'n' => 21, 'z' => $bz('BOXE')]],
            ['K', $rz('K'), $r(1, 8), $r(9, 16), $r(17, 24), $r(25, 32), ['k' => 'BOXB_7',  'n' => 7,  'z' => $bz('BOXB')], ['k' => 'BOXE_22', 'n' => 22, 'z' => $bz('BOXE')]],
            ['J', $rz('J'), $r(1, 8), $r(9, 16), $r(17, 24), $r(25, 32), ['k' => 'BOXB_6',  'n' => 6,  'z' => $bz('BOXB')], ['k' => 'BOXE_23', 'n' => 23, 'z' => $bz('BOXE')]],
            ['H', $rz('H'), $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), null,                                null],
            ['G', $rz('G'), $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_5',  'n' => 5,  'z' => $bz('BOXA')], ['k' => 'BOXD_24', 'n' => 24, 'z' => $bz('BOXD')]],
            ['F', $rz('F'), $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_4',  'n' => 4,  'z' => $bz('BOXA')], ['k' => 'BOXD_25', 'n' => 25, 'z' => $bz('BOXD')]],
            ['E', $rz('E'), $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_3',  'n' => 3,  'z' => $bz('BOXA')], ['k' => 'BOXD_26', 'n' => 26, 'z' => $bz('BOXD')]],
            ['D', $rz('D'), $r(1, 7), $r(8, 14), $r(15, 22), $r(23, 29), ['k' => 'BOXA_2',  'n' => 2,  'z' => $bz('BOXA')], ['k' => 'BOXD_27', 'n' => 27, 'z' => $bz('BOXD')]],
            ['C', $rz('C'), $r(1, 7), $r(8, 14), $r(15, 22), $r(23, 29), ['k' => 'BOXA_1',  'n' => 1,  'z' => $bz('BOXA')], ['k' => 'BOXD_28', 'n' => 28, 'z' => $bz('BOXD')]],
            ['B', $rz('B'), $r(1, 6), $r(7, 13), $r(14, 21), $r(22, 27), null,                                null],
            ['A', $rz('A'), $r(1, 5), $r(6, 12), $r(13, 20), $r(21, 25), null,                                null],
        ];
    }
}
