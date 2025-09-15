<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\MortgageRequest;

class PaymentService
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    public function createPayment(MortgageRequest $mortgageRequest)
    {
        $sub_total_amount = $mortgageRequest->monthly_amount;
        $insurance = 900000;
        $total_tax_amount = round($sub_total_amount * 0.11);

        $grossAmount = $sub_total_amount + $insurance + $total_tax_amount;

        $params = [
            'transaction_details' => [
                'order_id' => 'ORDER-' . uniqid(),
                'gross_amount' => round($grossAmount),
            ],
            'customer_details' => [
                'first_name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'phone' => auth()->user()->phone,
            ],
            'item_details' => [
                [
                    'id' => $mortgageRequest->id,
                    'price' => $grossAmount,
                    'quantity' => 1,
                    'name' => 'Mortgage Payment for ' . $mortgageRequest->house->name,
                ],
            ],
            'custom_field1' => auth()->id(),
            'custom_field2' => $mortgageRequest->id,
        ];

        return $this->midtransService->createSnapToken($params);
    }

    public function processNotification()
    {
        $notification = $this->midtransService->handleNotification();

        $transactionStatus = $notification['transaction_status'];
        $grossAmount = $notification['gross_amount'];

        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            $mortgageRequestId = $notification['custom_field2'];
            $mortgageRequest = MortgageRequest::find($mortgageRequestId);
            $this->createInstallment($mortgageRequest, $grossAmount);
        }
    }

    public function createInstallment(MortgageRequest $mortgageRequest, $grossAmount)
    {
        $lastInstallment = $mortgageRequest->installments()
            ->where('is_paid', true)
            ->orderBy('no_of_payment', 'desc')
            ->first();

        $previosRemainingLoan = $lastInstallment
            ? $lastInstallment->remaining_loan_amount
            : $mortgageRequest->loan_interest_total_amount;

        $sub_total_amount = $mortgageRequest->monthly_amount;
        $insurance = 900000;
        $total_tax_amount = round($sub_total_amount * 0.11);

        $remainingloan = max($previosRemainingLoan - $sub_total_amount, 0);

        return Installment::create([
            'mortgage_request_id' => $mortgageRequest->id,
            'no_of_payment' => $mortgageRequest->installments()->count() + 1,
            'total_tax_amount' => $total_tax_amount,
            'grand_total_amount' => $grossAmount,
            'sub_total_amount' => $sub_total_amount,
            'insurance_amount' => $insurance,
            'is_paid' => true,
            'payment_type' => 'Midtrans',
            'remaining_loan_amount' => $remainingloan,
        ]);
    }
}
