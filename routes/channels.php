<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel สำหรับ suntaraporn concert (ไม่ต้อง auth)
Broadcast::channel('suntaraporn-concert', function () {
    return true;
});
