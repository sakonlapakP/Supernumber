<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suntaraporn_row_zones', function (Blueprint $table) {
            $table->id();
            $table->string('row_key', 20)->unique();
            $table->unsignedBigInteger('zone_id');
            $table->foreign('zone_id')->references('id')->on('suntaraporn_zones')->onDelete('restrict');
            $table->timestamps();
        });

        $now = now();

        // Build slug → id map
        $zoneIds = DB::table('suntaraporn_zones')->pluck('id', 'slug')->all();

        // Suntaraporn row-to-zone mapping
        // Note: BOXF seats are individually assigned 'blue' in the SeatMap service,
        // but the BOX group key is 'box'. We store the BOX group zone as 'box'.
        // Individual seat overrides (BOXF seats being 'blue') are in the SeatMap service rows() logic.
        $rowAssignments = [
            'V'    => 'vip',
            'W'    => 'vip',
            'U'    => 'yellow',
            'T'    => 'yellow',
            'S'    => 'yellow',
            'R'    => 'blue',
            'Q'    => 'blue',
            'P'    => 'blue',
            'N'    => 'pink',
            'M'    => 'pink',
            'L'    => 'pink',
            'K'    => 'pink',
            'J'    => 'green',
            'H'    => 'green',
            'G'    => 'green',
            'F'    => 'green',
            'E'    => 'purple',
            'D'    => 'purple',
            'C'    => 'purple',
            'B'    => 'purple',
            'A'    => 'purple',
            'BOXA' => 'green',
            'BOXB' => 'box',
            'BOXC' => 'box',
            'BOXD' => 'green',
            'BOXE' => 'pink',
            'BOXF' => 'box',
        ];

        foreach ($rowAssignments as $rowKey => $zoneSlug) {
            DB::table('suntaraporn_row_zones')->insert([
                'row_key'    => $rowKey,
                'zone_id'    => $zoneIds[$zoneSlug],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suntaraporn_row_zones');
    }
};
