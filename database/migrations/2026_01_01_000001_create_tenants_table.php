<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->enum('status', ['SANDBOX', 'ACTIVE', 'SUSPENDED', 'OFFBOARDED'])->default('SANDBOX');
            $table->enum('plan', ['STARTER', 'GROWTH', 'ENTERPRISE'])->default('STARTER');
            $table->enum('kyb_status', ['NOT_STARTED', 'DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'])->default('NOT_STARTED');
            $table->jsonb('branding')->nullable();
            $table->jsonb('feature_flags')->nullable();
            $table->jsonb('whitelabel_config')->nullable();
            $table->timestamp('go_live_requested_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
