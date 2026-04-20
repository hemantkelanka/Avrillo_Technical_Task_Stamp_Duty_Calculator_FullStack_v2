<?php

namespace App\Http\Controllers;

use App\Services\StampDutyCalculatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StampDutyController extends Controller
{
    public function index(): View
    {
        return view('calculator');
    }

    public function calculate(Request $request): View
    {
        $validated = $request->validate([
            'price'         => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'purchase_type' => ['required', 'in:standard,first_time_buyer,additional_property'],
        ]);

        $service = new StampDutyCalculatorService();

        $result = $service->calculate(
            (float) $validated['price'],
            $validated['purchase_type']
        );

        $ftbRelief = $validated['purchase_type'] === 'first_time_buyer';
        $ftbLimitExceeded = $ftbRelief && ((float) $validated['price']) > 500000;

        return view('calculator', [
            'result'           => $result,
            'price'            => $validated['price'],
            'purchase_type'    => $validated['purchase_type'],
            'ftbLimitExceeded' => $ftbLimitExceeded,
        ]);
    }
}
