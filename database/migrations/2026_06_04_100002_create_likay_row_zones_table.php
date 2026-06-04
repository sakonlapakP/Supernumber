<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likay_row_zones', function (Blueprint $table) {
            $table->id();
            $table->string('row_key', 20)->unique();
            $table->unsignedBigInteger('zone_id');
            $table->foreign('zone_id')->references('id')->on('likay_zones')->onDelete('restrict');
            $table->timestamps();
        });

        $now = now();

        // Build slug → id map
        $zoneIds = DB::table('likay_zones')->pluck('id', 'slug')->all();

        $rowAssignments = [
            'V'    => 'yellow',
            'W'    => 'yellow',
            'U'    => 'yellow',
            'T'    => 'yellow',
            'S'    => 'blue',
            'R'    => 'blue',
            'Q'    => 'blue',
            'P'    => 'blue',
            'N'    => 'pink',
            'M'    => 'pink',
            'L'    => 'pink',
            'K'    => 'pink',
            'J'    => 'pink',
            'H'    => 'green',
            'G'    => 'green',
            'F'    => 'green',
            'E'    => 'green',
            'D'    => 'green',
            'C'    => 'purple',
            'B'    => 'purple',
            'A'    => 'purple',
            'BOXA' => 'box',
            'BOXB' => 'box',
            'BOXC' => 'box',
            'BOXD' => 'box',
            'BOXE' => 'box',
            'BOXF' => 'box',
        ];

        foreach ($rowAssignments as $rowKey => $zoneSlug) {
            DB::table('likay_row_zones')->insert([
                'row_key'    => $rowKey,
                'zone_id'    => $zoneIds[$zoneSlug],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('likay_row_zones');
    }
};
