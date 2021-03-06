<?php

namespace Abs\GigoPkg\Api;

use Abs\BasicPkg\Traits\CrudTrait;
use App\Http\Controllers\Controller;
use App\Part;
use Auth;
use DB;
use Illuminate\Http\Request;

class PartController extends Controller {
	use CrudTrait;
	public $model = Part::class;
	public $successStatus = 200;

	public function stock_data(Request $request) {
		// dd($request->all());
		$stock_data = DB::table('part_stocks')
			->where([
				'part_id' => $request->part,
				'outlet_id' => $request->outlet,
				'company_id' => Auth::user()->company_id,
			])->get()->first();

		return response()->json(['success' => true, 'stock_data' => $stock_data]);

	}

	public function getFormData(Request $request) {
		// dd($request->all());

		$part = Part::with([
			'uom',
			'partStock' => function ($query) use ($request) {
				$query->where('outlet_id', $request->outletId);
			},
			'jobOrderParts',
			'taxCode',
			'taxCode.taxes',
			'repair_order_parts',
		])
			->find($request->partId);

		return response()->json(['success' => true, 'part' => $part]);
	}

}