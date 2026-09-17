<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('name');
            $table->enum('environment', ['TEST', 'LIVE'])->default('TEST');
            $table->string('key_prefix', 20); // fpk_test_ or fpk_live_
            $table->string('key_hash', 64); // SHA256 of full key
            $table->jsonb('scopes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('key_hash');
        });

        Schema::create('webhook_endpoints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('url');
            $table->jsonb('events'); // array of event types
            $table->string('signing_secret', 64);
            $table->enum('environment', ['TEST', 'LIVE'])->default('TEST');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('ip_whitelist_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('ip_cidr', 50);
            $table->string('label')->nullable();
            $table->enum('environment', ['TEST', 'LIVE'])->default('TEST');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('settlement_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->unique()->index();
            $table->string('bank_code', 10);
            $table->string('bank_name');
            $table->string('account_number', 20);
            $table->string('account_name');
            $table->string('currency', 3)->default('NGN');
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_accounts');
        Schema::dropIfExists('ip_whitelist_rules');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('api_keys');
    }
};
