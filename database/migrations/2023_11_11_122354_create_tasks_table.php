<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('task_uuid');
            $table->foreignId('creator_id')->constrained("users")->onDelete('cascade');
            $table->string('title');
            $table->longText('description');
            $table->boolean('is_completed')->default(false);
            $table->dateTime("due_date")->nullable();
            $table->boolean("private")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
