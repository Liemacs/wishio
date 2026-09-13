<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_submissions', function (Blueprint $table) {
            // Când i-am spus proprietarului că această completare așteaptă „cine
            // este?”. O completare deja anunțată nu mai declanșează altă notificare.
            $table->timestamp('owner_notified_at')->nullable()->after('identity_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('profile_submissions', function (Blueprint $table) {
            $table->dropColumn('owner_notified_at');
        });
    }
};
