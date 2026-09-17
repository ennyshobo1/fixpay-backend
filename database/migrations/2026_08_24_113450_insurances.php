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
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('user_id')
                ->constrained('app_users')
                ->cascadeOnDelete();

            // Customer information
            $table->string('email');
            $table->string('phone');

            // Insurance product
            $table->string('product_type');
            $table->string('product_variant');

            // Motor insurance details
            $table->string('nin')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('plate_number')->nullable();

            // Insurance provider information
            $table->string('reference')->unique();
            $table->uuid('customer_id')->nullable();

            $table->decimal('premium_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('NGN');

            $table->string('status')->default('pending');

            $table->text('checkout_url')->nullable();
            
            $table->timestamp('expires_at')->nullable();

            // Callback
            $table->text('callback_url')->nullable();

            // Payment / completion information
            $table->string('payment_reference')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('plate_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
