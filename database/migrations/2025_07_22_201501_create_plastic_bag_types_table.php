<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plastic_bag_types', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('name'); // Small, Medium, Large, etc.
            $table->text('description')->nullable();
            $table->decimal('price', 22, 4); // Price per bag
            $table->decimal('stock_quantity', 22, 4)->default(0); // Current stock
            $table->decimal('alert_quantity', 22, 4)->nullable(); // Alert when stock is low
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->index(['business_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_bag_types');
    }
};