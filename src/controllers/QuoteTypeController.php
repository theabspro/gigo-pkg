<?php

namespace Abs\GigoPkg;
use App\Http\Controllers\Controller;
use Abs\GigoPkg\QuoteType;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class QuoteTypeController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	public function getQuoteTypeFilterData() {
		$this->data['extras'] = [
			'status' => [
				['id' => '', 'name' => 'Select Status'],
				['id' => '1', 'name' => 'Active'],
				['id' => '0', 'name' => 'Inactive'],
			],
		];
		return response()->json($this->data);
	}

	public function getQuoteTypeList(Request $request) {
		$quote_types = QuoteType::withTrashed()

			->select([
				'quote_types.id',
				'quote_types.name',
				'quote_types.code',

				DB::raw('IF(quote_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('quote_types.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->short_name)) {
					$query->where('quote_types.code', 'LIKE', '%' . $request->short_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('quote_types.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('quote_types.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('quote_types.deleted_at');
				}
			})
		;

		return Datatables::of($quote_types)
			 ->addColumn('status', function ($quote_types) {
				$status = $quote_types->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indigator ' . $status . '"></span>' . $quote_types->status;
			})
			->addColumn('action', function ($quote_type) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-quote-type')) {
					$output .= '<a href="#!/gigo-pkg/quote-type/edit/' . $quote_type->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1 . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-quote-type')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#quote-type-delete-modal" onclick="angular.element(this).scope().deleteQuoteType('.$quote_type->id.')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getQuoteTypeFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$quote_type = new QuoteType;
			$action = 'Add';
		} else {
			$quote_type = QuoteType::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['success'] = true;
		$this->data['quote_type'] = $quote_type;
		$this->data['action'] = $action;
		return response()->json($this->data);
	}

	public function saveQuoteType(Request $request) {
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
					'unique:quote_types,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'nullable',
					'min:3',
					'max:191',
					'unique:quote_types,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$quote_type = new QuoteType;
				$quote_type->created_by_id = Auth::user()->id;
				$quote_type->created_at = Carbon::now();
				$quote_type->updated_at = NULL;
			} else {
				$quote_type = QuoteType::withTrashed()->find($request->id);
				$quote_type->updated_by_id = Auth::user()->id;
				$quote_type->updated_at = Carbon::now();
			}
			$quote_type->company_id = Auth::user()->company_id;
			$quote_type->fill($request->all());
			if ($request->status == 'Inactive') {
				$quote_type->deleted_at = Carbon::now();
			} else {
				$quote_type->deleted_at = NULL;
			}
			$quote_type->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Quote Type Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Quote Type Updated Successfully',
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

	public function deleteQuoteType(Request $request) {
		DB::beginTransaction();
		//dd($request->id);
		try {
			$quote_type = QuoteType::withTrashed()->where('id', $request->id)->forceDelete();
			if ($quote_type) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Quote Type Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function getQuoteTypes(Request $request) {
		$quote_types = QuoteType::withTrashed()
			->with([
				'quote-types',
				'quote-types.user',
			])
			->select([
				'quote_types.id',
				'quote_types.name',
				'quote_types.code',
				DB::raw('IF(quote_types.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('quote_types.company_id', Auth::user()->company_id)
			->get();

		return response()->json([
			'success' => true,
			'quote_types' => $quote_types,
		]);
	}
}