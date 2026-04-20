<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StampDutyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

class StampDutyController extends Controller
{
    private StampDutyService $service;

    public function __construct(StampDutyService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the calculator page.
     */
    public function index(): View
    {
        return view('calculator');
    }

    /**
     * Handle the AJAX calculation request.
     *
     * Accepts a JSON or form POST and returns a JSON response.
     * Input validation happens here so the pure service never sees dirty data.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_price'     => ['required', 'numeric', 'min:0', 'max:100000000000'], // max £1 billion
            'buyer_type'         => ['required', 'in:standard,first_time_buyer'],
            'additional_property'=> ['sometimes', 'boolean'],
        ], [
            'purchase_price.required' => 'Please enter the purchase price.',
            'purchase_price.numeric'  => 'The purchase price must be a number.',
            'purchase_price.min'      => 'The purchase price cannot be negative.',
            'purchase_price.max'      => 'The purchase price seems too large. Please check the value.',
            'buyer_type.required'     => 'Please select a buyer type.',
            'buyer_type.in'           => 'The selected buyer type is not valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

        // Convert pounds (user input, may have decimals) to whole pence.
        // We round to avoid sub-penny floating point drift before casting.
        $pricePence = (int) round((float) $validated['purchase_price'] * 100);

        $buyerType          = $validated['buyer_type'];
        $additionalProperty = filter_var(
            $validated['additional_property'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        // Judgement call: if the user ticks "additional property" they cannot
        // also be a first-time buyer — the service enforces this, but we add a
        // clear message in the response so the UI can surface it.
        $ftbOverridden = $additionalProperty && $buyerType === StampDutyService::BUYER_FIRST_TIME;

        try {
            $result = $this->service->calculate($pricePence, $buyerType, $additionalProperty);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'errors'  => ['purchase_price' => [$e->getMessage()]],
            ], 422);
        }

        return response()->json([
            'success'        => true,
            'ftb_overridden' => $ftbOverridden, // UI can show "First-time buyer relief does not apply when buying an additional property"
            'result'         => $result,
        ]);
    }
}
