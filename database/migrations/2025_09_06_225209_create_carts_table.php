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
        Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');   // who added
    $table->unsignedBigInteger('product_id');
    $table->unsignedBigInteger('store_id');
    $table->string('product_name');
    $table->decimal('product_price', 10, 2);
    $table->string('product_unit')->nullable();
    $table->text('product_description')->nullable();
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->integer('quantity')->default(1);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
