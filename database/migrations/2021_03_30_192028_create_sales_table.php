<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('coa_detail_id')->nullable()->constrained('chart_of_account_details')->onUpdate('cascade')->onDelete('set null');
            $table->foreignId('coa_detail_payment_id')->nullable()->constrained('chart_of_account_details')->onUpdate('cascade')->onDelete('set null');
            $table->string('nomor_penjualan')->unique();
            $table->integer('total');
            $table->string('keterangan');
            $table->date('tanggal');
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
        Schema::dropIfExists('sales');
    }
}
