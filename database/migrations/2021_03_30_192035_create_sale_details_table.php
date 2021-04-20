<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('kuantitas')->nullable();
            $table->integer('harga_satuan')->nullable();
            $table->integer('subtotal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_details');
    }
}
