<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotent_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key')->unique();
            $table->uuid('user_id')->nullable();
            $table->string('request_path');
            $table->string('request_method');
            $table->integer('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->enum('status', ['PROCESSING', 'COMPLETED'])->default('PROCESSING');
            $table->timestamps();

            $table->index(['idempotency_key', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotent_requests');
    }
};
