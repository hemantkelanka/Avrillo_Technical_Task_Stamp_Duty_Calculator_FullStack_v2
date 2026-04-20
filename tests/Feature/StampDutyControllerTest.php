<?php

namespace Tests\Feature;

use Tests\TestCase;

class StampDutyControllerTest extends TestCase
{
    // -----------------------------------------------------------------------
    // GET /
    // -----------------------------------------------------------------------

    public function test_home_returns_200_with_form(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Stamp Duty');
        $response->assertSee('purchase_type');
        $response->assertSee('price');
    }

    // -----------------------------------------------------------------------
    // POST /calculate — happy path
    // -----------------------------------------------------------------------

    public function test_calculate_standard_returns_200_with_results(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '300000',
            'purchase_type' => 'standard',
        ]);

        $response->assertStatus(200);
        $response->assertSee('5,000.00');  // £5,000 tax
        $response->assertSee('1.67');      // effective rate
    }

    public function test_calculate_first_time_buyer_returns_200(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '300000',
            'purchase_type' => 'first_time_buyer',
        ]);

        $response->assertStatus(200);
        $response->assertSee('0.00');
    }

    public function test_calculate_additional_property_returns_200(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '100000',
            'purchase_type' => 'additional_property',
        ]);

        $response->assertStatus(200);
        $response->assertSee('3,000.00');
    }

    public function test_ftb_above_500k_shows_notice(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '600000',
            'purchase_type' => 'first_time_buyer',
        ]);

        $response->assertStatus(200);
        $response->assertSee('First-time buyer relief does not apply');
    }

    // -----------------------------------------------------------------------
    // POST /calculate — validation failures
    // -----------------------------------------------------------------------

    public function test_missing_price_fails_validation(): void
    {
        $response = $this->post('/calculate', [
            'purchase_type' => 'standard',
        ]);

        // Either redirects with errors (web) or returns 422.
        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            'Expected 302 or 422, got ' . $response->getStatusCode()
        );
    }

    public function test_invalid_purchase_type_fails_validation(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '300000',
            'purchase_type' => 'invalid_type',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            'Expected 302 or 422, got ' . $response->getStatusCode()
        );
    }

    public function test_price_zero_fails_validation(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '0',
            'purchase_type' => 'standard',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            'Expected 302 or 422, got ' . $response->getStatusCode()
        );
    }

    public function test_negative_price_fails_validation(): void
    {
        $response = $this->post('/calculate', [
            'price'         => '-1000',
            'purchase_type' => 'standard',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            'Expected 302 or 422, got ' . $response->getStatusCode()
        );
    }

    public function test_non_numeric_price_fails_validation(): void
    {
        $response = $this->post('/calculate', [
            'price'         => 'not-a-number',
            'purchase_type' => 'standard',
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [302, 422]),
            'Expected 302 or 422, got ' . $response->getStatusCode()
        );
    }
}
