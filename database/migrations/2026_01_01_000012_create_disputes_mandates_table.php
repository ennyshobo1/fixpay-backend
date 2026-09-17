<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('related_payment_id')->nullable(); // vtpass_payment or transfer reference
            $table->string('related_payment_type', 20)->nullable(); // VTPASS, TRANSFER
            $table->enum('category', ['WRONG_AMOUNT', 'NOT_DELIVERED', 'UNAUTHORIZED', 'DUPLICATE', 'OTHER']);
            $table->text('reason');
            $table->enum('status', ['OPEN', 'UNDER_REVIEW', 'RESOLVED', 'REJECTED'])->default('OPEN');
            $table->text('resolution_notes')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->timestamp('sla_deadline')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('app_users');
        });

        Schema::create('nibss_mandates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('mandate_reference')->unique();
            $table->string('description')->nullable();
            $table->bigInteger('amount_kobo');
            $table->string('frequency', 20); // DAILY, WEEKLY, MONTHLY, QUARTERLY, ANNUALLY
            $table->string('beneficiary_bank_code', 10)->nullable();
            $table->string('beneficiary_account_number', 20)->nullable();
            $table->string('authorization_url')->nullable();
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['PENDING_AUTH', 'ACTIVE', 'PAUSED', 'CANCELLED', 'EXPIRED'])->default('PENDING_AUTH');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('app_users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nibss_mandates');
        Schema::dropIfExists('disputes');
    }
};
