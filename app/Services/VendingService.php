<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VendingService
{
  public function createXenditInvoice($request)
  {
    return Http::withBasicAuth(env('XENDIT_KEY'), '')->post(env('XENDIT_API') . '/invoices', [
      'external_id' => 'invoice-' . now(),
      'amount' => $request->amount,
      'description' => $request->vending . '-' . $request->product,
    ]);
  }

  public function getProductAvailable($request)
  {
    $datas = DB::table($request->vending)
      ->orderBy('id', 'desc')
      ->first();
    $products = (array) $datas;

    // converting to array
    $productsAvailable = [];
    for ($i = 1; $i <= 100; $i++) {
      if ($products["Produk$i"] !== "KOSONG") {
        $imageName = strtolower(str_replace(' ', '_', $products["Produk$i"])) . '.jpg';

        $productsAvailable[] = [
          'name' => $products["Produk$i"],
          'stock' => $products["Slot$i"],
          'price' => $products["Harga$i"],
          'image' => Storage::disk('public')->exists($imageName)
            ? Storage::disk('public')->url($imageName)
            : null,
        ];
      }
    }

    // sorting
    usort($productsAvailable, function ($a, $b) {
      return $a['name'] <=> $b['name'];
    });

    // merging
    for ($i = 0; $i < count($productsAvailable); $i++) {
      if (
        $i > 0 &&
        $productsAvailable[$i]['name'] == $productsAvailable[$i - 1]['name']
      ) {
        $productsAvailable[$i - 1]['stock'] += $productsAvailable[$i]['stock'];
        $productsAvailable[$i]['stock'] = 0;
      }
    }

    return $productsAvailable;
  }

  protected function getProductSold()
  {
    return Invoice::where('status', 'paid')
      ->whereDate('created_at', today())
      ->groupBy('product')
      ->get(['product', DB::raw('count(*) as total')])
      ->toArray();
  }

  public function getProductReady($productsAvailable)
  {
    $productsSold = $this->getProductSold();

    // accumulating current stock with current selling
    $productReady = [];
    foreach ($productsAvailable as $key => $product) {
      if ($product['stock'] != 0) {
        $isSold = array_search($product['name'], array_column($productsSold, 'product'));

        if ($isSold !== false) {
          $product['stock'] -= $productsSold[$isSold]['total'];
        }

        if ($product['stock'] > 0) {
          $productReady[] = $product;
        }
      }
    }

    return $productReady;
  }
}
