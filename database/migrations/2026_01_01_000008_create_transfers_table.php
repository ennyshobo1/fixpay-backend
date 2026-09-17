<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('wallet_id')->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('transfer_reference')->unique();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('transfer_type', ['BANK', 'WALLET']);
            $table->bigInteger('amount_kobo');
            $table->bigInteger('fee_kobo')->default(0);
            $table->string('narration')->nullable();
            // Bank transfer fields
            $table->string('account_number', 20)->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('paystack_transfer_code')->nullable();
            $table->string('paystack_recipient_code')->nullable();
            // Wallet transfer fields
            $table->uuid('recipient_user_id')->nullable()->index();
            $table->uuid('recipient_wallet_id')->nullable()->index();
            // Status
            $table->enum('status', ['INITIATED', 'PROCESSING', 'COMPLETED', 'FAILED', 'REVERSED'])->default('INITIATED');
            $table->jsonb('provider_response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('app_users');
            $table->foreign('wallet_id')->references('id')->on('wallets');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
