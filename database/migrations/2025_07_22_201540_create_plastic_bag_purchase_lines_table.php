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
        Schema::create('plastic_bag_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plastic_bag_purchase_id');
            $table->unsignedBigInteger('plastic_bag_type_id');
            $table->decimal('quantity', 22, 4); // Number of bags purchased
            $table->decimal('price_per_bag', 22, 4); // Price per individual bag
            $table->decimal('line_total', 22, 4); // Total for this line
            $table->timestamps();

            $table->foreign('plastic_bag_purchase_id')->references('id')->on('plastic_bag_purchases')->onDelete('cascade');
            $table->foreign('plastic_bag_type_id')->references('id')->on('plastic_bag_types')->onDelete('cascade');
            $table->index('plastic_bag_purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_bag_purchase_lines');
    }
};