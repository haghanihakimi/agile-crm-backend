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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('file_uuid');
            $table->foreignId('uploaded_by')->constrained("users")->onDelete('cascade');
            // $table->nullableMorphs('fileable');
            $table->morphs('fileable');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_format');
            $table->string('file_type');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('used_for');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
