<?php

namespace App\Services;

class StampDutyCalculatorService
{
    private array $standardBands;
    private array $ftbConfig;
    private int   $surcharge;

    public function __construct()
    {
        $this->standardBands = config('stamp_duty.standard');
        $this->ftbConfig     = config('stamp_duty.first_time_buyer');
        $this->surcharge     = config('stamp_duty.additional_property.surcharge');
    }

    /**
     * Calculate SDLT for a given property price and purchase type.
     *
     * @param  float  $price        Property price in pounds (e.g. 250000.00)
     * @param  string $purchaseType 'standard' | 'first_time_buyer' | 'additional_property'
     * @return array{total: int, effective_rate: float, breakdown: array}
     */
    public function calculate(float $price, string $purchaseType): array
    {
        // Work entirely in pence to avoid floating-point errors.
        $pricePence = (int) round($price * 100);

        $bands = $this->resolveBands($pricePence, $purchaseType);

        $breakdown = $this->applyBands($pricePence, $bands);

        $total = array_sum(array_column($breakdown, 'tax'));

        $effectiveRate = $pricePence > 0
            ? round(($total / $pricePence) * 100, 2)
            : 0.0;

        return [
            'total'          => $total,
            'effective_rate' => $effectiveRate,
            'breakdown'      => $breakdown,
        ];
    }

    /**
     * Return the band definitions (with rates) appropriate for this purchase.
     */
    private function resolveBands(int $pricePence, string $purchaseType): array
    {
        if ($purchaseType === 'first_time_buyer' && $pricePence <= $this->ftbConfig['max_price']) {
            return $this->ftbConfig['bands'];
        }

        if ($purchaseType === 'additional_property') {
            return array_map(function (array $band): array {
                $band['rate'] += $this->surcharge;
                return $band;
            }, $this->standardBands);
        }

        // 'standard' OR first_time_buyer above the £500k threshold.
        return $this->standardBands;
    }

    /**
     * Slice the price across bands and compute tax per band.
     *
     * @return array Array of band breakdown rows.
     */
    private function applyBands(int $pricePence, array $bands): array
    {
        $breakdown = [];

        foreach ($bands as $band) {
            $bandFrom = $band['from'];
            $bandTo   = $band['to'];

            if ($pricePence <= $bandFrom) {
                break; // Price doesn't reach this band.
            }

            $upper         = $bandTo !== null ? min($pricePence, $bandTo) : $pricePence;
            // Amount of the purchase price that falls into this band.
            $taxableAmount = $upper - $bandFrom;

            $tax = (int) round($taxableAmount * $band['rate'] / 100);

            $breakdown[] = [
                'description'    => $band['description'],
                'rate'           => (float) $band['rate'],
                'taxable_amount' => $taxableAmount,
                'tax'            => $tax,
            ];
        }

        return $breakdown;
    }
}
