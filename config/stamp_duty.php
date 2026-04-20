<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SDLT Rates — effective 1 April 2025 (HMRC)
    |--------------------------------------------------------------------------
    | Thresholds are in pence. Rates are percentages (e.g. 5 = 5%).
    */

    'standard' => [
        // 'from' is the lower boundary (exclusive) of the band — amounts UP TO 'to' are taxed at 'rate'.
        // The applyBands logic skips a band when pricePence <= from, so using exact thresholds is correct.
        ['from' => 0,         'to' => 12500000,  'rate' => 0,  'description' => 'Up to £125,000'],
        ['from' => 12500000,  'to' => 25000000,  'rate' => 2,  'description' => '£125,001 to £250,000'],
        ['from' => 25000000,  'to' => 92500000,  'rate' => 5,  'description' => '£250,001 to £925,000'],
        ['from' => 92500000,  'to' => 150000000, 'rate' => 10, 'description' => '£925,001 to £1,500,000'],
        ['from' => 150000000, 'to' => null,       'rate' => 12, 'description' => 'Over £1,500,000'],
    ],

    'first_time_buyer' => [
        /*
         * Applies only when price <= £500,000. If price > £500,000 the
         * standard rates are used instead (see service).
         */
        'max_price'  => 50000000, // £500,000 in pence
        'bands' => [
            ['from' => 0,        'to' => 30000000, 'rate' => 0, 'description' => 'Up to £300,000'],
            ['from' => 30000000, 'to' => 50000000, 'rate' => 5, 'description' => '£300,001 to £500,000'],
        ],
    ],

    'additional_property' => [
        /*
         * Standard bands with a flat +3% surcharge applied to each band rate.
         */
        'surcharge' => 3,
    ],
];
