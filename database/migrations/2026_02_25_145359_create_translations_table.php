<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')
                ->constrained('translation_keys')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('locale', 10);
            $table->text('content');
            $table->timestamps();

            $table->unique(['translation_key_id', 'locale']);
            $table->index('locale');
            $table->index('updated_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
