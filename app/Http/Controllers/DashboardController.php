<?php

namespace App\Http\Controllers;

use App\Models\Installment;
use App\Models\MortgageRequest;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $mortgageService;
    protected $paymentService;

    public function __construct(MortgageRequest $mortgageService, PaymentService $paymentService)
    {
        $this->mortgageService = $mortgageService;
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $userId = Auth::id();
        $mortgages = $this->mortgageService->getUserMortgages($userId);
        return view('customer.mortgages.index', compact('mortgages'));
    }

    public function details(MortgageRequest $mortgageRequest)
    {
        $details = $this->mortgageService->getMortgageDetails($mortgageRequest);

        return view('customer.mortgages.details', $details);
    }

    public function installment_details(Installment $installment)
    {
        $installmentDetails = $this->mortgageService->getInstallmentDetails($installment);
        return view('customer.installments.index', compact('installmentDetails'));
    }

    public function installment_payment(MortgageRequest $mortgageRequest)
    {
        $paymentDetails = $this->mortgageService->getInstallmentPaymentDetails($mortgageRequest);
        return view('customer.installments.pay_installment', $paymentDetails);
    }

    public function paymentStoreMidtrans(Request $request)
    {
        try {
            $mortgageRequest = $this->mortgageService->getMortgageReqest($request->input('mortgage_request_id'));

            $snapToken = $this->paymentService->createPayment($mortgageRequest);
            return response()->json(['snap_token' => $snapToken], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment Failed. ' . $e->getMessage()], 500);
        }
    }
}
