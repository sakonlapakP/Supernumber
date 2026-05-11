<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0811200009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0811300008',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 100,
            'status' => PhoneNumber::STATUS_ACTIVE,
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
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0890300109',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo B',
            'sale_price' => 110,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0880300009',
            'network_code' => 'true_dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 90,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->get('/numbers?prefix=089&p5=3&p10=9&plan=Promo%20A%20100');

        $response->assertOk();
        $response->assertViewHas('positionPattern', '089_3____9');
        $response->assertViewHas('numbers', function ($numbers) {
            return $numbers->getCollection()->pluck('phone_number')->all() === ['0890300009'];
        });
    }

    public function test_it_lists_distinct_plan_labels_from_database(): void
    {
        PhoneNumber::create([
            'phone_number' => '0810000001',
            'network_code' => 'true_dtac',
            'plan_name' => 'True Super Value',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0810000002',
            'network_code' => 'true_dtac',
            'plan_name' => 'True Super Value',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0810000003',
            'network_code' => 'true_dtac',
            'plan_name' => 'True Super Value',
            'sale_price' => 1199,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->get('/numbers');

        $response->assertOk();
        $response->assertViewHas('plans', function ($plans) {
            return $plans->values()->all() === [
                ['value' => 'True Super Value 699', 'label' => 'True Super Value 699'],
                ['value' => 'True Super Value 1199', 'label' => 'True Super Value 1199'],
            ];
        });
        $response->assertSee('True Super Value 699');
    }

    public function test_catalog_includes_true_dtac_and_ais_networks(): void
    {
        PhoneNumber::create([
            'phone_number' => '0810000001',
            'network_code' => 'true',
            'plan_name' => 'Promo A',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0820000002',
            'network_code' => 'dtac',
            'plan_name' => 'Promo A',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        PhoneNumber::create([
            'phone_number' => '0830000003',
            'network_code' => 'ais',
            'plan_name' => 'Promo A',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->get('/numbers');

        $response->assertOk();
        $response->assertViewHas('numbers', function ($numbers) {
            return $numbers->getCollection()->pluck('phone_number')->all() === [
                '0810000001',
                '0820000002',
                '0830000003',
            ];
        });
    }

    public function test_catalog_treats_legacy_blank_service_type_as_postpaid(): void
    {
        DB::table('phone_numbers')->insert([
            [
                'phone_number' => '0810000001',
                'network_code' => 'true_dtac',
                'plan_name' => 'True Super Value',
                'sale_price' => 699,
                'status' => PhoneNumber::STATUS_ACTIVE,
                'service_type' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'phone_number' => '0820000002',
                'network_code' => 'true_dtac',
                'plan_name' => 'True Super Value',
                'sale_price' => 1199,
                'status' => PhoneNumber::STATUS_ACTIVE,
                'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'phone_number' => '0830000003',
                'network_code' => 'true_dtac',
                'plan_name' => 'เติมเงิน',
                'sale_price' => 5000,
                'status' => PhoneNumber::STATUS_ACTIVE,
                'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get('/numbers?service_type=postpaid');

        $response->assertOk();
        $response->assertViewHas('numbers', function ($numbers) {
            return $numbers->getCollection()->pluck('phone_number')->all() === [
                '0810000001',
                '0820000002',
            ];
        });
        $response->assertViewHas('plans', function ($plans) {
            return $plans->values()->all() === [
                ['value' => 'True Super Value 699', 'label' => 'True Super Value 699'],
                ['value' => 'True Super Value 1199', 'label' => 'True Super Value 1199'],
            ];
        });
    }

    public function test_book_page_uses_promotion_name_from_database(): void
    {
        PhoneNumber::create([
            'phone_number' => '0644514194',
            'network_code' => 'true_dtac',
            'plan_name' => 'True Super Value',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $response = $this->get('/book?number=0644514194&package=699');

        $response->assertOk();
        $response->assertSee('ชื่อโปร: True Super Value 699');
    }
}
