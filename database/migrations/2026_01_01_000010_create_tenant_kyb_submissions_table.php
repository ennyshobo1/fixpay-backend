<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_kyb_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->unique()->index();
            $table->string('business_name')->nullable();
            $table->string('cac_number', 30)->nullable();
            $table->string('tin_number', 30)->nullable();
            $table->enum('business_type', ['SOLE_PROP', 'PARTNERSHIP', 'LTD', 'PLC', 'NGO', 'OTHER'])->nullable();
            $table->string('business_address')->nullable();
            $table->string('website_url')->nullable();
            $table->jsonb('directors')->nullable(); // [{name, bvn, nin, dob, phone}]
            $table->jsonb('document_urls')->nullable(); // {cac_doc, memart, utility_bill}
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'])->default('DRAFT');
            $table->text('review_notes')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_kyb_submissions');
    }
};
