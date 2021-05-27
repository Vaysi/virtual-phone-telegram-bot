<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTuserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tusers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('phone',15)->nullable();
            $table->string('service')->default('tg');
            $table->boolean('verified')->default(false);
            $table->string('vphone')->nullable();
            $table->string('vphone_id')->nullable();
            $table->string('tcode')->nullable();
            $table->dateTime('tcode_expires')->nullable();
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
        Schema::dropIfExists('tusers');
    }
}
