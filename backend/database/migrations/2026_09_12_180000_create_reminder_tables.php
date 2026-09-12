<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Cu câte zile înainte vrea să fie anunțat. Vezi scara din docs/09 § 3.
            $table->json('reminder_days')->nullable();        // implicit [7, 3, 1]
            $table->unsignedTinyInteger('preferred_hour')->default(10);

            // Ore de liniște, în fusul utilizatorului. O notificare la 3 dimineața
            // nu e un reminder, e un motiv de dezinstalare.
            $table->unsignedTinyInteger('quiet_from')->default(22);
            $table->unsignedTinyInteger('quiet_to')->default(8);

            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_digest')->default(true);
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 255);
            $table->enum('platform', ['ios', 'android', 'web']);
            $table->string('locale', 2)->default('ro');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index('user_id');
        });

        Schema::create('notifications_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_id')->constrained()->cascadeOnDelete();

            $table->enum('channel', ['push', 'email']);

            // Câte zile înainte de ocazie — determină textul (docs/09 § 3).
            $table->unsignedTinyInteger('days_before');
            // Anul ocaziei pentru care se trimite: împiedică retrimiterea
            // aceluiași reminder la următoarea aniversare.
            $table->unsignedSmallInteger('occasion_year');

            // Limba se fixează LA PLANIFICARE, nu la trimitere: dacă utilizatorul
            // schimbă limba între timp, notificarea deja programată rămâne
            // coerentă cu ce se aștepta.
            $table->string('locale', 2);

            $table->timestamp('scheduled_for');               // UTC
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('failure', 190)->nullable();

            $table->timestamps();

            // O singură notificare per ocazie, prag și an.
            $table->unique(['occasion_id', 'channel', 'days_before', 'occasion_year'], 'notif_unique');
            $table->index(['scheduled_for', 'sent_at']);
            $table->index(['user_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_queue');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('user_settings');
    }
};
