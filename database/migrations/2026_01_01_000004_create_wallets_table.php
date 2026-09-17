<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique()->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->bigInteger('balance_kobo')->default(0);
            $table->bigInteger('ledger_balance_kobo')->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['ACTIVE', 'FROZEN', 'CLOSED'])->default('ACTIVE');
            $table->string('virtual_account_number', 20)->nullable();
            $table->string('virtual_account_bank', 100)->nullable();
            $table->string('virtual_account_bank_code', 10)->nullable();
            $table->string('virtual_account_reference')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('app_users')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
