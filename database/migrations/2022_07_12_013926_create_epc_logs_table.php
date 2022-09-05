<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEpcLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('epc_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('at');
            $table->string('epc');
            $table->text('note');
            $table->string('ref');
            $table->enum('activity', ['registration', 'inbound', 'outbound', 'relocation', 'transfer', 'replacement', 'disposal']);
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('user_data');
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
        Schema::dropIfExists('epc_logs');
    }
}
