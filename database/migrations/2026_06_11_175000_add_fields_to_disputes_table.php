<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->string('ticket_number')->nullable()->unique()->after('id');
            $table->boolean('refund_processed')->default(false)->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_number',
                'refund_processed'
            ]);
        });
    }
};
