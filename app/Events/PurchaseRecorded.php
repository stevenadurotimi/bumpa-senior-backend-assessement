<?php

namespace App\Events;

use App\Models\Purchase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Purchase $purchase) {}
}
