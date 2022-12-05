<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Vending;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VendingController extends Controller
{
    public function invoice(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|integer',
                'product' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->failure($validator->errors()->first(), 400);
            }

            $xendit = Http::withBasicAuth(env('XENDIT_KEY'), '')->post(env('XENDIT_API') . '/invoices', [
                'external_id' => 'invoice-' . now(),
                'amount' => $request->amount,
                'description' => $request->vending . '-' . $request->product,
            ]);

            if ($xendit->failed()) {
                Log::channel('xendit')->error($xendit->body(), $request->all());
                return $this->failure('xendit integration error');
            }

            $res = $xendit->json();
            $invoice = $request->all();
            $invoice['xendit_id'] = $res['id'];
            $invoice['xendit_url'] = $res['invoice_url'];

            DB::beginTransaction();
            $data = Invoice::create($invoice);

            if ($vending = Vending::where('name', $request->vending)->first()) {
                $vending->update(['status' => 'payment']);
            }

            DB::commit();

            return $this->success($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->failure($th->getMessage(), 500);
        }
    }

    public function callback(Request $request)
    {
        if ($request->header('x-callback-token') !== env('XENDIT_CALLBACK')) {
            return $this->failure('Unauthorized', 401);
        }

        Invoice::where('xendit_id', $request->id)->update(['status' => strtolower($request->status)]);

        return $this->success('received');
    }
}
