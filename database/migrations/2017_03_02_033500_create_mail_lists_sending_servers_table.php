<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailListsSendingServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_lists_sending_servers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sending_server_id')->unsigned();
            $table->integer('mail_list_id')->unsigned();
            $table->integer('fitness');
            
            $table->timestamps();
            
            $table->foreign('sending_server_id')->references('id')->on('sending_servers')->onDelete('cascade');
            $table->foreign('mail_list_id')->references('id')->on('mail_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mail_lists_sending_servers');
    }
}
