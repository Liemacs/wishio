<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 2)->default('ro')->after('email');
            $table->string('country_code', 2)->default('MD')->after('locale');
            $table->string('timezone', 48)->default('Europe/Chisinau')->after('country_code');

            // Calendarul implicit pentru onomastici. Se alege din limbă la
            // înregistrare (ro -> stil nou, ru -> stil vechi) și poate fi
            // schimbat. Vezi docs/15-onomastici.md § 2.
            $table->enum('name_day_calendar', ['orthodox_new', 'orthodox_old', 'catholic'])
                ->default('orthodox_new')->after('timezone');

            $table->date('birth_date')->nullable()->after('name_day_calendar');
            $table->char('phone_hash', 64)->nullable()->after('birth_date');

            $table->index('phone_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone_hash']);
            $table->dropColumn([
                'locale', 'country_code', 'timezone',
                'name_day_calendar', 'birth_date', 'phone_hash',
            ]);
        });
    }
};
