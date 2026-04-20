<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stamp Duty Calculator')</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6f9;
            color: #1a1a2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: #1a1a2e;
            color: #fff;
            padding: 1rem 1.5rem;
        }
        header h1 { font-size: 1.25rem; font-weight: 600; letter-spacing: .02em; }
        header p  { font-size: .8rem; color: #a0aec0; margin-top: .2rem; }

        main {
            flex: 1;
            max-width: 760px;
            width: 100%;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        footer {
            text-align: center;
            font-size: .75rem;
            color: #718096;
            padding: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        /* Cards */
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            padding-bottom: .75rem;
            border-bottom: 1px solid #e2e8f0;
            color: #2d3748;
        }

        /* Form */
        .form-group { margin-bottom: 1.25rem; }
        label {
            display: block;
            font-size: .875rem;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: .4rem;
        }
        label .hint {
            font-weight: 400;
            color: #718096;
            font-size: .8rem;
            display: block;
            margin-top: .1rem;
        }
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: .6rem .85rem;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font-size: .95rem;
            color: #2d3748;
            transition: border-color .15s;
            background: #fff;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66,153,225,.15);
        }
        .price-wrap { position: relative; }
        .price-wrap .currency-symbol {
            position: absolute;
            left: .85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
            font-size: .95rem;
        }
        .price-wrap input { padding-left: 1.75rem; }

        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: .85rem 1rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: 1.1rem;
            height: 1.1rem;
            margin-top: .15rem;
            flex-shrink: 0;
            cursor: pointer;
        }
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }

        /* Field errors */
        .field-error {
            font-size: .8rem;
            color: #e53e3e;
            margin-top: .35rem;
        }
        input.is-invalid, select.is-invalid { border-color: #e53e3e; }

        /* Button */
        .btn-primary {
            background: #2b6cb0;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: .7rem 1.75rem;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            width: 100%;
        }
        .btn-primary:hover { background: #2c5282; }
        .btn-primary:disabled { background: #a0aec0; cursor: not-allowed; }

        /* Alerts */
        .alert {
            border-radius: 5px;
            padding: .85rem 1rem;
            font-size: .875rem;
            margin-bottom: 1.25rem;
        }
        .alert-error   { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
        .alert-warning { background: #fffaf0; border: 1px solid #feebc8; color: #c05621; }
        .alert-info    { background: #ebf8ff; border: 1px solid #bee3f8; color: #2b6cb0; }

        /* Spinner */
        .spinner-wrap {
            display: flex;
            align-items: center;
            gap: .75rem;
            color: #4a5568;
            font-size: .875rem;
            padding: .5rem 0;
        }
        .spinner {
            width: 1.25rem;
            height: 1.25rem;
            border: 3px solid #e2e8f0;
            border-top-color: #4299e1;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Result summary */
        .result-summary {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .summary-box {
            flex: 1;
            min-width: 140px;
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 6px;
            padding: .85rem 1rem;
            text-align: center;
        }
        .summary-box .label { font-size: .75rem; color: #4a5568; text-transform: uppercase; letter-spacing: .05em; }
        .summary-box .value { font-size: 1.6rem; font-weight: 700; color: #2b6cb0; margin-top: .2rem; }
        .summary-box .value.zero { color: #276749; }

        /* Breakdown table */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .875rem;
        }
        .breakdown-table th {
            background: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            text-align: left;
            padding: .6rem .75rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .breakdown-table td {
            padding: .6rem .75rem;
            border-bottom: 1px solid #f0f4f8;
            color: #2d3748;
        }
        .breakdown-table tr:last-child td { border-bottom: none; }
        .breakdown-table td.amount { text-align: right; font-family: monospace; }
        .breakdown-table tfoot td {
            font-weight: 700;
            border-top: 2px solid #e2e8f0;
            padding-top: .75rem;
        }
        .rate-badge {
            display: inline-block;
            background: #e2e8f0;
            color: #4a5568;
            border-radius: 3px;
            padding: .1rem .4rem;
            font-size: .8rem;
            font-family: monospace;
        }

        /* Utility */
        .hidden { display: none !important; }
        .text-sm { font-size: .8rem; color: #718096; margin-top: .5rem; }
    </style>
</head>
<body>

<header>
    <h1>Stamp Duty Land Tax Calculator</h1>
    <p>Residential property purchases in England &mdash; rates current as of April 2026</p>
</header>

<main>
    @yield('content')
</main>

<footer>
    Rates sourced from <a href="https://www.gov.uk/stamp-duty-land-tax/residential-property-rates" target="_blank" rel="noopener">HMRC guidance</a>.
    This calculator covers standard residential SDLT in England only.
    It is not legal or financial advice.
</footer>

</body>
</html>
