<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bestätigung per E-Mail (F-08): bis wann der Link gilt und wohin der Absender danach zurückkann.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('echto_quarantine', function (Blueprint $table) {
            $table->timestamp('confirm_until')->nullable()->after('unreachable');
            $table->string('return_url', 2000)->nullable()->after('confirm_until');
        });
    }

    public function down(): void
    {
        Schema::table('echto_quarantine', function (Blueprint $table) {
            $table->dropColumn(['confirm_until', 'return_url']);
        });
    }
};
