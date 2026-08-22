<?php

namespace App\Contracts\Payments;

use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Payload\PaymentResult;

interface CashbackPaymentProvider
{
    /**
     * Send or simulate a cashback payout and normalize the provider response.
     */
    public function sendCashback(CashbackPaymentRequest $request): PaymentResult;
}
