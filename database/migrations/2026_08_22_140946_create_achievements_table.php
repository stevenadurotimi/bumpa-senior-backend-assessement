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
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            // Group and trigger_type make achievement rules data-driven.
            $table->string('name')->unique();
            $table->string('group')->index();
            $table->string('trigger_type')->index();
            $table->unsignedInteger('threshold');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Prevent duplicate milestone definitions for the same rule.
            $table->unique(['group', 'trigger_type', 'threshold']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
