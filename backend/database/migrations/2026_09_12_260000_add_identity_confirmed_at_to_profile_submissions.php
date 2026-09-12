<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_submissions', function (Blueprint $table) {
            // Proprietarul a ales cine este omul care a completat. Doar o legătură
            // confirmată aduce date într-un contact care exista deja; o completare
            // ulterioară a aceluiași om se leagă apoi singură (docs/00 § D-021, S9.8).
            //
            // O completare cu `accepted_at` gol așteaptă această alegere.
            $table->timestamp('identity_confirmed_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('profile_submissions', function (Blueprint $table) {
            $table->dropColumn('identity_confirmed_at');
        });
    }
};
