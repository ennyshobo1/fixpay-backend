<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favourites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('type'); // e.g. 'bill_payment', 'transfer'
            $table->string('service_id')->nullable(); // e.g. 'airtime', 'data'
            $table->string('service_name')->nullable();
            $table->string('counterparty_name'); // e.g. phone number or bank account name
            $table->string('description')->nullable();
            $table->integer('amount_kobo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favourites');
    }
};
