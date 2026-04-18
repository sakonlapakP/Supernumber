<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluatePhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_evaluate_redirects_home_when_phone_is_not_10_digits(): void
    {
        $response = $this->get('/evaluate?phone=12345');

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('phone');
        $response->assertSessionHasInput('phone', '12345');
    }

    public function test_evaluate_accepts_a_10_digit_phone_after_sanitizing_non_digits(): void
    {
        $response = $this->get('/evaluate?phone=081-234-5678');

        $response->assertOk();
        $response->assertViewHas('phone', '0812345678');
        $response->assertSee('0812345678');
        $response->assertSee('ภาพรวมหมวดที่เบอร์นี้ส่งเสริม');
        $response->assertSee('การสื่อสาร');
        $response->assertSee('ศาสตร์เร้นลับ/ลางสังหรณ์');
        $response->assertDontSee('10/10');
    }

    public function test_evaluate_bad_number_requires_a_10_digit_phone(): void
    {
        $response = $this->get('/evaluateBadNumber?phone=123456789');

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('phone');
        $response->assertSessionHasInput('phone', '123456789');
    }
}
