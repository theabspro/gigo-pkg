<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\EstimationType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class EstimationTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getEstimationTypeFilter() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getEstimationTypeList(Request $request) {
		$estimation_types = EstimationType::withTrashed()

			->select([
				'estimation_types.id',
				'estimation_types.name',
				'estimation_types.code',
				'estimation_types.minimum_amount',

				DB::raw('IF(estimation_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('estimation_types.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('estimation_types.code', 'LIKE', '%' . $request->code . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('estimation_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})

			->where(function ($query) use ($request) {
				if (!empty($request->minimum_amount)) {
					$query->where('estimation_types.minimum_amount', 'LIKE', '%' . $request->minimum_amount . '%');
				}
			})

			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('estimation_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('estimation_types.deleted_at');
				}
			})
		;

		return Datatables::of($estimation_types)

			->addColumn('status', function ($estimation_type) {
				$status = $estimation_type->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $estimation_type->status;
			})

			->addColumn('action', function ($estimation_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$action = '';

				if (Entrust::can('edit-estimation-type')) {
					$action .= '<a href="#!/gigo-pkg/estimation-type/edit/' . $estimation_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';

				}

				if (Entrust::can('delete-estimation-type')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_estimation_type" onclick="angular.element(this).scope().deleteEstimationType(' . $estimation_type->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';

				}
				return $action;
			})
			->make(true);
	}

	public function getEstimationTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$estimation_type = new EstimationType;
			$action = 'Add';
		} else {
			$estimation_type = EstimationType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['estimation_type'] = $estimation_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveEstimationType(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 64 Charachers',
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
				'minimum_amount.required' => 'Amount is Required',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:estimation_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:estimation_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'minimum_amount' => [
					'required:true',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$estimation_type = new EstimationType;
				$estimation_type->company_id = Auth::user()->company_id;
			} else {
				$estimation_type = EstimationType::withTrashed()->find($request->id);
			}
			$estimation_type->fill($request->all());
			if ($request->status == 'Inactive') {
				$estimation_type->deleted_at = Carbon::now();
			} else {
				$estimation_type->deleted_at = NULL;
			}
			$estimation_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Estimation Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Estimation Type Updated Successfully',
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

	public function deleteEstimationType(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$estimation_type = EstimationType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($estimation_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Estimation Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}