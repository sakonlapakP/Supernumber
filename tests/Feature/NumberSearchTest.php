<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_numbers_by_specific_positions(): void
    {
        PhoneNumber::create([
            'phone_number' => '0811300009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => 'available',
        ]);

        PhoneNumber::create([
            'phone_number' => '0811200009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => 'available',
        ]);

        PhoneNumber::create([
            'phone_number' => '0811300008',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => 'available',
        ]);

        PhoneNumber::create([
            'phone_number' => '0911300009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => 'sold',
        ]);

        $response = $this->get('/numbers?p5=3&p10=9');

        $response->assertOk();
        $response->assertViewHas('positionPattern', '____3____9');
        $response->assertViewHas('numbers', function ($numbers) {
            return $numbers->getCollection()->pluck('phone_number')->all() === ['0811300009'];
        });
    }

    public function test_it_combines_prefix_and_plan_filters(): void
    {
        PhoneNumber::create([
            'phone_number' => '0890300009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => 'available',
        ]);

        PhoneNumber::create([
            'phone_number' => '0890300109',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo B',
            'sale_price' => 110,
            'status' => 'available',
        ]);

        PhoneNumber::create([
            'phone_number' => '0880300009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 90,
            'status' => 'available',
        ]);

        $response = $this->get('/numbers?prefix=089&p5=3&p10=9&plan=Promo%20A');

        $response->assertOk();
        $response->assertViewHas('positionPattern', '089_3____9');
        $response->assertViewHas('numbers', function ($numbers) {
            return $numbers->getCollection()->pluck('phone_number')->all() === ['0890300009'];
        });
    }
}
