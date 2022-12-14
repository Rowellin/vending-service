<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToVendings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendings', function (Blueprint $table) {
            $table->string('ln_video_uri')->nullable();
            $table->string('pt_video_uri')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendings', function (Blueprint $table) {
            $table->dropColumn('ln_video_uri');
            $table->dropColumn('pt_video_uri');
        });
    }
}
