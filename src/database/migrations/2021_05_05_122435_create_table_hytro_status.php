<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableHytroStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('hydrometer_electrolyte_statuses')) {
            Schema::create('hydrometer_electrolyte_statuses', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('company_id');
                $table->string('code',40);
                $table->string('name',40);

                $table->unique(['company_id', 'code']);
                $table->unique(['company_id', 'name']);

                $table->foreign('company_id','company_id_foreign')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hydrometer_electrolyte_statuses');
    }
}
