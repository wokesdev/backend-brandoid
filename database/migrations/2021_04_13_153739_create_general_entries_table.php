<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cash_payment_id')->nullable()->constrained('cash_payments')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('cash_receipt_id')->nullable()->constrained('cash_receipts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('nomor_transaksi')->unique();
            $table->date('tanggal');
            $table->string('keterangan')->nullable();
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
        Schema::dropIfExists('general_entries');
    }
}
