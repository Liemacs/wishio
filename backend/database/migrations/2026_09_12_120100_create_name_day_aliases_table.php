<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('name_day_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('name_day_id')->constrained()->cascadeOnDelete();

            $table->string('given_name', 100);            // forma afisabila: "Gheorghiță"
            $table->string('normalized', 100);            // "gheorghita" — fara diacritice, lowercase
            $table->char('gender', 1)->nullable();        // m | f | null

            // Limba/scriptul formei. Acelasi sfant are aliasuri si in chirilic.
            $table->enum('script', ['latin', 'cyrillic'])->default('latin');

            // 1.00 forma exacta · 0.80 varianta · 0.60 diminutiv · 0.40 ambiguu
            $table->decimal('confidence', 3, 2)->default(1.00);
            $table->boolean('is_diminutive')->default(false);

            $table->timestamps();

            // Un prenume poate aparea la mai multi sfinti (ex. Ioan are mai multe date).
            $table->index(['normalized', 'gender']);
            $table->unique(['name_day_id', 'normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('name_day_aliases');
    }
};
