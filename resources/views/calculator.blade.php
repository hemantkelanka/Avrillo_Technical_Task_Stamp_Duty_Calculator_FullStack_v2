<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stamp Duty Land Tax Calculator</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f4f6f9;
            color: #1a202c;
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 2rem;
        }

        header h1 {
            font-size: 1.9rem;
            font-weight: 700;
            color: #1a202c;
        }

        header p {
            margin-top: 0.5rem;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .card h2 {
            font-size: 1.15rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: #2d3748;
            border-bottom: 2px solid #edf2f7;
            padding-bottom: 0.6rem;
        }

        .form-group {
            margin-bottom: 1.4rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.4rem;
        }

        .price-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .price-prefix {
            position: absolute;
            left: 0.9rem;
            font-size: 1.05rem;
            font-weight: 600;
            color: #4a5568;
            pointer-events: none;
        }

        input[type="number"] {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 2rem;
            border: 1.5px solid #cbd5e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color .2s;
            -moz-appearance: textfield;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button { -webkit-appearance: none; }

        input[type="number"]:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66,153,225,.15);
        }

        input.is-invalid { border-color: #fc8181; }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }

        .radio-option {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.75rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .radio-option:hover { border-color: #4299e1; background: #ebf8ff; }

        .radio-option input[type="radio"] {
            margin-top: 0.15rem;
            accent-color: #3182ce;
            flex-shrink: 0;
        }

        .radio-option.selected { border-color: #3182ce; background: #ebf8ff; }

        .radio-label { font-size: 0.93rem; }
        .radio-label strong { display: block; font-weight: 600; color: #2d3748; }
        .radio-label span { color: #718096; font-size: 0.85rem; }

        .btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #3182ce;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, transform .1s;
            width: 100%;
        }

        .btn:hover { background: #2b6cb0; }
        .btn:active { transform: scale(.98); }

        .error-list {
            background: #fff5f5;
            border: 1.5px solid #fc8181;
            border-radius: 8px;
            padding: 1rem 1.2rem;
            margin-bottom: 1.2rem;
        }

        .error-list p { font-weight: 600; color: #c53030; margin-bottom: 0.4rem; }
        .error-list ul { padding-left: 1.2rem; }
        .error-list li { color: #c53030; font-size: 0.9rem; }

        .notice {
            background: #fffbeb;
            border: 1.5px solid #f6d860;
            border-radius: 8px;
            padding: 0.9rem 1.1rem;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
            color: #744210;
        }

        /* Results */
        .result-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-box {
            background: #f7fafc;
            border-radius: 10px;
            padding: 1.1rem;
            text-align: center;
        }

        .stat-box .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #718096;
            font-weight: 600;
        }

        .stat-box .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #2d3748;
            margin-top: 0.3rem;
        }

        .stat-box.highlight .stat-value { color: #2b6cb0; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead tr { background: #edf2f7; }
        thead th {
            text-align: left;
            padding: 0.7rem 0.9rem;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        tbody tr { border-bottom: 1px solid #f0f4f8; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 0.65rem 0.9rem; color: #2d3748; }

        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }

        .zero-tax { color: #718096; }

        footer {
            text-align: center;
            color: #a0aec0;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
<div class="container">

    <header>
        <h1>🏠 Stamp Duty Land Tax Calculator</h1>
        <p>Calculate how much Stamp Duty Land Tax (SDLT) you'll pay on a property in England or Northern Ireland.</p>
        <p style="margin-top:.3rem; font-size:.8rem; color:#718096">Rates effective from 1 April 2025 (HMRC)</p>
    </header>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="error-list" role="alert">
            <p>Please correct the following:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- First-time buyer limit exceeded notice --}}
    @if (!empty($ftbLimitExceeded))
        <div class="notice">
            ⚠️ <strong>First-time buyer relief does not apply</strong> — the property price exceeds £500,000.
            Standard rates have been used instead.
        </div>
    @endif

    {{-- FORM --}}
    <div class="card">
        <h2>Property details</h2>
        <form method="POST" action="{{ route('calculate') }}" id="sdlt-form">
            @csrf

            <div class="form-group">
                <label for="price">Property price</label>
                <div class="price-wrapper">
                    <span class="price-prefix">£</span>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        step="0.01"
                        min="0.01"
                        max="100000000"
                        placeholder="e.g. 350000"
                        value="{{ old('price', $price ?? '') }}"
                        class="{{ $errors->has('price') ? 'is-invalid' : '' }}"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label>Purchase type</label>
                <div class="radio-group">
                    @php
                        $selectedType = old('purchase_type', $purchase_type ?? 'standard');
                    @endphp

                    <label class="radio-option {{ $selectedType === 'standard' ? 'selected' : '' }}">
                        <input type="radio" name="purchase_type" value="standard"
                            {{ $selectedType === 'standard' ? 'checked' : '' }}>
                        <span class="radio-label">
                            <strong>Standard purchase</strong>
                            <span>Moving home or buying a property that will be your only home</span>
                        </span>
                    </label>

                    <label class="radio-option {{ $selectedType === 'first_time_buyer' ? 'selected' : '' }}">
                        <input type="radio" name="purchase_type" value="first_time_buyer"
                            {{ $selectedType === 'first_time_buyer' ? 'checked' : '' }}>
                        <span class="radio-label">
                            <strong>First-time buyer</strong>
                            <span>You've never owned a property before (relief applies up to £500,000)</span>
                        </span>
                    </label>

                    <label class="radio-option {{ $selectedType === 'additional_property' ? 'selected' : '' }}">
                        <input type="radio" name="purchase_type" value="additional_property"
                            {{ $selectedType === 'additional_property' ? 'checked' : '' }}>
                        <span class="radio-label">
                            <strong>Additional property</strong>
                            <span>Buy-to-let, second home, or any property you'll own in addition to your current home (3% surcharge applies)</span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn" id="calc-btn">Calculate Stamp Duty</button>
        </form>
    </div>

    {{-- RESULTS --}}
    @isset($result)
        <div class="card" id="results">
            <h2>Your Stamp Duty calculation</h2>

            <div class="result-summary">
                <div class="stat-box highlight">
                    <div class="stat-label">Total SDLT due</div>
                    <div class="stat-value">£{{ number_format($result['total'] / 100, 2) }}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Effective rate</div>
                    <div class="stat-value">{{ number_format($result['effective_rate'], 2) }}%</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tax band</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Amount in band</th>
                        <th class="text-right">Tax payable</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result['breakdown'] as $band)
                        <tr class="{{ $band['tax'] === 0 ? 'zero-tax' : '' }}">
                            <td>{{ $band['description'] }}</td>
                            <td class="text-right">{{ number_format($band['rate'], 0) }}%</td>
                            <td class="text-right">£{{ number_format($band['taxable_amount'] / 100, 2) }}</td>
                            <td class="text-right font-semibold">£{{ number_format($band['tax'] / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="border-top: 2px solid #e2e8f0;">
                        <td colspan="3" class="font-semibold text-right" style="color:#4a5568">Total</td>
                        <td class="text-right font-semibold" style="color:#2b6cb0">
                            £{{ number_format($result['total'] / 100, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endisset

    <footer>
        <p>This calculator is for guidance only. Always verify with a solicitor or HMRC before completing a purchase.</p>
    </footer>

</div>

<script>
    // Highlight selected radio option
    document.querySelectorAll('input[type="radio"][name="purchase_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.radio-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            this.closest('.radio-option').classList.add('selected');
        });
    });

    // Show loading state on submit
    document.getElementById('sdlt-form').addEventListener('submit', function() {
        var btn = document.getElementById('calc-btn');
        btn.textContent = 'Calculating…';
        btn.disabled = true;
    });

    // Scroll to results if present
    var results = document.getElementById('results');
    if (results) {
        results.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>
</body>
</html>
