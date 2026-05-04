<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoEstimateTest extends TestCase
{
    /** @test */
    public function estimate_page_has_correct_seo_elements()
    {
        $response = $this->get('/estimate');

        $response->assertStatus(200);
        
        // 1. Check Title
        $response->assertSee('เลือกเบอร์มงคลให้เหมาะกับคุณ วิเคราะห์ตามวันเกิดและอาชีพ | Supernumber');
        
        // 2. Check Meta Description
        $response->assertSee('ระบบช่วยเลือกเบอร์มงคลที่ใช่สำหรับคุณโดยเฉพาะ วิเคราะห์ตามข้อมูลส่วนบุคคล วันเกิด อาชีพ และเป้าหมายชีวิต');
        
        // 3. Check H1
        $response->assertSee('<h1 id="estimate-title">เลือกเบอร์ให้เหมาะกับคุณ</h1>', false);
        
        // 4. Check Kicker
        $response->assertSee('ระบบวิเคราะห์และแนะนำเบอร์มงคลอัจฉริยะ');
        
        // 5. Check JSON-LD Breadcrumb
        $response->assertSee('"@type": "BreadcrumbList"', false);
        $response->assertSee('"name": "เลือกเบอร์ให้เหมาะกับคุณ"', false);
        
        // 6. Check SEO Content Keywords
        $response->assertSee('ทำไมต้องใช้ระบบเลือกเบอร์มงคลอัจฉริยะ?');
        $response->assertSee('วิเคราะห์ตามวันเกิด');
        $response->assertSee('คัดตามลักษณะงาน');
        $response->assertSee('ตอบโจทย์เป้าหมายชีวิต');
    }
}
