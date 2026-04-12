<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['1', '2'])->default('1');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'stage']);
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'text', 'textarea', 'email', 'phone', 'number',
                'select', 'multiselect', 'radio', 'checkbox',
                'file', 'image', 'date', 'heading', 'paragraph', 'divider'
            ]);
            $table->string('label');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable(); // for select/radio/checkbox
            $table->json('validation_rules')->nullable();
            // File-specific
            $table->json('accepted_file_types')->nullable();
            $table->integer('max_file_size_kb')->nullable();
            $table->integer('max_files')->default(1);
            // Display-only content (for heading/paragraph)
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
