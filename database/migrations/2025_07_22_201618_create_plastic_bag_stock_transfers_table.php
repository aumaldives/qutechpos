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
        Schema::create('plastic_bag_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('transfer_number')->unique(); // Added missing transfer_number field
            $table->unsignedBigInteger('plastic_bag_type_id');
            $table->integer('from_location_id')->unsigned(); // Source location
            $table->integer('to_location_id')->unsigned(); // Destination location
            $table->decimal('quantity', 22, 4); // Quantity transferred
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->integer('created_by')->unsigned();
            $table->integer('approved_by')->unsigned()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('received_by')->unsigned()->nullable(); // Added missing received_by field
            $table->timestamp('received_at')->nullable(); // Added missing received_at field
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('plastic_bag_type_id')->references('id')->on('plastic_bag_types')->onDelete('cascade');
            $table->foreign('from_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('to_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['business_id', 'transfer_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_bag_stock_transfers');
    }
};