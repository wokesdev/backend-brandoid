<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralEntryDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_entry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('general_entry_id')->nullable()->constrained('general_entries')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('coa_detail_id')->nullable()->constrained('chart_of_account_details')->onUpdate('cascade')->onDelete('set null');
            $table->integer('debit');
            $table->integer('kredit');
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
        Schema::dropIfExists('general_entry_details');
    }
}
