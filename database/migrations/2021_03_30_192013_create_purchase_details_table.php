<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('kuantitas');
            $table->integer('harga_satuan');
            $table->integer('subtotal');
            $table->integer('stok');
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
        Schema::dropIfExists('purchase_details');
    }
}
