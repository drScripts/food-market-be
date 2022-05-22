<?php

namespace App\Helpers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Midtrans\Config;
use Midtrans\Snap;

class Midtrans
{

    public static function getBody(int $qty, Product $product, int $driver, User $user, Transaction $transaction)
    {
        $total_price = $qty * $product->price;
        $tax = $total_price * 10 / 100;
        $total = $total_price + $tax + $driver;

        $orderId = random_bytes(4);


        $body = [
            'transaction_details' => [
                'order_id' => $transaction->id . "-" . bin2hex($orderId),
                'total' => $total
            ],
            'item_details' => [
                [
                    'id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $qty,
                    'name' => $product->name,
                ],
                [
                    'id' => "TAX",
                    'price' => $tax,
                    'name' => '10% Tax',
                    'quantity' => 1,
                ],
                [
                    'id' => "DRIVER",
                    'price' => $driver,
                    'name' => "Driver Service",
                    'quantity' => 1,
                ]
            ],
            "customer_details" => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->profile->phone_number,
                'shipping_address' => [
                    "first_name" => $user->name,
                    'email' => $user->email,
                    'phone' => $user->profile->phone_number,
                    'address' => $user->profile->address . ' ' . $user->profile->house_number,
                    'city' => $user->profile->city,
                ]
            ],
        ];

        return $body;
    }

    public static function getToken(array $body)
    {
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION') == true;
        Config::$serverKey = env("MIDTRANS_SERVER_KEY");
        Config::$overrideNotifUrl = env("APP_URL") . "/api/transactions/notif";
        Config::$paymentIdempotencyKey = env("MIDTRANS_IDEMPOTENCY_KEY");

        $transaction = Snap::createTransaction($body);

        return [
            'url' => $transaction->redirect_url,
            'token' => $transaction->token
        ];
    }
}
