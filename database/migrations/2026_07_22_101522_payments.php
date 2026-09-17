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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->foreignUuid('user_id')
                ->constrained('app_users')
                ->cascadeOnDelete();

            $table->string('reference')->unique();

            $table->string('access_code')->nullable();

            $table->text('payment_url')->nullable();

            $table->decimal('amount', 18, 2);

            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED'
            ])->default('PENDING');

            // gateway response
            $table->string('transaction_reference')->nullable();

            $table->string('gateway_reference')->nullable();

            $table->json('response')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
