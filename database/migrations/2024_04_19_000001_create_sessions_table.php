<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('topic')->nullable();
            $table->smallInteger('duration_minutes');
            $table->timestamps();
            
            $table->index(['course_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
