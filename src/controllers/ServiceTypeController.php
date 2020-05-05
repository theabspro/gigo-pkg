<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\ServiceType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ServiceTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getServiceTypeList(Request $request) {
		$service_types = ServiceType::withTrashed()

			->select([
				'service_types.id',
				'service_types.name',
				'service_types.code',

				DB::raw('IF(service_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('service_types.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('service_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('service_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('service_types.deleted_at');
				}
			})
		;

		return Datatables::of($service_types)
			->rawColumns(['name', 'action'])
			->addColumn('name', function ($service_type) {
				$status = $service_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $service_type->name;
			})
			->addColumn('action', function ($service_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-service_type')) {
					$output .= '<a href="#!/gigo-pkg/service_type/edit/' . $service_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-service_type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#service_type-delete-modal" onclick="angular.element(this).scope().deleteServiceType(' . $service_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getServiceTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$service_type = new ServiceType;
			$action = 'Add';
		} else {
			$service_type = ServiceType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['service_type'] = $service_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveServiceType(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Short Name is Required',
				'code.unique' => 'Short Name is already taken',
				'code.min' => 'Short Name is Minimum 3 Charachers',
				'code.max' => 'Short Name is Maximum 32 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:service_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:191',
					'unique:service_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$service_type = new ServiceType;
				$service_type->company_id = Auth::user()->company_id;
			} else {
				$service_type = ServiceType::withTrashed()->find($request->id);
			}
			$service_type->fill($request->all());
			if ($request->status == 'Inactive') {
				$service_type->deleted_at = Carbon::now();
			} else {
				$service_type->deleted_at = NULL;
			}
			$service_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Service Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Service Type Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deleteServiceType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$service_type = ServiceType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($service_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Service Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getServiceTypes(Request $request) {
		$service_types = ServiceType::withTrashed()
			->with([
				'service-types',
				'service-types.user',
			])
			->select([
				'service_types.id',
				'service_types.name',
				'service_types.code',
				DB::raw('IF(service_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('service_types.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'service_types' => $service_types,
		]);
	}
}