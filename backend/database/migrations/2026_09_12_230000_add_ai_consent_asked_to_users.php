<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            /*
             * „Nu am întrebat încă” și „a refuzat” sunt stări diferite.
             *
             * Fără separarea asta, `ai_consent_at = null` ar însemna amândouă,
             * iar cine a refuzat o dată ar fi întrebat la fiecare căutare —
             * exact comportamentul care transformă un consimțământ într-un
             * dark pattern.
             */
            $table->timestamp('ai_consent_asked_at')->nullable()->after('ai_consent_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('ai_consent_asked_at'));
    }
};
