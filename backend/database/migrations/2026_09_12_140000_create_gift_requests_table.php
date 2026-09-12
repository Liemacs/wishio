<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cererile din Faza 0 — validarea concierge (docs/14).
     *
     * Tabel TEMPORAR, cu scop de invatare, nu parte din produs. Contine insa
     * date personale reale (contact, descrierea unei terte persoane), deci se
     * supune acelorasi reguli ca restul: consimtamant explicit, stergere la
     * cerere, fara date sensibile. Vezi docs/06-privacy-legal.md.
     *
     * Campul `about` este cel mai valoros lucru din toata Faza 0: devine
     * corpusul de intrare pentru prompturile de la S7 (docs/14 § 1).
     */
    public function up(): void
    {
        Schema::create('gift_requests', function (Blueprint $table) {
            $table->id();

            $table->string('locale', 2);                     // ro | ru | en
            $table->string('relationship', 32);              // prieten, parinte, coleg...
            $table->string('age_bracket', 16)->nullable();
            $table->char('recipient_gender', 1)->nullable();

            $table->text('about');                           // text liber — corpusul
            $table->unsignedInteger('budget_min')->nullable();
            $table->unsignedInteger('budget_max')->nullable();

            $table->string('occasion', 32)->nullable();
            $table->date('occasion_date')->nullable();

            $table->string('contact', 190);                  // telegram / whatsapp / email
            $table->string('contact_channel', 16)->nullable();

            // Dovada consimtamantului: ce text a acceptat si cand.
            $table->string('consent_version', 16);
            $table->timestamp('consented_at');

            // Fluxul concierge
            $table->timestamp('answered_at')->nullable();
            $table->text('sent_ideas')->nullable();          // ce i-am trimis
            $table->text('feedback')->nullable();            // ce a raspuns
            $table->boolean('would_buy')->nullable();        // masoara G1
            $table->boolean('clicked_through')->nullable();  // masoara G1
            $table->unsignedSmallInteger('minutes_spent')->nullable();

            $table->string('source', 64)->nullable();        // de unde a venit
            $table->string('ip_hash', 64)->nullable();       // doar hash, pentru rate limit
            $table->timestamps();

            $table->index('locale');
            $table->index('answered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_requests');
    }
};
