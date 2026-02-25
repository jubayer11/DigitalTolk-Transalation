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
        Schema::create('translation_key_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translation_key_id')
                ->constrained('translation_keys')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->primary(['translation_key_id', 'tag_id']);
            $table->index(['tag_id', 'translation_key_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_key_tag');
    }
};
