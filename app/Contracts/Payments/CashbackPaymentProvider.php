<?php

namespace App\Contracts\Payments;

use App\Payments\Payload\CashbackPaymentRequest;
use App\Payments\Payload\PaymentResult;

interface CashbackPaymentProvider
{
    public function sendCashback(CashbackPaymentRequest $request): PaymentResult;
}
