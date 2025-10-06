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
        Schema::create('plastic_bag_purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->string('invoice_number');
            $table->date('purchase_date');
            $table->integer('supplier_id')->unsigned()->nullable(); // From contacts table
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->string('invoice_file')->nullable(); // Attached invoice file
            $table->text('notes')->nullable();
            $table->integer('created_by')->unsigned();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['business_id', 'purchase_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plastic_bag_purchases');
    }
};