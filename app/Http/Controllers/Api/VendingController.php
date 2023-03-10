<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Vending;
use App\Services\VendingService;
use App\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VendingController extends Controller
{
    private VendingService $service;

    public function __construct(VendingService $service)
    {
        $this->service = $service;
    }

    public function login(Request $request)
    {
        $user = User::where('user', $request->user)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            return $this->success();
        } else {
            return $this->failure('Unauthorized', '401');
        }
    }

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

            $xendit = $this->service->createXenditInvoice($request);

            if ($xendit->failed()) {
                Log::channel('xendit')->error($xendit->body(), $request->all());
                return $this->failure('xendit integration error');
            }

            $res = $xendit->json();
            $invoice = $request->all();
            $invoice['xendit_id'] = $res['id'];
            $invoice['xendit_url'] = $res['invoice_url'];
            $expired = new DateTime($res['expiry_date']);

            DB::beginTransaction();
            $data = Invoice::create($invoice);
            $data['expired'] = $expired->getTimestamp() - now()->getTimestamp();

            if ($vending = Vending::where('name', $request->vending)->first()) {
                $vending->update(['status' => 'payment', 'xendit_id' => $res['id'], 'product' => $request->product]);
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
        try {
            if ($request->header('x-callback-token') !== env('XENDIT_CALLBACK')) {
                return $this->failure('Unauthorized', 401);
            }

            Invoice::where('xendit_id', $request->id)->update(['status' => strtolower($request->status)]);
            Vending::where('xendit_id', $request->id)->update([
                'status' => strtolower($request->status) == 'paid' ? 'processing' : 'ready',
                'xendit_id' => null
            ]);

            return $this->success('received');
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 500);
        }
    }

    public function products(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vending' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->failure($validator->errors()->first(), 400);
            }

            $data = $this->service->getProductReady(
                $this->service->getProductAvailable($request),
                $this->service->getProductSold($request->vending)
            );

            return $this->success([
                'bg' => Storage::disk('public')->exists('bg.jpg')
                    ? Storage::disk('public')->url('bg.jpg')
                    : null,
                'video' => Vending::where('name',  $request->vending)->first(['ln_video_uri', 'pt_video_uri', 'switch_screen']),
                'products' => $data,
            ]);
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 500);
        }
    }

    public function vendingCheck(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vending' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->failure($validator->errors()->first(), 400);
            }

            if (Vending::where('name', $request->vending)->exists()) {
                return $this->success();
            } else {
                return $this->failure('Not Found', 404);
            }
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 500);
        }
    }
}
