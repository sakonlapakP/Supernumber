<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LikayZone extends Model
{
    protected $table = 'likay_zones';
    protected $fillable = ['slug', 'label', 'color', 'price', 'sort_order'];
    protected $casts = ['price' => 'integer', 'sort_order' => 'integer'];

    // Compute text color from bg brightness
    public function getTextColorAttribute(): string
    {
        $hex = ltrim($this->color, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.6 ? '#333333' : '#ffffff';
    }

    // Compute border color (darken bg by 15%)
    public function getBorderColorAttribute(): string
    {
        $hex = ltrim($this->color, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 38);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 38);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 38);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
