<?php

namespace Tests\Unit;

use App\Services\StampDutyCalculatorService;
use Tests\TestCase;

class StampDutyCalculatorServiceTest extends TestCase
{
    private StampDutyCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StampDutyCalculatorService();
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------

    private function tax(float $price, string $type = 'standard'): int
    {
        return $this->service->calculate($price, $type)['total'];
    }

    private function poundsToTax(float $pounds): int
    {
        return (int) round($pounds * 100);
    }

    // -----------------------------------------------------------------------
    // Standard rates
    // -----------------------------------------------------------------------

    public function test_standard_zero_price(): void
    {
        $result = $this->service->calculate(0.01, 'standard');
        $this->assertSame(0, $result['total']);
    }

    public function test_standard_100k(): void
    {
        // £100,000 — entirely in the 0% band → £0
        $this->assertSame(0, $this->tax(100_000));
    }

    public function test_standard_125k_boundary(): void
    {
        // £125,000 — exactly at the top of the 0% band → £0
        $this->assertSame(0, $this->tax(125_000));
    }

    public function test_standard_200k(): void
    {
        // £75,000 at 2% = £1,500
        $this->assertSame($this->poundsToTax(1_500), $this->tax(200_000));
    }

    public function test_standard_250k_boundary(): void
    {
        // £125,000 at 2% = £2,500
        $this->assertSame($this->poundsToTax(2_500), $this->tax(250_000));
    }

    public function test_standard_300k(): void
    {
        // £2,500 + 5% on £50,000 = £2,500 + £2,500 = £5,000
        $this->assertSame($this->poundsToTax(5_000), $this->tax(300_000));
    }

    public function test_standard_500k(): void
    {
        // £2,500 + 5% on £250,000 = £2,500 + £12,500 = £15,000
        $this->assertSame($this->poundsToTax(15_000), $this->tax(500_000));
    }

    public function test_standard_925k(): void
    {
        // £2,500 + 5% on £675,000 = £2,500 + £33,750 = £36,250
        $this->assertSame($this->poundsToTax(36_250), $this->tax(925_000));
    }

    public function test_standard_1500k(): void
    {
        // £36,250 + 10% on £575,000 = £36,250 + £57,500 = £93,750
        $this->assertSame($this->poundsToTax(93_750), $this->tax(1_500_000));
    }

    public function test_standard_2000k(): void
    {
        // £93,750 + 12% on £500,000 = £93,750 + £60,000 = £153,750
        $this->assertSame($this->poundsToTax(153_750), $this->tax(2_000_000));
    }

    // -----------------------------------------------------------------------
    // First-time buyer rates
    // -----------------------------------------------------------------------

    public function test_ftb_100k(): void
    {
        // 0% on £100,000 → £0
        $this->assertSame(0, $this->tax(100_000, 'first_time_buyer'));
    }

    public function test_ftb_300k_boundary(): void
    {
        // 0% on £300,000 → £0
        $this->assertSame(0, $this->tax(300_000, 'first_time_buyer'));
    }

    public function test_ftb_400k(): void
    {
        // 0% on £300,000 + 5% on £100,000 = £5,000
        $this->assertSame($this->poundsToTax(5_000), $this->tax(400_000, 'first_time_buyer'));
    }

    public function test_ftb_500k_boundary(): void
    {
        // 0% on £300,000 + 5% on £200,000 = £10,000
        $this->assertSame($this->poundsToTax(10_000), $this->tax(500_000, 'first_time_buyer'));
    }

    public function test_ftb_600k_falls_back_to_standard(): void
    {
        // Price > £500k → standard rates
        // £2,500 + 5% on £350,000 = £2,500 + £17,500 = £20,000
        $ftb      = $this->tax(600_000, 'first_time_buyer');
        $standard = $this->tax(600_000, 'standard');
        $this->assertSame($this->poundsToTax(20_000), $ftb);
        $this->assertSame($standard, $ftb);
    }

    // -----------------------------------------------------------------------
    // Additional property (surcharge)
    // -----------------------------------------------------------------------

    public function test_additional_100k(): void
    {
        // 3% on £100,000 = £3,000
        $this->assertSame($this->poundsToTax(3_000), $this->tax(100_000, 'additional_property'));
    }

    public function test_additional_250k(): void
    {
        // 3% on £125,000 + 5% on £125,000 = £3,750 + £6,250 = £10,000
        $this->assertSame($this->poundsToTax(10_000), $this->tax(250_000, 'additional_property'));
    }

    public function test_additional_500k(): void
    {
        // 3% on £125,000 + 5% on £125,000 + 8% on £250,000
        // = £3,750 + £6,250 + £20,000 = £30,000
        $this->assertSame($this->poundsToTax(30_000), $this->tax(500_000, 'additional_property'));
    }

    // -----------------------------------------------------------------------
    // Effective rate
    // -----------------------------------------------------------------------

    public function test_effective_rate_is_correct(): void
    {
        $result = $this->service->calculate(500_000, 'standard');
        // £15,000 / £500,000 = 3.00%
        $this->assertSame(3.0, $result['effective_rate']);
    }

    public function test_effective_rate_zero_for_nil_tax(): void
    {
        $result = $this->service->calculate(100_000, 'standard');
        $this->assertSame(0.0, $result['effective_rate']);
    }

    // -----------------------------------------------------------------------
    // Breakdown structure
    // -----------------------------------------------------------------------

    public function test_breakdown_contains_required_keys(): void
    {
        $result = $this->service->calculate(300_000, 'standard');
        foreach ($result['breakdown'] as $band) {
            $this->assertArrayHasKey('description', $band);
            $this->assertArrayHasKey('rate', $band);
            $this->assertArrayHasKey('taxable_amount', $band);
            $this->assertArrayHasKey('tax', $band);
        }
    }

    public function test_breakdown_sums_to_total(): void
    {
        $result = $this->service->calculate(750_000, 'standard');
        $sum = array_sum(array_column($result['breakdown'], 'tax'));
        $this->assertSame($result['total'], $sum);
    }
}
