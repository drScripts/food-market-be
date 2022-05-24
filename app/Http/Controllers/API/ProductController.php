<?php

namespace App\Http\Controllers\API;

use App\Helpers\Cloudinary;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::query();

            if ($request->query('q')) {
                $query = $query->orWhere("name", "LIKE", "%" . $request->query('q') . "%");
                $query = $query->orWhere("ingredients", "LIKE", "%" . $request->query('q') . "%");
            }

            if ($request->query('startPrice')) {
                $query = $query->where("price", '>=', $request->query('startPrice'));
            }

            if ($request->query('endPrice')) {
                $query = $query->where("price", '<=', $request->query('endPrice'));
            }

            if ($request->query('type')) {
                $query = $query->where('type', $request->query('type'));
            }


            return ResponseFormatter::success($query->get(), 'succsess');
        } catch (Exception $err) {
            $code  = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, 'error', 'error', $code);
        }
    }

    public function show($id)
    {
        try {

            $product = Product::find($id);

            return ResponseFormatter::success($product, 'success');
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, $err->getMessage(), 'error', $code);
        }
    }

    public function create(Request $request)
    {
        try {
            $rules = Validator::make($request->all(), [
                'name' => "string|required",
                'price' => "integer|required",
                'ingredients' => "string|required",
                'rating' => "numeric|between:0,5",
                'description' => "string|required",
                'stock' => "integer|required",
                'picture' => "image|required",
                'type' => "in:new_taste,recommended,popular",
            ]);

            if ($rules->fails()) return ResponseFormatter::error($rules->errors()->toArray(), 'Input Error');

            $fields = [
                'name' => $request->name,
                'price' => $request->price,
                'ingredients' => $request->ingredients,
                'rating' => $request->rating ?? 0,
                'description' => $request->description,
                'stock' => $request->stock,
                'type' => $request->type ?? "none",
            ];

            $cloudinary = new Cloudinary();

            $res = $cloudinary->postImage($request->file('picture')->path(), 'foodMarketProduct');
            $fields['picture'] = $res['image_url'];
            $fields['picture_public_id'] = $res['public_id'];

            $product = Product::create($fields);

            return ResponseFormatter::success($product, 'created', 201);
        } catch (Exception $err) {
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
                'name' => "string",
                'price' => "integer",
                'ingredients' => "string",
                'rating' => "numeric|between:0,5",
                'description' => "string",
                'stock' => "integer",
                'picture' => "image",
                'type' => "in:new_taste,recommended,popular",
            ]);

            if ($rules->fails()) return ResponseFormatter::error($rules->errors()->toArray(), 'Input Error');

            $product = Product::find($id);

            if ($request->name) {
                $product->name = $request->name;
            }

            if ($request->price) {
                $product->price = $request->price;
            }

            if ($request->ingredients) {
                $product->ingredients = $request->ingredients;
            }

            if ($request->rating) {
                $product->rating = $request->rating;
            }

            if ($request->description) $product->description = $request->description;

            if ($request->stock) $product->stock = $request->stock;

            if ($request->type) $product->type = $request->type;


            if ($request->file('picture')) {
                $cloudinary = new Cloudinary();

                if ($product->picture_public_id) {
                    $cloudinary->deleteImage($product->picture_public_id);
                }

                $res = $cloudinary->postImage($request->file('picture')->path(), 'foodMarketProduct');
                $product->picture = $res['image_url'];
                $product->picture_public_id = $res['public_id'];
            }

            $product->save();

            return ResponseFormatter::success($product, 'created', 201);
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, "Internal server error", 'error', $code);
        }
    }

    public function delete($id)
    {
        try {
            Product::destroy($id);

            return ResponseFormatter::success(null, 'created', 201);
        } catch (Exception $err) {
            $code = $err->getCode();

            if ($code < 200 || is_string($code)) {
                $code = 500;
            }

            return ResponseFormatter::error(null, "Internal Server Error", 'error', $code);
        }
    }
}
