<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * StampDutyService
 *
 * Pure calculation engine for UK Stamp Duty Land Tax (SDLT).
 * Handles three rate scenarios:
 *   - standard          : buyer has owned property before
 *   - first_time_buyer  : buyer has never owned property anywhere
 *   - additional        : purchase will leave buyer owning more than one property
 *
 * Design decisions:
 *   - All arithmetic done in PENCE (integers) to avoid float precision issues.
 *   - No database access, no side effects — same inputs always produce same output.
 *   - Rate bands and thresholds are read from config/stamp_duty.php.
 *   - If additional_property is true, the surcharge always applies regardless of
 *     buyer type (owning another property disqualifies first-time buyer status).
 *   - First-time buyer relief is all-or-nothing at the £500,000 cap:
 *     if price > cap the service silently falls back to standard rates and sets
 *     a flag in the result so the UI can inform the user.
 */
class StampDutyService
{
    public const BUYER_STANDARD         = 'standard';
    public const BUYER_FIRST_TIME       = 'first_time_buyer';

    /**
     * Calculate SDLT for a residential property purchase in England.
     *
     * @param  int    $pricePence         Purchase price in pence (e.g. 30000000 = £300,000)
     * @param  string $buyerType          'standard' or 'first_time_buyer'
     * @param  bool   $additionalProperty True if buyer will own multiple properties after purchase
     *
     * @return array{
     *   scenario: string,
     *   ftb_relief_withdrawn: bool,
     *   purchase_price_pence: int,
     *   purchase_price_pounds: string,
     *   bands: list<array{
     *     description: string,
     *     from_pence: int,
     *     to_pence: int|null,
     *     rate_pct: int,
     *     taxable_pence: int,
     *     tax_pence: int,
     *     from_pounds: string,
     *     to_pounds: string|null,
     *     taxable_pounds: string,
     *     tax_pounds: string,
     *   }>,
     *   total_tax_pence: int,
     *   total_tax_pounds: string,
     *   effective_rate_pct: string,
     * }
     *
     * @throws InvalidArgumentException
     */
    public function calculate(
        int $pricePence,
        string $buyerType = self::BUYER_STANDARD,
        bool $additionalProperty = false
    ): array {
        $this->validate($pricePence, $buyerType);

        [$bands, $scenario, $ftbReliefWithdrawn] = $this->resolveBands(
            $pricePence,
            $buyerType,
            $additionalProperty
        );

        $bandResults  = [];
        $totalTaxPence = 0;

        foreach ($bands as $band) {
            $from = $band['from'];
            $to   = $band['to'];
            $rate = $band['rate'];

            // How much of the purchase price falls in this band?
            if ($pricePence <= $from) {
                // Price is below this band — nothing to tax here.
                continue;
            }

            $bandCeiling   = ($to === null) ? $pricePence : min($pricePence, $to);
            $taxablePence  = $bandCeiling - $from;
            $taxPence      = intdiv($taxablePence * $rate, 100);
            $totalTaxPence += $taxPence;

            $bandResults[] = [
                'description'    => $this->bandDescription($from, $to, $rate),
                'from_pence'     => $from,
                'to_pence'       => $to,
                'rate_pct'       => $rate,
                'taxable_pence'  => $taxablePence,
                'tax_pence'      => $taxPence,
                // Formatted display values (pounds & pence as strings)
                'from_pounds'    => $this->penceToPoundsFormatted($from),
                'to_pounds'      => $to !== null ? $this->penceToPoundsFormatted($to) : null,
                'taxable_pounds' => $this->penceToPoundsFormatted($taxablePence),
                'tax_pounds'     => $this->penceToPoundsFormatted($taxPence),
            ];
        }

        // Effective rate = total tax / purchase price × 100, to 2 dp.
        $effectiveRate = $pricePence > 0
            ? number_format(($totalTaxPence / $pricePence) * 100, 2)
            : '0.00';

        return [
            'scenario'             => $scenario,
            'ftb_relief_withdrawn' => $ftbReliefWithdrawn,
            'purchase_price_pence' => $pricePence,
            'purchase_price_pounds'=> $this->penceToPoundsFormatted($pricePence),
            'bands'                => $bandResults,
            'total_tax_pence'      => $totalTaxPence,
            'total_tax_pounds'     => $this->penceToPoundsFormatted($totalTaxPence),
            'effective_rate_pct'   => $effectiveRate,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve which band set to use and annotate the scenario name.
     *
     * @return array{0: list<array>, 1: string, 2: bool}
     */
    private function resolveBands(
        int $pricePence,
        string $buyerType,
        bool $additionalProperty
    ): array {
        $surcharge    = (int) config('stamp_duty.additional_property_surcharge');
        $standardBands = config('stamp_duty.standard.bands');

        // Additional property always wins — FTB + additional is impossible in law.
        if ($additionalProperty) {
            $bands = $this->applyFlatSurcharge($standardBands, $surcharge);
            return [$bands, 'additional_property', false];
        }

        if ($buyerType === self::BUYER_FIRST_TIME) {
            $cap = (int) config('stamp_duty.first_time_buyer.relief_cap_pence');

            if ($pricePence <= $cap) {
                $bands = config('stamp_duty.first_time_buyer.bands');
                return [$bands, 'first_time_buyer', false];
            }

            // Price exceeds cap — relief withdrawn, fall back to standard.
            return [$standardBands, 'standard', true];
        }

        return [$standardBands, 'standard', false];
    }

    /**
     * Add a flat surcharge to every band rate.
     */
    private function applyFlatSurcharge(array $bands, int $surcharge): array
    {
        return array_map(
            static fn (array $band) => array_merge($band, ['rate' => $band['rate'] + $surcharge]),
            $bands
        );
    }

    /**
     * Build a human-readable band description in plain English.
     */
    private function bandDescription(int $fromPence, ?int $toPence, int $ratePct): string
    {
        $fromFormatted = $this->penceToPoundsFormatted($fromPence);

        if ($toPence === null) {
            return "Portion above £{$fromFormatted} at {$ratePct}%";
        }

        $toFormatted = $this->penceToPoundsFormatted($toPence);

        if ($ratePct === 0) {
            return "Up to £{$toFormatted} (no tax)";
        }

        return "£{$fromFormatted} to £{$toFormatted} at {$ratePct}%";
    }

    /**
     * Convert an integer pence value to a formatted pounds string.
     * e.g. 12_500_000 → "125,000.00"
     */
    private function penceToPoundsFormatted(int $pence): string
    {
        return number_format($pence / 100, 2);
    }

    /**
     * Validate inputs, throw on invalid data.
     *
     * @throws InvalidArgumentException
     */
    private function validate(int $pricePence, string $buyerType): void
    {
        if ($pricePence < 0) {
            throw new InvalidArgumentException('Purchase price cannot be negative.');
        }

        $validBuyerTypes = [self::BUYER_STANDARD, self::BUYER_FIRST_TIME];

        if (! in_array($buyerType, $validBuyerTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid buyer type '{$buyerType}'. Must be one of: " . implode(', ', $validBuyerTypes)
            );
        }
    }
}
