<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('at');
            $table->string('device');
            $table->string('version');
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
        Schema::dropIfExists('user_logs');
    }
}
