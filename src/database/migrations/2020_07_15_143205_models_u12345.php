<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModelsU12345 extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('models', function (Blueprint $table) {
			$table->unsignedInteger('lv_main_type_id')->nullable()->after('model_number');
			$table->foreign("lv_main_type_id")->references("id")->on("lv_main_types")->onDelete("SET NULL")->onUpdate("CASCADE");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('models', function (Blueprint $table) {
			$table->dropForeign("models_lv_main_type_id_foreign");
			$table->dropColumn('lv_main_type_id');
		});
	}
}
