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
        Schema::create('wallet_transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignUuid('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                ->constrained('app_users')
                ->cascadeOnDelete();

            $table->enum('type', [
                'CREDIT',
                'DEBIT'
            ]);

            $table->decimal('amount', 18, 2);

            $table->string('reference')->unique();

            $table->string('description')->nullable();

            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED'
            ])->default('SUCCESS');

            $table->decimal('balance_before', 18, 2)->nullable();

            $table->decimal('balance_after', 18, 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

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
