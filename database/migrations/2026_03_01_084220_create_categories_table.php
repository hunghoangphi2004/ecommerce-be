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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('title', 150);

            $table->string('slug', 160)->unique();

            $table->text('description')->nullable();

            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->text('thumbnail')->nullable();

            $table->boolean('status')->default(true);

            $table->integer('position')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
