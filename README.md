# Stamp Duty Land Tax (SDLT) Calculator

A Laravel 11 / PHP 8.2 web application that calculates UK Stamp Duty Land Tax for residential property purchases in England. Built as a technical task for Avrillo.

---

## Quick Start

Everything below assumes a fresh clone. You should be at the browser in under 5 minutes.

### 1. Clone & install dependencies

```bash
git clone <repo-url> sdlt-calculator
cd sdlt-calculator
composer install
```

### 2. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

No database is needed — the calculator is entirely stateless.

### 3. Start the development server

```bash
php artisan serve
```

Open **http://127.0.0.1:8000** in your browser. The calculator loads immediately.

---

## Running the Tests

```bash
php artisan test
```

Expected output: **38 tests, 76 assertions** — all green.

```
PASS  Tests\Unit\StampDutyServiceTest      (19 tests)
PASS  Tests\Feature\StampDutyControllerTest (17 tests)
PASS  Tests\Unit\ExampleTest               (1 test)
PASS  Tests\Feature\ExampleTest            (1 test)
```

---

## Example Inputs & Expected Outputs

Use these to sanity-check the maths after setup. All figures match the [HMRC SDLT calculator](https://www.tax.service.gov.uk/calculate-stamp-duty-land-tax).

| # | Purchase Price | Buyer Type | Additional Property? | Expected Tax | Effective Rate |
|---|---|---|---|---|---|
| 1 | £295,000 | Standard | No | **£4,750** | 1.61% |
| 2 | £300,000 | First-time buyer | No | **£0** | 0.00% |
| 3 | £500,000 | First-time buyer | No | **£10,000** | 2.00% |
| 4 | £295,000 | Standard | Yes (additional property) | **£19,500** | 6.61% |

**Example 1 breakdown** (standard buyer, £295,000):
- £0 – £125,000 @ 0% = £0
- £125,000 – £250,000 @ 2% = £2,500
- £250,000 – £295,000 @ 5% = £2,250
- **Total: £4,750**

**Example 3 breakdown** (first-time buyer, £500,000):
- £0 – £300,000 @ 0% = £0
- £300,000 – £500,000 @ 5% = £10,000
- **Total: £10,000**

> **Note:** First-time buyer relief is all-or-nothing at the £500,000 cap. A property at £500,001 gets no relief at all and is taxed at standard rates in full.

---

## Features

- Three buyer scenarios: **standard**, **first-time buyer**, **additional property**
- HMRC-accurate rates (5% additional property surcharge, raised October 2024)
- Full band-by-band breakdown returned in every response
- AJAX form — instant results, no page reload
- No database, no auth, no build step required

---

## Project Structure

```
app/
  Http/Controllers/StampDutyController.php  — validates input, calls service, returns JSON
  Services/StampDutyService.php             — pure calculation engine (no side effects)
config/
  stamp_duty.php                            — single source of truth for all rate bands
resources/views/
  calculator.blade.php                      — AJAX form + results table
  layouts/app.blade.php                     — base layout (self-contained CSS, no npm needed)
tests/
  Unit/StampDutyServiceTest.php             — 19 unit tests covering all scenarios & boundaries
  Feature/StampDutyControllerTest.php       — 17 feature tests covering HTTP layer & validation
```

---

## Key Design Decisions

- **Pence arithmetic** — all amounts are integers in pence internally to avoid floating-point drift
- **Config-driven rates** — `config/stamp_duty.php` is the only place rates live; no magic numbers in logic
- **FTB cap is all-or-nothing** — above £500,000 relief is fully withdrawn; standard rates apply in full
- **Additional property always overrides FTB** — owning another property disqualifies first-time buyer status in law, so the surcharge always wins
