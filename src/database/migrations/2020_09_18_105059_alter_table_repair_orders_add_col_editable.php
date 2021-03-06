<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableRepairOrdersAddColEditable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->tinyInteger('is_editable')->default(0)->comment('0 : Non Editable, 1 : Editable')->after('name');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('repair_orders', function (Blueprint $table) {
			$table->dropColumn('is_editable');
		});
	}
}
