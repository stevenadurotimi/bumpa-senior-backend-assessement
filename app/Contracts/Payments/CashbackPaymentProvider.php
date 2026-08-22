<?php

namespace App\Contracts\Payments;

use App\Payments\CashbackPaymentRequest;
use App\Payments\PaymentResult;

interface CashbackPaymentProvider
{
    public function sendCashback(CashbackPaymentRequest $request): PaymentResult;
}
