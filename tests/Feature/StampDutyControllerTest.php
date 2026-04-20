<?php

namespace Tests\Feature;

use Tests\TestCase;

class StampDutyControllerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // GET /
    // -------------------------------------------------------------------------

    public function test_home_page_returns_200(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_home_page_renders_calculator_view(): void
    {
        $this->get('/')->assertSee('Stamp Duty');
    }

    // -------------------------------------------------------------------------
    // POST /calculate — happy paths
    // -------------------------------------------------------------------------

    /**
     * HMRC example: £295,000 standard buyer → £4,750 tax (1.61% effective rate).
     */
    public function test_standard_buyer_295k_returns_correct_tax(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '295000',
            'buyer_type'          => 'standard',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'result'  => [
                         'scenario'         => 'standard',
                         'total_tax_pence'  => 475000,
                         'total_tax_pounds' => '4,750.00',
                     ],
                 ]);
    }

    public function test_response_includes_required_keys(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '295000',
            'buyer_type'          => 'standard',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'ftb_overridden',
                     'result' => [
                         'scenario',
                         'ftb_relief_withdrawn',
                         'total_tax_pence',
                         'total_tax_pounds',
                         'effective_rate_pct',
                         'bands',
                     ],
                 ]);
    }

    /**
     * HMRC example: £500,000 FTB → 0% on first £300k, 5% on next £200k = £10,000.
     */
    public function test_first_time_buyer_500k_returns_correct_tax(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '500000',
            'buyer_type'          => 'first_time_buyer',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'result' => [
                         'scenario'        => 'first_time_buyer',
                         'total_tax_pence' => 1000000,
                         'total_tax_pounds'=> '10,000.00',
                     ],
                 ]);
    }

    public function test_first_time_buyer_under_300k_pays_zero(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '250000',
            'buyer_type'          => 'first_time_buyer',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'result' => ['total_tax_pence' => 0],
                 ]);
    }

    public function test_additional_property_scenario_is_returned(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '250000',
            'buyer_type'          => 'standard',
            'additional_property' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'result' => ['scenario' => 'additional_property'],
                 ]);
    }

    public function test_zero_purchase_price_returns_zero_tax(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '0',
            'buyer_type'          => 'standard',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'result' => ['total_tax_pence' => 0],
                 ]);
    }

    public function test_bands_array_is_populated(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '300000',
            'buyer_type'          => 'standard',
            'additional_property' => false,
        ]);

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertIsArray($data['result']['bands']);
        $this->assertNotEmpty($data['result']['bands']);
    }

    // -------------------------------------------------------------------------
    // FTB relief cap & override flags
    // -------------------------------------------------------------------------

    public function test_ftb_above_500k_cap_withdraws_relief(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '600000',
            'buyer_type'          => 'first_time_buyer',
            'additional_property' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'result' => [
                         'ftb_relief_withdrawn' => true,
                         'scenario'             => 'standard',
                     ],
                 ]);
    }

    public function test_ftb_overridden_flag_when_ftb_plus_additional_property(): void
    {
        $response = $this->postJson('/calculate', [
            'purchase_price'      => '300000',
            'buyer_type'          => 'first_time_buyer',
            'additional_property' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['ftb_overridden' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /calculate — validation failures (all must return 422)
    // -------------------------------------------------------------------------

    public function test_missing_purchase_price_returns_422(): void
    {
        $this->postJson('/calculate', ['buyer_type' => 'standard'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['purchase_price']);
    }

    public function test_non_numeric_purchase_price_returns_422(): void
    {
        $this->postJson('/calculate', ['purchase_price' => 'abc', 'buyer_type' => 'standard'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['purchase_price']);
    }

    public function test_negative_purchase_price_returns_422(): void
    {
        $this->postJson('/calculate', ['purchase_price' => '-1', 'buyer_type' => 'standard'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['purchase_price']);
    }

    public function test_missing_buyer_type_returns_422(): void
    {
        $this->postJson('/calculate', ['purchase_price' => '250000'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['buyer_type']);
    }

    public function test_invalid_buyer_type_returns_422(): void
    {
        $this->postJson('/calculate', ['purchase_price' => '250000', 'buyer_type' => 'corporate'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['buyer_type']);
    }

    public function test_purchase_price_too_large_returns_422(): void
    {
        $this->postJson('/calculate', ['purchase_price' => '999999999999', 'buyer_type' => 'standard'])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['purchase_price']);
    }
}
