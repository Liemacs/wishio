<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            // Fără asta, jobul orar ar trimite același digest de câte ori
            // rulează în intervalul potrivit.
            $table->timestamp('last_digest_sent_at')->nullable()->after('email_digest');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn('last_digest_sent_at');
        });
    }
};
