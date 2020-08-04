<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTblJobOrderRemoveCol extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->dropForeign('job_orders_part_intent_status_id_foreign');
			$table->dropColumn('part_intent_status_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::table('job_orders', function (Blueprint $table) {
			$table->unsignedInteger('part_intent_status_id')->nullable()->after('minimum_payable_amount');
			$table->foreign('part_intent_status_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
		});
	}
}
