@extends('layouts.app')

@section('title', 'Stamp Duty Calculator')

@section('content')

{{-- ============================================================
     Calculator form
     ============================================================ --}}
<div class="card">
    <div class="card-title">Your property details</div>

    <form id="calculator-form" novalidate>
        @csrf

        {{-- Purchase price --}}
        <div class="form-group">
            <label for="purchase_price">
                Purchase price
                <span class="hint">Enter the full purchase price in pounds, e.g. 350000</span>
            </label>
            <div class="price-wrap">
                <span class="currency-symbol">£</span>
                <input
                    type="number"
                    id="purchase_price"
                    name="purchase_price"
                    min="0"
                    step="1"
                    placeholder="e.g. 350000"
                    autocomplete="off"
                >
            </div>
            <div class="field-error hidden" id="err-purchase_price"></div>
        </div>

        {{-- Buyer type --}}
        <div class="form-group">
            <label for="buyer_type">
                Have you owned a home before?
                <span class="hint">Select "No" if you have never owned a residential property anywhere in the world.</span>
            </label>
            <select id="buyer_type" name="buyer_type">
                <option value="">— please select —</option>
                <option value="standard">Yes &mdash; I have owned property before</option>
                <option value="first_time_buyer">No &mdash; I am a first-time buyer</option>
            </select>
            <div class="field-error hidden" id="err-buyer_type"></div>
        </div>

        {{-- Additional property (hidden when first-time buyer is selected) --}}
        <div class="form-group" id="additional-property-group">
            <div class="checkbox-group">
                <input type="checkbox" id="additional_property" name="additional_property" value="1">
                <label for="additional_property">
                    I will own more than one residential property after this purchase
                    <span class="hint">Tick this if you are buying a second home, buy-to-let property, or holiday home and will still own your current home.</span>
                </label>
            </div>
            <div class="field-error hidden" id="err-additional_property"></div>
        </div>

        {{-- General server error --}}
        <div class="alert alert-error hidden" id="general-error"></div>

        <button type="submit" class="btn-primary" id="submit-btn">Calculate Stamp Duty</button>
    </form>
</div>

{{-- ============================================================
     Loading state
     ============================================================ --}}
<div class="card hidden" id="loading-card">
    <div class="spinner-wrap">
        <div class="spinner"></div>
        <span>Calculating&hellip;</span>
    </div>
</div>

{{-- ============================================================
     Results card (hidden until a successful response)
     ============================================================ --}}
<div class="card hidden" id="result-card">
    <div class="card-title">Stamp Duty breakdown</div>

    {{-- FTB relief withdrawn notice --}}
    <div class="alert alert-warning hidden" id="ftb-withdrawn-notice">
        <strong>First-time buyer relief does not apply.</strong>
        Because the purchase price exceeds £500,000, standard rates are used in full.
        This is the HMRC rule &mdash; there is no partial relief above the cap.
    </div>

    {{-- FTB overridden by additional property --}}
    <div class="alert alert-warning hidden" id="ftb-overridden-notice">
        <strong>First-time buyer relief cannot apply here.</strong>
        Owning another property after this purchase disqualifies you from first-time buyer relief.
        The additional property surcharge rates have been used instead.
    </div>

    {{-- Summary boxes --}}
    <div class="result-summary">
        <div class="summary-box">
            <div class="label">Purchase price</div>
            <div class="value" id="res-price"></div>
        </div>
        <div class="summary-box">
            <div class="label">Total stamp duty</div>
            <div class="value" id="res-total"></div>
        </div>
        <div class="summary-box">
            <div class="label">Effective rate</div>
            <div class="value" id="res-rate"></div>
        </div>
    </div>

    {{-- Scenario label --}}
    <div class="alert alert-info" id="res-scenario-label"></div>

    {{-- Band breakdown table --}}
    <table class="breakdown-table">
        <thead>
            <tr>
                <th>Portion of purchase price</th>
                <th style="text-align:right">Taxable amount</th>
                <th style="text-align:right">Tax owed</th>
            </tr>
        </thead>
        <tbody id="breakdown-body">
            {{-- Rows injected by JS --}}
        </tbody>
        <tfoot>
            <tr>
                <td><strong>Total</strong></td>
                <td></td>
                <td class="amount" id="res-total-foot"></td>
            </tr>
        </tfoot>
    </table>

    <p class="text-sm">
        Figures above are for illustration only. Consult a solicitor or HMRC for a definitive calculation.
    </p>
</div>

{{-- ============================================================
     Vanilla JS — no build step required
     ============================================================ --}}
<script>
(function () {
    'use strict';

    /* ---- Element refs ---- */
    const form             = document.getElementById('calculator-form');
    const submitBtn        = document.getElementById('submit-btn');
    const loadingCard      = document.getElementById('loading-card');
    const resultCard       = document.getElementById('result-card');
    const generalError     = document.getElementById('general-error');
    const breakdownBody    = document.getElementById('breakdown-body');
    const ftbWithdrawn     = document.getElementById('ftb-withdrawn-notice');
    const ftbOverridden    = document.getElementById('ftb-overridden-notice');

    /* ---- Helpers ---- */
    function show(el)  { el.classList.remove('hidden'); }
    function hide(el)  { el.classList.add('hidden'); }
    function text(el, t) { el.textContent = t; }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
            hide(el);
        });
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
        hide(generalError);
    }

    function showFieldErrors(errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const errEl  = document.getElementById('err-' + field);
            const inputEl = document.getElementById(field) || document.querySelector('[name="' + field + '"]');
            if (errEl) {
                errEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                show(errEl);
            }
            if (inputEl) inputEl.classList.add('is-invalid');
        });
    }

    function formatPounds(str) {
        // str comes from PHP number_format: "125,000.00"
        // Display as £125,000 (strip trailing .00 for whole amounts)
        const num = parseFloat(str.replace(/,/g, ''));
        if (Number.isInteger(num)) return '£' + num.toLocaleString('en-GB');
        return '£' + num.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function scenarioLabel(scenario, ftbReliefWithdrawn) {
        if (scenario === 'additional_property') {
            return 'Additional property rates applied (standard rates + 5% surcharge on every band).';
        }
        if (scenario === 'first_time_buyer') {
            return 'First-time buyer relief applied (0% up to £300,000; 5% from £300,001 to £500,000).';
        }
        if (ftbReliefWithdrawn) {
            return 'Standard rates applied. (First-time buyer relief withdrawn — purchase price exceeds £500,000.)';
        }
        return 'Standard residential rates applied.';
    }

    /* ---- Show/hide additional property block based on buyer type ---- */
    const buyerTypeEl            = document.getElementById('buyer_type');
    const additionalPropertyGroup = document.getElementById('additional-property-group');
    const additionalPropertyCb   = document.getElementById('additional_property');

    function syncAdditionalPropertyVisibility() {
        if (buyerTypeEl.value === 'first_time_buyer') {
            hide(additionalPropertyGroup);
            additionalPropertyCb.checked = false; // uncheck silently so it never gets submitted
        } else {
            show(additionalPropertyGroup);
        }
    }

    buyerTypeEl.addEventListener('change', syncAdditionalPropertyVisibility);
    syncAdditionalPropertyVisibility(); // run once on page load

    /* ---- Reset UI to fresh state when user edits form after seeing a result ---- */
    form.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', () => {
            clearErrors();
            hide(resultCard);
            hide(loadingCard);
        });
    });

    /* ---- Form submission ---- */
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearErrors();
        hide(resultCard);
        hide(generalError);

        /* Basic client-side presence check before hitting the server */
        const price     = document.getElementById('purchase_price').value.trim();
        const buyerType = document.getElementById('buyer_type').value;
        let clientErrors = false;

        if (price === '' || isNaN(parseFloat(price))) {
            showFieldErrors({ purchase_price: ['Please enter the purchase price.'] });
            clientErrors = true;
        } else if (parseFloat(price) < 0) {
            showFieldErrors({ purchase_price: ['The purchase price cannot be negative.'] });
            clientErrors = true;
        }
        if (!buyerType) {
            showFieldErrors({ buyer_type: ['Please select a buyer type.'] });
            clientErrors = true;
        }
        if (clientErrors) return;

        /* Show loading, disable button */
        show(loadingCard);
        submitBtn.disabled = true;

        const payload = {
            purchase_price:      price,
            buyer_type:          buyerType,
            additional_property: document.getElementById('additional_property').checked ? '1' : '0',
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            const response = await fetch('{{ route("calculator.calculate") }}', {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            hide(loadingCard);
            submitBtn.disabled = false;

            if (!response.ok || !data.success) {
                if (data.errors) {
                    showFieldErrors(data.errors);
                } else {
                    generalError.textContent = 'Something went wrong. Please try again.';
                    show(generalError);
                }
                return;
            }

            renderResult(data);

        } catch (err) {
            hide(loadingCard);
            submitBtn.disabled = false;
            generalError.textContent = 'Could not reach the server. Please check your connection and try again.';
            show(generalError);
        }
    });

    /* ---- Render result ---- */
    function renderResult(data) {
        const r = data.result;

        /* Notices */
        hide(ftbWithdrawn);
        hide(ftbOverridden);

        if (data.ftb_overridden) {
            show(ftbOverridden);
        } else if (r.ftb_relief_withdrawn) {
            show(ftbWithdrawn);
        }

        /* Summary boxes */
        text(document.getElementById('res-price'), formatPounds(r.purchase_price_pounds));
        const totalEl = document.getElementById('res-total');
        text(totalEl, formatPounds(r.total_tax_pounds));
        totalEl.className = 'value' + (r.total_tax_pence === 0 ? ' zero' : '');
        text(document.getElementById('res-rate'), r.effective_rate_pct + '%');

        /* Scenario label */
        text(
            document.getElementById('res-scenario-label'),
            scenarioLabel(r.scenario, r.ftb_relief_withdrawn)
        );

        /* Breakdown rows */
        breakdownBody.innerHTML = '';
        r.bands.forEach(band => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    ${escHtml(band.description)}
                    <span class="rate-badge">${band.rate_pct}%</span>
                </td>
                <td class="amount">£${escHtml(band.taxable_pounds)}</td>
                <td class="amount">£${escHtml(band.tax_pounds)}</td>
            `;
            breakdownBody.appendChild(tr);
        });

        /* Footer total */
        text(document.getElementById('res-total-foot'), '£' + r.total_tax_pounds);

        show(resultCard);

        /* Smooth scroll to result */
        resultCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
</script>

@endsection
