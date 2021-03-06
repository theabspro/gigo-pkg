<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CampaignsC extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {

		if (!Schema::hasTable('compaigns')) {
			Schema::create('compaigns', function (Blueprint $table) {

				$table->increments('id');
				$table->unsignedInteger('company_id');
				$table->string('authorisation_no', 64);
				$table->string('complaint_code', 64)->nullable();
				$table->string('fault_code', 64)->nullable();
				$table->unsignedInteger('claim_type_id')->nullable();
				$table->unsignedInteger("created_by_id")->nullable();
				$table->unsignedInteger("updated_by_id")->nullable();
				$table->unsignedInteger("deleted_by_id")->nullable();
				$table->timestamps();
				$table->softDeletes();

				$table->foreign("company_id")->references("id")->on("companies")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("claim_type_id")->references("id")->on("configs")->onDelete("CASCADE")->onUpdate("CASCADE");
				$table->foreign("created_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("updated_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");
				$table->foreign("deleted_by_id")->references("id")->on("users")->onDelete("SET NULL")->onUpdate("cascade");

				$table->unique(["company_id", "authorisation_no"]);

			});
		}
	}
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::dropIfExists('compaigns');
	}
}
