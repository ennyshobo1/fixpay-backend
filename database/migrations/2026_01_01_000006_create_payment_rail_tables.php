<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_rail_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable()->index(); // null = global default
            $table->string('payment_method', 50); // VTPASS, PAYSTACK_TRANSFER, etc.
            $table->string('processor_id', 100); // plugin/adapter identifier
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('enabled')->default(true);
            $table->boolean('maintenance')->default(false);
            $table->jsonb('config_json')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['payment_method', 'enabled', 'priority']);
        });

        Schema::create('processor_fee_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('config_id')->index();
            $table->bigInteger('min_amount_kobo')->default(0);
            $table->bigInteger('max_amount_kobo')->nullable(); // null = no max
            $table->decimal('percentage_fee', 5, 4)->default(0); // e.g. 0.015 = 1.5%
            $table->bigInteger('flat_fee_kobo')->default(0);
            $table->bigInteger('cap_kobo')->nullable(); // max fee cap
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->timestamps();

            $table->foreign('config_id')->references('id')->on('payment_rail_configs')->cascadeOnDelete();
        });

        Schema::create('payment_rail_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('entity_type', 50);
            $table->uuid('entity_id');
            $table->string('action', 20); // CREATE, UPDATE, DELETE
            $table->uuid('admin_id')->nullable();
            $table->jsonb('old_json')->nullable();
            $table->jsonb('new_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_rail_audit_logs');
        Schema::dropIfExists('processor_fee_schedules');
        Schema::dropIfExists('payment_rail_configs');
    }
};
