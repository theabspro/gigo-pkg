<?php

namespace Abs\GigoPkg;

use Abs\HelperPkg\Traits\SeederTrait;
use App\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatteryLoadTestResult extends BaseModel
{
    use SeederTrait;
    use SoftDeletes;
    protected $table = 'battery_load_test_results';
    public $timestamps = true;
    protected $fillable =
        ["company_id", "outlet_id", "vehicle_battery_id", "amp_hour", "battery_voltage", "load_test_status_id", "hydrometer_electrolyte_status_id", "remarks"]
    ;

    public function getRegistrationNumberAttribute($value)
    {
        $registration_number = '';

        if ($value) {
            $value = str_replace('-', '', $value);
            $reg_number = str_split($value);

            $last_four_numbers = substr($value, -4);

            $registration_number .= $reg_number[0] . $reg_number[1] . '-' . $reg_number[2] . $reg_number[3] . '-';

            if (is_numeric($reg_number[4])) {
                $registration_number .= $last_four_numbers;
            } else {
                $registration_number .= $reg_number[4];
                if (is_numeric($reg_number[5])) {
                    $registration_number .= '-' . $last_four_numbers;
                } else {
                    $registration_number .= $reg_number[5] . '-' . $last_four_numbers;
                }
            }
        }
        return $this->attributes['registration_number'] = $registration_number;
    }

    public function vehicleBattery()
    {
        return $this->belongsTo('App\VehicleBattery', 'vehicle_battery_id');
    }

    public function outlet()
    {
        return $this->belongsTo('App\Outlet', 'outlet_id');
    }

    public function batteryLoadTestStatus()
    {
        return $this->belongsTo('App\BatteryLoadTestStatus', 'overall_status_id');
    }

    public function loadTestStatus()
    {
        return $this->belongsTo('App\LoadTestStatus', 'load_test_status_id');
    }

    public function hydrometerElectrolyteStatus()
    {
        return $this->belongsTo('App\HydrometerElectrolyteStatus', 'hydrometer_electrolyte_status_id');
    }
}
