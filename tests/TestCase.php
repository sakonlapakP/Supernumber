<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 🛡️ ระบบป้องกันดาต้าเบสหลัก (Safety Guard)
        // ตรวจสอบว่าถ้าเป็นการรันเทส (APP_ENV=testing) ต้องไม่ใช้ MySQL ตัวหลักของเครื่อง
        // เพื่อป้องกันกรณีที่ RefreshDatabase ไปสั่งล้างตาราง (migrate:fresh) ในฐานข้อมูลที่ใช้งานจริง
        if (config('database.default') === 'mysql' && app()->environment('testing')) {
            throw new \Exception('❌ [Safety Guard] ตรวจพบว่าคุณกำลังรันเทสบน Database หลัก (MySQL)! ระบบหยุดทำงานเพื่อป้องกันข้อมูลหาย กรุณาเช็คการตั้งค่าใน phpunit.xml หรือ .env');
        }

        // ป้องกันการส่ง HTTP Request ออกไปข้างนอกจริงๆ ในระหว่างรันเทสต์
        // ถ้ามีเทสต์ตัวไหนลืมใช้ Http::fake() แล้วพยายามส่งข้อมูลออกไป เทสต์จะพังทันที (Exception)
        // เพื่อความปลอดภัย 100% ตามที่ผู้ใช้ต้องการ
        Http::preventStrayRequests();
    }
}
