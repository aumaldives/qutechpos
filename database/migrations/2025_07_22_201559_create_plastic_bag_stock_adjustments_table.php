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
        Schema::create('plastic_bag_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->unsignedBigInteger('plastic_bag_type_id');
            $table->integer('location_id')->unsigned()->nullable(); // Business location
            $table->enum('adjustment_type', ['increase', 'decrease']);
            $table->decimal('quantity', 22, 4); // Quantity adjusted
            $table->string('reason'); // Reason for adjustment
            $table->text('notes')->nullable();
            $table->date('adjustment_date');
            $table->integer('created_by')->unsigned();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('plastic_bag_type_id')->references('id')->on('plastic_bag_types')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'adjustment_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_bag_stock_adjustments');
    }
};