<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occasions', function (Blueprint $table) {
            $table->id();

            // user_id denormalizat: dashboard-ul interoghează „ocaziile mele
            // din următoarele N zile” fără join pe people.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['birthday', 'name_day', 'anniversary', 'holiday', 'custom']);
            $table->string('title', 120)->nullable();     // doar pentru 'custom'

            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->unsignedSmallInteger('year')->nullable();

            // Sfântul care a generat onomastica, dacă e cazul.
            $table->foreignId('name_day_id')->nullable()->constrained()->nullOnDelete();

            $table->string('source', 32);
            $table->decimal('confidence', 3, 2)->default(1.00);

            // Onomasticile deduse se confirmă de utilizator înainte de a genera
            // notificări. Un reminder greșit e mai rău decât niciunul.
            $table->timestamp('confirmed_at')->nullable();
            // „Nu sărbătorește” — păstrăm respingerea, ca să nu re-propunem.
            $table->timestamp('rejected_at')->nullable();

            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->unique(['person_id', 'type', 'month', 'day']);
            $table->index(['user_id', 'month', 'day']);
            $table->index(['user_id', 'confirmed_at', 'rejected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occasions');
    }
};
