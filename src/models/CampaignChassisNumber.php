<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignChassisNumber extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'campaign_chassis_numbers';
	public $timestamps = true;
	protected $fillable = [
		"campaign_id",
		"chassis_number",
	];

}
