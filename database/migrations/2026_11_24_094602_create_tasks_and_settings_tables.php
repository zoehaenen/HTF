<?php

use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('course')->nullable();
            $table->date('deadline');
            $table->decimal('estimated_hours', 5, 2);
            $table->boolean('done')->default(false);
            $table->text('source_text')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique();
            $table->decimal('hours', 4, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('settings');
    }
};
