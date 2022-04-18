<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Donation;


class DonationController extends Controller
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds = config('services.midtrans.is3ds');
    }

     public function index()
     {
         return view('donation');
     }

     public function store(Request $request)
     {
         // dengan menggunakan \DB::transaction ini bisa rollback otomatis jika ada proses insert yg salah
         \DB::transaction(function() use($request) {
           
            // Request data dari form untuk disimpan ke db
            $donation = Donation::create([
                'transaction_id' => \Str::uuid(),
                'donor_name' => $request->donor_name,
                'donor_email' => $request->donor_email,
                'donation_type' => $request->donation_type,
                'amount' => floatval($request->amount),
                'note' => $request->note,
            ]);
        

            // generate snap token start
            $payload = [
                'transaction_details' => [
                    'order_id'      => $donation->transaction_id,
                    'gross_amount'  => $donation->amount,
                ],

                'customer_details' => [
                    'first_name'    => $donation->donor_name,
                    'email'         => $donation->donor_email,
                    // 'phone'         => '08888888888',
                    // 'address'       => '',
                ],

                'item_details' => [
                    [
                        'id'       => $donation->donation_type,
                        'price'    => $donation->amount,
                        'quantity' => 1,
                        'name'     => ucwords(str_replace('_', ' ', $donation->donation_type))
                    ]
                ]

            ];

        

            $snapToken = \Midtrans\Snap::getSnapToken($payload);
            // generate snap token end

            //update masukkan snaptoken ke db start
            $donation->snap_token = $snapToken;
            $donation->save();
            //end

            // ini supaya $snapToken bisa di panggil di javascriptnya dengan nama snap_token
            $this->response['snap_token'] = $snapToken;
        });

     }
}
