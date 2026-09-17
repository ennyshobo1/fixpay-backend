<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vtpass_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('wallet_id')->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('payment_reference')->unique();
            $table->string('idempotency_key')->unique()->nullable();
            $table->string('service_id', 100);
            $table->string('variation_code', 100)->nullable();
            $table->bigInteger('amount_kobo');
            $table->bigInteger('fee_kobo')->default(0);
            $table->string('phone', 20)->nullable();
            $table->string('billersCode')->nullable(); // meter/smartcard number
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('response_payload')->nullable();
            $table->enum('payment_status', ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REVERSED'])->default('PENDING');
            $table->string('provider_code', 20)->nullable(); // VTPass response code
            $table->string('processor_id', 100)->nullable();
            $table->bigInteger('processor_fee_kobo')->default(0);
            $table->string('token')->nullable(); // electricity token
            $table->string('units')->nullable(); // electricity units
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('app_users');
            $table->foreign('wallet_id')->references('id')->on('wallets');
        });

        Schema::create('payment_journal_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payment_id')->index();
            $table->string('step', 50); // INITIATED, WALLET_DEBITED, PROVIDER_CALLED, COMPLETED, FAILED
            $table->string('status', 20);
            $table->jsonb('metadata')->nullable();
            $table->string('actor', 50)->nullable(); // system, webhook, user
            $table->timestamp('created_at');

            $table->foreign('payment_id')->references('id')->on('vtpass_payments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_journal_entries');
        Schema::dropIfExists('vtpass_payments');
    }
};
