<?php

namespace App\Http\Controllers\API;

use App\Helpers\Midtrans;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Midtrans\Notification;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $login_user = $request->attributes->get('user');


        $query = Transaction::with(['user', 'product']);

        $query = $query->where('user_id', $login_user);

        $status = explode(',', $request->query('status'));
        if ($request->query('status')) {
            $c = 0;
            foreach ($status as  $stat) {
                if ($c == 0) {
                    $query = $query->where("status", $stat);
                } else {
                    $query = $query->orWhere("status", $stat);
                }
                $c++;
            }
        }

        if ($request->query("q")) {
            $query = $query->WhereRelation('user', 'name', 'LIKE', "%" . $request->query('q') . "%");
            $query = $query->orWhereRelation('product', 'name', 'LIKE', "%" . $request->query('q') . "%");
        }

        $transaction = $query->get();

        return ResponseFormatter::success($transaction, 'success', 200);
    }

    public function show(Request $request, $id)
    {
        try {
            $user_login = $request->attributes->get('user');

            $transaction = Transaction::with(['user', 'product'])->where('user_id', $user_login)->find($id);

            return ResponseFormatter::success($transaction, 'success');
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, 'Internal server error', 'error', $code);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules  = Validator::make($request->all(), [
                'product_id' => 'integer|required',
                'qty' => "integer|required",
                'driver' => "integer|required",
                'total' => "integer|required",
            ]);

            if ($rules->fails()) return ResponseFormatter::error($rules->errors()->toArray(), 'Input error');

            $user_login = $request->attributes->get('user');

            $user = User::with('profile')->find($user_login);
            $product = Product::find($request->product_id);

            $transaction = Transaction::create([
                'user_id' => $user_login,
                'product_id' => $product->id,
                'qty' => $request->qty,
                'driver' => $request->driver,
                'total' => $request->total,
            ]);

            $product->stock = $product->stock - $request->qty;
            $product->save();

            $midtransBody = Midtrans::getBody($request->qty, $product, $request->driver, $user, $transaction);

            $transaction->raw_body = json_encode($midtransBody);

            $midtrans = Midtrans::getToken($midtransBody);

            $transaction->payment_url = $midtrans['url'];
            $transaction->payment_token = $midtrans['token'];

            $transaction->save();

            return ResponseFormatter::success($transaction, 'created', 201);
        } catch (Exception $err) {
            $transaction->forceDelete();
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, $err->getMessage(), 'error', $code);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = Validator::make($request->all(), [
                'status' => "in:canceled",
            ]);

            if ($rules->fails()) return ResponseFormatter::error($rules->errors()->toArray(), 'Input error');

            $login_user = $request->attributes->get('user');

            $transaction = Transaction::with(['user', 'product'])->where('user_id', $login_user)->find($id);

            if (!$transaction) return ResponseFormatter::error(null, 'Restricted resource', 'error', 403);

            if ($request->status) {
                $transaction->status = $request->status;
            }

            $transaction->save();

            return ResponseFormatter::success($transaction, 'created', 201);
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, 'Internal server error', 'error', $code);
        }
    }

    public function notif()
    {
        try {
            Config::$isProduction = env('MIDTRANS_IS_PRODUCTION') == true;
            Config::$serverKey = env("MIDTRANS_SERVER_KEY");
            Config::$paymentIdempotencyKey = env("MIDTRANS_IDEMPOTENCY_KEY");

            $notif = new Notification();

            $notif = $notif->getResponse();

            $transaction = $notif->transaction_status;
            $type = $notif->payment_type;

            $order_id = $notif->order_id;
            $transaction_id = explode("-", $order_id)[0];

            $fraud = $notif->fraud_status;

            $transaction = Transaction::find($transaction_id);

            if ($transaction == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                    } else {
                        $transaction->status = "completed";
                    }
                }
            } else if ($transaction == 'settlement') {
                $transaction->status = "completed";
            } else if ($transaction == 'pending') {
                $transaction->status = "in progress";
            } else if ($transaction == 'deny') {
                $transaction->status = "canceled";
            } else if ($transaction == 'expire') {
                $transaction->status = "failed";
            } else if ($transaction == 'cancel') {
                $transaction->status = "canceled";
            }

            $transaction->save();

            return response('ok', 200);
        } catch (Exception $err) {
            dd($err->getMessage());
        }
    }
}
