<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_payout_accounts', function (Blueprint $table) {
            $table->id();
            // One payout account per user keeps cashback routing simple.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->default('flutterwave');
            $table->string('bank_code');
            $table->string('account_number');
            $table->string('account_name')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payout_accounts');
    }
};
