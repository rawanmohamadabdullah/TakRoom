<?php

// namespace App\Http\Controllers;

// use App\Models\Product;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use App\Http\Requests\StoreProductRequest;
// use App\Http\Requests\UpdateProductRequest;
// use App\Http\Requests\StoreCategoryRequest;
// use App\Models\Category;

// class ProductController extends Controller
// {
//     public function getAllCategories()
//     {
//         $categories = Category::select('id', 'name')->get();

//         return response()->json([
//             "status" => "success",
//             "categories" => $categories
//         ], 200);
//     }


//     public function storeCategory(StoreCategoryRequest $request)
//     {
//         $category = Category::create($request->validated());

//         return response()->json([
//             "status" => "success",
//             "message" => "Category created successfully",
//             "category" => $category
//         ], 201);
//     }
//     public function index(Request $request)
//     {
//         $categoryFilter = $request->query('category', 'all');

//         $query = Product::with(['category:id,name', 'supplier:id,name'])
//             ->where('stock', '>', 0);

//         if ($categoryFilter !== 'all') {
//             $query->whereHas('category', function ($q) use ($categoryFilter) {
//                 $q->where('name', $categoryFilter);
//             });
//         }

//         $products = $query->latest()->get();

//         return response()->json([
//             "status" => "success",
//             "filter_applied" => $categoryFilter,
//             "count" => $products->count(),
//             "products" => $products
//         ], 200);
//     }

//     public function getByCategory($categoryId)
//     {
//         $products = Product::with(['category:id,name'])
//             ->where('category_id', $categoryId)
//             ->where('stock', '>', 0)
//             ->latest()
//             ->get();

//         return response()->json([
//             "status" => "success",
//             "products" => $products
//         ], 200);
//     }

//     public function store(StoreProductRequest $request)
//     {
//         $validatedData = $request->validated();

//         $product = Product::create([
//             'name' => $validatedData['name'],
//             'description' => $validatedData['description'],
//             'price' => $validatedData['price'],
//             'stock' => $validatedData['stock'],
//             'category_id' => $validatedData['category_id'],
//             'supplier_id' => Auth::id(),
//         ]);

//         return response()->json([
//             "status" => "success",
//             "message" => "Product created successfully",
//             "product" => $product
//         ], 201);
//     }

//     public function update(UpdateProductRequest $request, $id)
//     {
//         $product = Product::findOrFail($id);

//         if ($product->supplier_id !== Auth::id()) {
//             return response()->json([
//                 "status" => "error",
//                 "message" => "Unauthorized. You can only update your own products."
//             ], 403);
//         }

//         $product->update($request->validated());

//         return response()->json([
//             "status" => "success",
//             "message" => "Product updated successfully",
//             "product" => $product
//         ], 200);
//     }

//     public function destroy($id)
//     {
//         $product = Product::findOrFail($id);

//         if ($product->supplier_id !== Auth::id()) {
//             return response()->json([
//                 "status" => "error",
//                 "message" => "Unauthorized. You can only delete your own products."
//             ], 403);
//         }

//         $product->delete();

//         return response()->json([
//             "status" => "success",
//             "message" => "Product deleted successfully"
//         ], 200);
//     }
// }
