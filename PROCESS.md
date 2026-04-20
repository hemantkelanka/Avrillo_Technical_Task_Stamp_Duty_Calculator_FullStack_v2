# SDLT Calculator — Process & Judgement Calls

## Why work in pence?

Floating-point arithmetic is unreliable for money calculations. For example,
`0.1 + 0.2` in IEEE 754 does not equal `0.3`. By converting the input price to
**integer pence** immediately (`(int) round($price * 100)`) and performing all
arithmetic in pence, we eliminate rounding drift across bands. The final values
stored and displayed are always exact integers in pence, converted back to pounds
only for display.

## Input fields chosen

| Field | Rationale |
|---|---|
| **Property price** | Single monetary input — sufficient to calculate SDLT. No other inputs are required by HMRC's own calculator. |
| **Purchase type** | Three HMRC-defined categories drive materially different tax outcomes, so it is presented as a labelled radio group rather than a checkbox to make the mutual exclusivity clear. |

Address, completion date, and buyer identity details are intentionally excluded:
SDLT liability depends only on price and purchase type, not on those fields.

## First-time buyer (FTB) behaviour above £500,000

HMRC's rules state that FTB relief applies **only** if the purchase price is
**£500,000 or less**. If the price exceeds this threshold, the buyer receives
**no FTB relief at all** and is taxed at standard rates.

Implementation decision: rather than throwing an error or hiding the radio button,
the service silently falls back to standard bands when `price > £500,000` and the
controller passes `$ftbLimitExceeded = true` to the view. The view then displays a
plain-English notice explaining why relief was not applied. This matches the
behaviour of the HMRC SDLT calculator.

## Config structure choice

Rates are stored in `config/stamp_duty.php` rather than the database because:

1. **SDLT rates are set by Parliament** and change infrequently (the last change was
   April 2025). A config file is appropriate for near-static reference data.
2. **No DB dependency** — the task specifies a pure calculation service with no
   side effects. Config files are read-only and available at boot.
3. **Easy to update** — a future rate change is a one-file edit with no migration.
4. **Testable without seeding** — unit tests boot the app and read the same config
   as production; no factory setup needed.

Thresholds in config are stored in **pence** (matching the service's internal
representation) so there is no unit mismatch between config and calculation code.

## Additional property surcharge

The +3% flat surcharge is stored as a single integer in config
(`stamp_duty.additional_property.surcharge`). The service adds this integer to
each standard band rate, keeping the surcharge as a config value rather than
hard-coding it in business logic.
