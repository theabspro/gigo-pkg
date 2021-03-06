<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use App\VehicleInventoryItem;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Abs\AttributePkg\FieldType;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class VehicleInventoryItemController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getVehicleInventoryItemList(Request $request) {
		$vehicle_inventory_items = VehicleInventoryItem::withTrashed()

			->select([
				'vehicle_inventory_items.id',
				'vehicle_inventory_items.name',
				'vehicle_inventory_items.code',
				'field_types.name as field_type',

				DB::raw('IF(vehicle_inventory_items.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_inventory_items.company_id', Auth::user()->company_id)
			->leftJoin('field_types', 'field_types.id', 'vehicle_inventory_items.field_type_id')

			->where(function ($query) use ($request) {
				if (!empty($request->code)) {
					$query->where('vehicle_inventory_items.code', 'LIKE', '%' . $request->code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('vehicle_inventory_items.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->field_type)) {
					$query->where('vehicle_inventory_items.field_type_id', $request->field_type);
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('vehicle_inventory_items.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('vehicle_inventory_items.deleted_at');
				}
			})
		;

		return Datatables::of($vehicle_inventory_items)
			->addColumn('status', function ($vehicle_inventory_item) {
				$status = $vehicle_inventory_item->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $vehicle_inventory_item->status;
			})
			->addColumn('action', function ($vehicle_inventory_item) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-vehicle-inventory-item')) {
					$output .= '<a href="#!/gigo-pkg/vehicle-inventory-item/edit/' . $vehicle_inventory_item->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-vehicle-inventory-item')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#vehicle_inventory_item_delete_modal" onclick="angular.element(this).scope().deleteVehicleInventoryItem(' . $vehicle_inventory_item->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getVehicleInventoryItemFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$vehicle_inventory_item = new VehicleInventoryItem;
			$action = 'Add';
		} else {
			$vehicle_inventory_item = VehicleInventoryItem::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['extras'] = [
			// 'field_type_list' => collect(FieldType::select('id','name')->get())->prepend(['id' => '', 'name' => 'Select Field Type']),
			'field_type_list' => collect(DB::table('field_types')->select('id','name')->get())->prepend(['id' => '', 'name' => 'Select Field Type']),
			];
		$this->data['success'] = true;
		$this->data['vehicle_inventory_item'] = $vehicle_inventory_item;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function getVehicleInventoryItemFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		$this->data['field_type_list'] = collect(FieldType::select('id','name')->get())->prepend(['id' => '', 'name' => 'Select Field Type']);

		return response()->json($this->data);
	}

	public function saveVehicleInventoryItem(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Code is Required',
				'code.unique' => 'Code is already taken',
				'code.min' => 'Code is Minimum 3 Charachers',
				'code.max' => 'Code is Maximum 32 Charachers',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 191 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'min:3',
					'max:32',
					'unique:vehicle_inventory_items,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'min:3',
					'max:191',
					'nullable',
					'unique:vehicle_inventory_items,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$vehicle_inventory_item = new VehicleInventoryItem;
				$vehicle_inventory_item->company_id = Auth::user()->company_id;
				$vehicle_inventory_item->created_by_id = Auth::user()->id;
			} else {
				$vehicle_inventory_item = VehicleInventoryItem::withTrashed()->find($request->id);
				$vehicle_inventory_item->updated_by_id = Auth::user()->id;
			}
			$vehicle_inventory_item->fill($request->all());
			if ($request->status == 'Inactive') {
				$vehicle_inventory_item->deleted_at = Carbon::now();
				$vehicle_inventory_item->deleted_by_id = Auth::user()->id;
			} else {
				$vehicle_inventory_item->deleted_at = NULL;
				$vehicle_inventory_item->deleted_by_id =NULL;
			}
			$vehicle_inventory_item->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Inventory Item Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Vehicle Inventory Item Updated Successfully',
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

	public function deleteVehicleInventoryItem(Request $request) {
		DB::beginTransaction();
		// dd($request->id);
		try {
			$vehicle_inventory_item = VehicleInventoryItem::withTrashed()->where('id', $request->id)->forceDelete();
			if ($vehicle_inventory_item) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Vehicle Inventory Item Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getVehicleInventoryItems(Request $request) {
		$vehicle_inventory_items = VehicleInventoryItem::withTrashed()
			->with([
				'vehicle-inventory-items',
				'vehicle-inventory-items.user',
			])
			->select([
				'vehicle_inventory_items.id',
				'vehicle_inventory_items.name',
				'vehicle_inventory_items.code',
				DB::raw('IF(vehicle_inventory_items.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('vehicle_inventory_items.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'vehicle_inventory_items' => $vehicle_inventory_items,
		]);
	}
}