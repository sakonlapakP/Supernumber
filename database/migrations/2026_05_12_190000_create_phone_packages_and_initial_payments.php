<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{price:int,label:string,data:string,speed:string,voice:string,benefits:string}>
     */
    private array $defaultPackages = [
        ['price' => 499, 'label' => 'พื้นฐาน', 'data' => '50GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '150 นาที', 'benefits' => 'ไม่รวม'],
        ['price' => 599, 'label' => 'เริ่มคุ้ม', 'data' => '60GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '200 นาที', 'benefits' => 'ไม่รวม'],
        ['price' => 699, 'label' => 'แพคเกจแนะนำ', 'data' => '70GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '250 นาที', 'benefits' => 'NOW ENT 12 เดือน'],
        ['price' => 899, 'label' => 'ยอดนิยม', 'data' => '90GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '300 นาที', 'benefits' => 'NOW ENT 12 เดือน'],
        ['price' => 999, 'label' => 'คุ้มค่า+', 'data' => '100GB', 'speed' => '1 Mbps (หลังหมดสปีดเต็ม)', 'voice' => '400 นาที', 'benefits' => 'NOW ENT 12 เดือน'],
        ['price' => 1199, 'label' => 'โปรสปีดสูง', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '600 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 1499, 'label' => 'พรีเมียม', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '900 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 1699, 'label' => 'พรีเมียม+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,100 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 1999, 'label' => 'โปรธุรกิจ', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,500 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 2199, 'label' => 'โปรธุรกิจ+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '1,800 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 2499, 'label' => 'Max', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '2,300 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 2699, 'label' => 'Max+', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '2,700 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 2999, 'label' => 'Ultra', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '3,000 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
        ['price' => 3499, 'label' => 'Ultra Max', 'data' => 'Unlimited', 'speed' => 'ไม่จำกัด', 'voice' => '3,400 นาที', 'benefits' => 'NOW ENT + CyberSafe PRO'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('phone_packages')) {
            Schema::create('phone_packages', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 80)->unique();
                $table->string('service_type', 20)->default(PhoneNumber::SERVICE_TYPE_POSTPAID);
                $table->string('network_code', 20)->default(PhoneNumber::NETWORK_TRUE);
                $table->string('name');
                $table->unsignedInteger('monthly_price')->nullable();
                $table->string('data_quota')->nullable();
                $table->string('speed_after_quota')->nullable();
                $table->string('voice_minutes')->nullable();
                $table->text('benefits')->nullable();
                $table->text('conditions')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['service_type', 'network_code', 'is_active']);
                $table->index('monthly_price');
            });
        }

        Schema::table('phone_numbers', function (Blueprint $table): void {
            if (!Schema::hasColumn('phone_numbers', 'package_id')) {
                $table->foreignId('package_id')
                    ->nullable()
                    ->constrained('phone_packages')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('phone_numbers', 'initial_payment_price')) {
                $table->unsignedInteger('initial_payment_price')->nullable()->index();
            }
        });

        Schema::table('customer_orders', function (Blueprint $table): void {
            if (!Schema::hasColumn('customer_orders', 'package_id')) {
                $table->foreignId('package_id')
                    ->nullable()
                    ->constrained('phone_packages')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_orders', 'package_code')) {
                $table->string('package_code', 80)->nullable();
                $table->index('package_code');
            }
            if (!Schema::hasColumn('customer_orders', 'package_name')) {
                $table->string('package_name')->nullable();
            }
            if (!Schema::hasColumn('customer_orders', 'monthly_price')) {
                $table->unsignedInteger('monthly_price')->nullable();
            }
            if (!Schema::hasColumn('customer_orders', 'initial_payment_price')) {
                $table->unsignedInteger('initial_payment_price')->nullable();
            }
        });

        $this->seedDefaultPackages();
        $this->backfillPhoneNumbers();
        $this->backfillCustomerOrders();
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table): void {
            $table->dropIndex(['package_code']);
            $table->dropConstrainedForeignId('package_id');
            $table->dropColumn(['package_code', 'package_name', 'monthly_price', 'initial_payment_price']);
        });

        Schema::table('phone_numbers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('package_id');
            $table->dropIndex(['initial_payment_price']);
            $table->dropColumn('initial_payment_price');
        });

        Schema::dropIfExists('phone_packages');
    }

    private function seedDefaultPackages(): void
    {
        $now = now();
        $networks = [
            PhoneNumber::NETWORK_TRUE => 'TRUE',
            PhoneNumber::NETWORK_DTAC => 'DTAC',
            PhoneNumber::NETWORK_AIS => 'AIS',
            PhoneNumber::NETWORK_TRUE_DTAC => 'TRUE-DTAC',
        ];
        $conditions = implode("\n", [
            'แพคเกจนี้สำหรับเบอร์ใหม่/ย้ายค่ายที่ร่วมรายการ',
            'ค่าโทรเกินแพคเกจและบริการเสริม คิดตามเงื่อนไขผู้ให้บริการ',
            'สิทธิพิเศษอาจมีการเปลี่ยนแปลงตามเงื่อนไขค่ายมือถือ',
        ]);

        foreach ($networks as $networkCode => $prefix) {
            foreach ($this->defaultPackages as $package) {
                DB::table('phone_packages')->updateOrInsert(
                    ['code' => $prefix . '-SV-' . $package['price']],
                    [
                        'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
                        'network_code' => $networkCode,
                        'name' => PhoneNumber::PACKAGE_NAME . ' ' . $package['price'],
                        'monthly_price' => $package['price'],
                        'data_quota' => $package['data'],
                        'speed_after_quota' => $package['speed'],
                        'voice_minutes' => $package['voice'],
                        'benefits' => $package['benefits'],
                        'conditions' => $conditions,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    private function backfillPhoneNumbers(): void
    {
        $packagesByNetworkAndPrice = DB::table('phone_packages')
            ->where('service_type', PhoneNumber::SERVICE_TYPE_POSTPAID)
            ->get()
            ->groupBy(fn ($package) => $package->network_code . ':' . $package->monthly_price);

        DB::table('phone_numbers')
            ->select(['id', 'service_type', 'network_code', 'plan_name', 'sale_price'])
            ->orderBy('id')
            ->chunkById(500, function ($numbers) use ($packagesByNetworkAndPrice): void {
                foreach ($numbers as $number) {
                    $serviceType = strtolower(trim((string) $number->service_type));
                    $salePrice = $number->sale_price !== null ? (int) $number->sale_price : null;

                    if ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID) {
                        DB::table('phone_numbers')
                            ->where('id', $number->id)
                            ->update([
                                'package_id' => null,
                                'initial_payment_price' => $salePrice,
                            ]);
                        continue;
                    }

                    $packagePrice = $this->extractPackagePrice($number->plan_name) ?? $salePrice;
                    $networkCode = strtolower(trim((string) $number->network_code)) ?: PhoneNumber::NETWORK_TRUE_DTAC;
                    $package = $packagePrice !== null
                        ? ($packagesByNetworkAndPrice->get($networkCode . ':' . $packagePrice)?->first()
                            ?? $packagesByNetworkAndPrice->get(PhoneNumber::NETWORK_TRUE_DTAC . ':' . $packagePrice)?->first())
                        : null;

                    DB::table('phone_numbers')
                        ->where('id', $number->id)
                        ->update([
                            'package_id' => $package?->id,
                            'initial_payment_price' => $salePrice,
                        ]);
                }
            });
    }

    private function backfillCustomerOrders(): void
    {
        DB::table('customer_orders')
            ->leftJoin('phone_numbers', 'customer_orders.phone_number_id', '=', 'phone_numbers.id')
            ->leftJoin('phone_packages', 'phone_numbers.package_id', '=', 'phone_packages.id')
            ->select([
                'customer_orders.id',
                'customer_orders.service_type',
                'customer_orders.selected_package',
                'phone_numbers.package_id as number_package_id',
                'phone_packages.code as number_package_code',
                'phone_packages.name as number_package_name',
                'phone_packages.monthly_price as number_monthly_price',
            ])
            ->chunkById(500, function ($orders): void {
                foreach ($orders as $order) {
                    $serviceType = strtolower(trim((string) $order->service_type));
                    $selectedPackage = $order->selected_package !== null ? (int) $order->selected_package : null;

                    DB::table('customer_orders')
                        ->where('id', $order->id)
                        ->update([
                            'package_id' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : $order->number_package_id,
                            'package_code' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : $order->number_package_code,
                            'package_name' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : $order->number_package_name,
                            'monthly_price' => $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? null : $order->number_monthly_price,
                            'initial_payment_price' => $selectedPackage,
                        ]);
                }
            }, 'customer_orders.id', 'id');
    }

    private function extractPackagePrice(mixed $planName): ?int
    {
        $planName = trim((string) $planName);
        if (preg_match('/(\d{3,5})\s*$/u', $planName, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }
};
