<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zurückgehaltene Einsendungen. `payload` ist mit dem Schlüssel der Anwendung verschlüsselt.
     */
    public function up(): void
    {
        Schema::create('echto_quarantine', function (Blueprint $table) {
            $table->id();
            $table->string('form', 64);
            $table->string('method', 10);
            $table->string('path');
            $table->text('payload');
            $table->json('reasons');
            $table->string('user_id')->nullable();
            $table->boolean('unreachable')->default(false);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('echto_quarantine');
    }
};
