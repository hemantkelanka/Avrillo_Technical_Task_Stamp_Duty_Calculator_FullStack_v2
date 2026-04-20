<?php

/*
|--------------------------------------------------------------------------
| Stamp Duty Land Tax (SDLT) Configuration
|--------------------------------------------------------------------------
|
| Source: HMRC — https://www.gov.uk/stamp-duty-land-tax/residential-property-rates
| Rates correct as of April 2026 (reverted to pre-holiday rates on 1 April 2025).
|
| All thresholds are stored in PENCE to avoid floating-point precision issues.
| Rate percentages are stored as plain integers (e.g. 5 means 5%).
|
| Three scenarios are supported:
|   standard          — buyer already owns (or has owned) property
|   first_time_buyer  — never owned a home anywhere in the world
|   additional        — purchase means buyer will own more than one property
|
| Judgement calls noted here:
|   - Additional property surcharge is a flat +5% applied to every band
|     on top of the standard rate (raised from 3% to 5% in October 2024).
|   - First-time buyer relief is entirely withdrawn when price > £500,000;
|     above that cap standard rates apply in full.
|   - "from" is inclusive, "to" is inclusive (null = no upper limit).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Standard Residential Rates
    |--------------------------------------------------------------------------
    | Applied when the buyer already owns residential property, or has done
    | so previously, and is not purchasing an additional property.
    */
    'standard' => [
        'bands' => [
            // 'from' is the exclusive lower bound (= previous band's 'to').
            // The service computes: taxable = min(price, to) - from
            ['from' => 0,           'to' => 12_500_000,  'rate' => 0],   // £0        – £125,000
            ['from' => 12_500_000,  'to' => 25_000_000,  'rate' => 2],   // £125,000  – £250,000
            ['from' => 25_000_000,  'to' => 92_500_000,  'rate' => 5],   // £250,000  – £925,000
            ['from' => 92_500_000,  'to' => 150_000_000, 'rate' => 10],  // £925,000  – £1,500,000
            ['from' => 150_000_000, 'to' => null,         'rate' => 12],  // above £1,500,000
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | First-Time Buyer Rates
    |--------------------------------------------------------------------------
    | Eligible when EVERY buyer on the transaction has never owned a
    | residential property anywhere in the world.
    |
    | Relief applies only when purchase price ≤ £500,000.
    | If price > £500,000, standard rates apply in full (no partial relief).
    |
    | Price cap stored in pence for consistent comparisons.
    */
    'first_time_buyer' => [
        'relief_cap_pence' => 50_000_000, // £500,000
        'bands' => [
            ['from' => 0,          'to' => 30_000_000, 'rate' => 0],  // £0       – £300,000
            ['from' => 30_000_000, 'to' => 50_000_000, 'rate' => 5],  // £300,000 – £500,000
        ],
        // Above the cap the service falls back to the 'standard' bands.
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Property Surcharge
    |--------------------------------------------------------------------------
    | A flat percentage added ON TOP of each standard band rate when the
    | buyer will own more than one residential property after completion.
    |
    | First-time buyer relief cannot be combined with additional property
    | surcharge (owning another property disqualifies FTB status).
    | The service enforces this: if additional_property is true, standard
    | bands + surcharge are used regardless of buyer_type.
    */
    'additional_property_surcharge' => 5, // +5% on every band (since October 2024)

];
