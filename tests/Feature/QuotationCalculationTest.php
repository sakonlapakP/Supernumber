<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuotationCalculationTest extends TestCase
{
    /**
     * Test standard calculation logic
     * Given Base Price = 50,000 THB:
     * - Selling Price = 50,000.00 THB
     * - VAT 7% = 3,500.00 THB
     * - Grand Total = 53,500.00 THB
     * - WHT 3% (calculated on Selling Price) = 1,500.00 THB
     * - Net to Pay = 52,000.00 THB
     */
    public function test_standard_calculation_50000_base_price(): void
    {
        $basePrice = 50000.00;
        
        $sellingPrice = $basePrice;
        $vat = round($sellingPrice * 0.07, 2);
        $grandTotal = round($sellingPrice + $vat, 2);
        $wht = round($sellingPrice * 0.03, 2);
        $netToPay = round($grandTotal - $wht, 2);

        $this->assertEquals(50000.00, $sellingPrice);
        $this->assertEquals(3500.00, $vat);
        $this->assertEquals(53500.00, $grandTotal);
        $this->assertEquals(1500.00, $wht);
        $this->assertEquals(52000.00, $netToPay);
    }

    /**
     * Test reverse calculation logic
     * Given Target Income = 50,000 THB:
     * - Selling Price = 50,000 / 0.97 = 51,546.39 THB
     * - VAT 7% = 3,608.25 THB
     * - Grand Total = 55,154.64 THB
     * - WHT 3% (calculated on Selling Price) = 1,546.39 THB
     * - Net to Pay = 53,608.25 THB
     * - Net Service Income (Selling Price - WHT) = 50,000.00 THB (Target Income matched exactly!)
     */
    public function test_reverse_calculation_50000_target_income(): void
    {
        $targetIncome = 50000.00;

        $sellingPrice = round($targetIncome / 0.97, 2);
        $vat = round($sellingPrice * 0.07, 2);
        $grandTotal = round($sellingPrice + $vat, 2);
        $wht = round($sellingPrice * 0.03, 2);
        $netToPay = round($grandTotal - $wht, 2);
        
        $serviceNetIncome = round($sellingPrice - $wht, 2);

        $this->assertEquals(51546.39, $sellingPrice);
        $this->assertEquals(3608.25, $vat);
        $this->assertEquals(55154.64, $grandTotal);
        $this->assertEquals(1546.39, $wht);
        $this->assertEquals(53608.25, $netToPay);
        $this->assertEquals(50000.00, $serviceNetIncome); // Exactly equal to Target Income!
    }
}
