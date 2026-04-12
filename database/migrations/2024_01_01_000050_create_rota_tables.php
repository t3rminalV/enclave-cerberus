<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rota_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->integer('min_shift_minutes')->default(120);  // 2 hours
            $table->integer('max_shift_minutes')->default(480);  // 8 hours
            $table->integer('min_rest_minutes')->default(480);   // 8 hours between shifts
            $table->integer('max_shifts_per_volunteer')->default(3);
            $table->timestamps();

            $table->unique('event_id');
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('min_volunteers')->default(1);
            $table->unsignedInteger('max_volunteers')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rota_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_manual')->default(false); // manually overridden by admin
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shift_id', 'user_id']);
        });

        Schema::create('rota_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('published_by')->constrained('users');
            $table->boolean('is_live')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rota_publications');
        Schema::dropIfExists('rota_assignments');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('rota_rules');
    }
};
