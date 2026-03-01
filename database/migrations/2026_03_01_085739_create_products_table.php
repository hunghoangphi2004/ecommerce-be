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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            $table->string('title', 200);
            $table->string('slug', 220)->unique();

            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);

            $table->unsignedInteger('discount_percentage')->default(0);

            $table->unsignedInteger('stock')->default(0);

            $table->string('thumbnail')->nullable();

            $table->boolean('status')->default(true);

            $table->boolean('is_featured')->default(false);

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
        Schema::dropIfExists('products');
    }
};
