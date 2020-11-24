<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayedGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('played_games', function (Blueprint $table) {
           //Id
           $table->id();

           //Attributes
           $table->string('best_time');

           //Foreign id's
           $table->unsignedBigInteger('user_id')->nullable();

           //Relationships
           $table->foreign('user_id')
           ->references('id')->on('users')
           ->onDelete('cascade');

           //Timestamps
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
        Schema::dropIfExists('played_games');
    }


}
