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
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->string('nibss_session_id')->nullable();
            $table->string('nibss_retrieval_token')->nullable();
            $table->string('bvn_consent_status')->nullable();
            $table->timestamp('consent_expiry_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kyc_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'nibss_session_id',
                'nibss_retrieval_token',
                'bvn_consent_status',
                'consent_expiry_time',
            ]);
        });
    }
};
