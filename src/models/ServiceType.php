<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use App\Company;
use App\SerialNumberGroup;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceType extends BaseModel {
	use SeederTrait;
	use SoftDeletes;
	protected $table = 'service_types';
	public $timestamps = true;
	protected $fillable =
		["company_id", "code", "name", "display_order"]
	;
	public static $AUTO_GENERATE_CODE = true;

	protected static $excelColumnRules = [
		'Code' => [
			'table_column_name' => 'code',
			'rules' => [
				'required' => [
				],
			],
		],
		'Name' => [
			'table_column_name' => 'name',
			'rules' => [
				'required' => [
				],
			],
		],
		'Is Free' => [
			'table_column_name' => 'is_free',
			'rules' => [
				'required' => [
				],
			],
		],
		'Display Order' => [
			'table_column_name' => 'display_order',
			'rules' => [
			],
		],

	];

	public function serviceTypeLabours() {
		return $this->belongsToMany('Abs\GigoPkg\RepairOrder', 'repair_order_service_type', 'service_type_id', 'repair_order_id')->withPivot(['is_free_service']);
	}

	public function serviceTypeParts() {
		return $this->belongsToMany('Abs\PartPkg\Part', 'part_service_type', 'service_type_id', 'part_id')->withPivot(['quantity', 'amount', 'is_free_service']);
	}

	public function scopeFilterIsFree($query, $is_free) {
		$query->where('is_free', $is_free);
	}

	/*public static function createFromObject($record_data) {

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
	}*/

	public static function getDropDownList($params = [], $add_default = true, $default_text = 'Select Service Type') {
		$list = Self::select([
			'service_types.id',
			'service_types.name',
		]);
		if (isset($params['vehicle_service_schedule_id'])) {
			$list->leftjoin('vehicle_service_schedule_service_types', 'service_types.id', 'vehicle_service_schedule_service_types.service_type_id')->where('vehicle_service_schedule_service_types.vehicle_service_schedule_id', $params['vehicle_service_schedule_id']);
		}
		if ($params && isset($params['service_type_ids'])) {
			$list = $list->whereNotIn('service_types.id', $params['service_type_ids']);
		}

		$list = $list->where('company_id', Auth::user()->company_id)->orderBy('service_types.display_order', 'ASC')->get();

		if ($add_default) {
			$list->prepend(['id' => '', 'name' => $default_text]);
		}
		return $list;
	}

	public static function saveFromObject($record_data) {
		$record = [
			'Company Code' => $record_data->company_code,
			'Code' => $record_data->code,
			'Name' => $record_data->name,
			'Is Free' => $record_data->is_free,
			'Display Order' => $record_data->display_order,
		];
		return static::saveFromExcelArray($record);
	}

	public static function saveFromExcelArray($record_data) {
		try {
			$errors = [];
			$company = Company::where('code', $record_data['Company Code'])->first();
			if (!$company) {
				return [
					'success' => false,
					'errors' => ['Invalid Company : ' . $record_data['Company Code']],
				];
			}
			// dd($record_data);
			if (!isset($record_data['Display Order'])) {
				$display_order = 99;
			} else {
				$display_order = intval($record_data['Display Order']);
			}

			if (!isset($record_data['created_by_id'])) {
				$admin = $company->admin();

				if (!$admin) {
					return [
						'success' => false,
						'errors' => ['Default Admin user not found'],
					];
				}
				$created_by_id = $admin->id;
			} else {
				$created_by_id = $record_data['created_by_id'];
			}

			if (count($errors) > 0) {
				return [
					'success' => false,
					'errors' => $errors,
				];
			}

			if (Self::$AUTO_GENERATE_CODE) {
				if (empty($record_data['Code'])) {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'name' => $record_data['Name'],
					]);
					$result = SerialNumberGroup::generateNumber(static::$SERIAL_NUMBER_CATEGORY_ID);
					if ($result['success']) {
						$record_data['Code'] = $result['number'];
					} else {
						return [
							'success' => false,
							'errors' => $result['errors'],
						];
					}
				} else {
					$record = static::firstOrNew([
						'company_id' => $company->id,
						'code' => $record_data['Code'],
					]);
				}
			} else {
				$record = static::firstOrNew([
					'company_id' => $company->id,
					'code' => $record_data['Code'],
				]);
			}
			/*$record = Self::firstOrNew([
				'company_id' => $company->id,
				'code' => $record_data['Code'],
			]);*/

			$result = Self::validateAndFillExcelColumns($record_data, Static::$excelColumnRules, $record);
			if (!$result['success']) {
				return $result;
			}
			$record->display_order = $display_order;
			$record->is_free = ($record_data['Is Free'] == 'yes') ? 1 : 0;
			$record->company_id = $company->id;
			$record->created_by_id = $created_by_id;
			$record->save();
			return [
				'success' => true,
			];
		} catch (\Exception $e) {
			return [
				'success' => false,
				'errors' => [
					$e->getMessage(),
				],
			];
		}
	}
}
