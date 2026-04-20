Time Spent (~2 hours total)
===================

Reading the brief & checking current HMRC rates: 20 mins

Laravel scaffolding (config, services): 15 mins

Controller, routes & UI: 30 mins

Tests (writing & debugging): 35 mins

UI tweaks & fixing config bugs: 20 mins

Writing documentation: 15 mins

===================
AI Usage
===================

Tool: GitHub Copilot (Claude Sonnet) via VS Code.

I used it mostly as a fast scaffolder to generate the boilerplate for the config, service, controller, views, and tests. I deliberately worked layer-by-layer rather than asking it to generate the whole app at once so I could control the architecture.

===================
Where I had to step in and fix the AI:
===================

The AI messed up the boundaries in config/stamp_duty.php. It tried to set the bands using to + 1 for the next starting point (e.g., from => 12_500_001 for the 2% band). Since the service calculates the taxable slice using min(price, band_to) - band_from, that +1 meant every boundary was off by exactly one penny. For example, a £125,000 purchase would have been taxed 2p short.

I caught this while doing a manual trace of the £295k HMRC example. I rewrote the config so the from values perfectly match the previous band's to value, and then wrote tests against those corrected expectations to ensure it matched HMRC to the penny.

===================
Verifying the Math
===================


The Source of Truth: I used the official HMRC SDLT calculator to grab my expected outputs before writing the assertions, rather than writing code and just assuming the output was correct.

Test Cases: Used HMRC's own published examples:

£295,000 standard -> £4,750

£500,000 first-time buyer -> £10,000

Testing Boundaries: Checked these manually, then locked them in with automated tests:

£125,000 (nil band ceiling) -> £0

£250,000 (2% band ceiling) -> £2,500

£925,000 (5% band ceiling) -> £36,250

£1,500,000 (10% band ceiling) -> £93,750

£500,000 FTB (exactly at cap) -> £10,000 (FTB rates apply)

£500,001 FTB (1p over cap) -> £10,000.05 (reverts back to standard rates)

Rate Updates: The repo instructions mentioned a 3% surcharge for additional properties. I noticed this was actually outdated—it bumped to 5% as of Oct 2024. I used the updated 5% rate for the logic.

FTB Cliff-edge: Double-checked the rules to confirm that FTB relief completely drops off over £500k (there is no partial relief). Tested both exactly on the boundary and 1p over to make sure it handles the cliff-edge properly.

===================
If I had another hour
===================

Add PROCESS.md earlier in the flow. Trying to remember exactly when I made certain judgment calls at the end is annoying; better to log them as I go.

Extract the validation out of the controller into a dedicated FormRequest class to clean things up and separate concerns.

Improve accessibility: add an ARIA live region for the results so screen readers actually announce the output, and manage focus so it shifts to the result card after calculating.

Add explicit tests for the bandDescription() strings (right now the tests cover the math and band shapes, but not the human-readable UI labels).

Set up a quick GitHub Actions CI workflow to run php artisan test on push, and add a .env.example note.

