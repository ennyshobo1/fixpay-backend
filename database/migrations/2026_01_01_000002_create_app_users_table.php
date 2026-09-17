<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('phone', 20)->unique();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password_hash');
            $table->string('pin_hash')->nullable();
            $table->enum('kyc_status', ['UNVERIFIED', 'PENDING', 'VERIFIED', 'FAILED'])->default('UNVERIFIED');
            $table->unsignedTinyInteger('tier')->default(1);
            $table->enum('status', ['ACTIVE', 'SUSPENDED', 'OFFBOARDED'])->default('ACTIVE');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
