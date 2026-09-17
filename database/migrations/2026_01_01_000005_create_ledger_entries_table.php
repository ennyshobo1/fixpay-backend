<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wallet_id')->index();
            $table->enum('entry_type', ['DEBIT', 'CREDIT']);
            $table->bigInteger('amount_kobo');
            $table->bigInteger('running_balance_kobo');
            $table->string('correlation_id')->index(); // payment/transfer reference
            $table->string('description')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->timestamp('created_at'); // no updated_at — append-only

            $table->foreign('wallet_id')->references('id')->on('wallets')->cascadeOnDelete();
        });

        if (config('database.default') !== 'sqlite') {
            // Prevent updates on this table via DB trigger
            // DB::statement("
            //     CREATE OR REPLACE FUNCTION prevent_ledger_update()
            //     RETURNS TRIGGER AS \$\$
            //     BEGIN
            //         RAISE EXCEPTION 'Ledger entries are immutable';
            //     END;
            //     \$\$ LANGUAGE plpgsql;
            // ");

            // DB::statement("
            //     CREATE TRIGGER ledger_entries_no_update
            //     BEFORE UPDATE ON ledger_entries
            //     FOR EACH ROW EXECUTE FUNCTION prevent_ledger_update();
            // ");
        }
    }

    public function down(): void
    {
        // if (config('database.default') !== 'sqlite') {
        //     DB::statement('DROP TRIGGER IF EXISTS ledger_entries_no_update ON ledger_entries');
        //     DB::statement('DROP FUNCTION IF EXISTS prevent_ledger_update');
        // }
        Schema::dropIfExists('ledger_entries');
        
    }
};
