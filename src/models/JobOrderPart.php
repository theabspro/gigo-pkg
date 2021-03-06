<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\Company;
use App\Config;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOrderPart extends Model {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'job_order_parts';
	public $timestamps = true;
	protected $fillable =
		["job_order_id", "part_id", "qty", "split_order_type_id", "rate", "amount", "status_id", "is_free_service", "is_oem_recommended"]
	;

	public function getDateOfJoinAttribute($value) {
		return empty($value) ? '' : date('d-m-Y', strtotime($value));
	}

	public function setDateOfJoinAttribute($date) {
		return $this->attributes['date_of_join'] = empty($date) ? NULL : date('Y-m-d', strtotime($date));
	}
	public function part() {
		return $this->belongsTo('App\Part', 'part_id');
	}
	public function splitOrderType() {
		return $this->belongsTo('App\SplitOrderType', 'split_order_type_id');
	}
	public function status() {
		return $this->belongsTo('App\Config', 'status_id');
	}
	public function jobOrderIssuedParts() {
		return $this->hasMany('Abs\GigoPkg\JobOrderIssuedPart', 'job_order_part_id')->select(
			'job_order_issued_parts.*',
			DB::raw('SUM(issued_qty) as issued_qty')
		)
			->groupBy('job_order_part_id');
	}

	public function customerVoice() {
		return $this->belongsTo('App\CustomerVoice', 'customer_voice_id');
	}

	public function taxes() {
		return $this->belongsToMany('App\Tax', 'job_order_part_tax', 'job_order_part_id', 'tax_id')->withPivot(['percentage', 'amount']);
	}

	public function fault() {
		return $this->belongsTo('App\Fault', 'fault_id');
	}

	public function complaint() {
		return $this->belongsTo('App\Complaint', 'complaint_id');
	}

	public static function createFromObject($record_data) {

		$errors = [];
		$company = Company::where('code', $record_data->company)->first();
		if (!$company) {
			dump('Invalid Company : ' . $record_data->company);
			return;
		}

		$admin = $company->admin();
		if (!$admin) {
			dump('Default Admin user not found');
			return;
		}

		$type = Config::where('name', $record_data->type)->where('config_type_id', 89)->first();
		if (!$type) {
			$errors[] = 'Invalid Tax Type : ' . $record_data->type;
		}

		if (count($errors) > 0) {
			dump($errors);
			return;
		}

		$record = self::firstOrNew([
			'company_id' => $company->id,
			'name' => $record_data->tax_name,
		]);
		$record->type_id = $type->id;
		$record->created_by_id = $admin->id;
		$record->save();
		return $record;
	}

	public static function getList($params = [], $add_default = true, $default_text = 'Select Job Order Part') {
		$list = Collect(Self::select([
			'id',
			'name',
		])
				->orderBy('name')
				->get());
		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

}
