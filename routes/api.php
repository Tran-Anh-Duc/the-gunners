<?php
	
	use App\Http\Controllers\Api\AuthController as ApiAuthController;
	use App\Http\Controllers\Api\CustomerController;
	use App\Http\Controllers\Api\InventoryController;
	use App\Http\Controllers\Api\OrderController;
	use App\Http\Controllers\Api\PaymentController;
	use App\Http\Controllers\Api\ProductController;
	use App\Http\Controllers\Api\StockInController;
	use App\Http\Controllers\Api\StockAdjustmentController;
	use App\Http\Controllers\Api\StockOutController;
	use App\Http\Controllers\Api\SupplierController;
	use App\Http\Controllers\Api\UnitController;
	use App\Http\Controllers\Api\UserController;
	use App\Http\Controllers\Api\WarehouseController;
	use App\Http\Controllers\TestController;

// Nhóm xác thực tách riêng vì vừa có route public, vừa có route yêu cầu token.
	Route::prefix('auth')->group(function () {
		Route::post('login', [ApiAuthController::class, 'login']);
		Route::post('register', [ApiAuthController::class, 'register']);
		
		Route::middleware('jwt')->group(function () {
			Route::post('logout', [ApiAuthController::class, 'logout']);
			Route::get('me', [ApiAuthController::class, 'me']);
		});
	});

// Từ đây trở xuống là các API business-scoped của MVP quản lý kho mini.
	Route::middleware('jwt')->prefix('users')->group(function () {
		Route::get('/', [UserController::class, 'index'])->middleware('permission:users,view');
		Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:users,view');
		Route::post('/', [UserController::class, 'store'])->middleware('permission:users,create');
		Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:users,update');
		Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:users,delete');
	});
	
	Route::middleware('jwt')->prefix('units')->group(function () {
		Route::get('/', [UnitController::class, 'index'])->middleware('permission:inventory,view');
		Route::post('/', [UnitController::class, 'store'])->middleware('permission:inventory,create');
		Route::get('/{id}', [UnitController::class, 'show'])->middleware('permission:inventory,view');
		Route::put('/{id}', [UnitController::class, 'update'])->middleware('permission:inventory,update');
		Route::delete('/{id}', [UnitController::class, 'destroy'])->middleware('permission:inventory,delete');
	});
	
	Route::middleware('jwt')->prefix('warehouses')->group(function () {
		Route::get('/', [WarehouseController::class, 'index'])->middleware('permission:inventory,view');
		Route::post('/', [WarehouseController::class, 'store'])->middleware('permission:inventory,create');
		Route::get('/{id}', [WarehouseController::class, 'show'])->middleware('permission:inventory,view');
		Route::put('/{id}', [WarehouseController::class, 'update'])->middleware('permission:inventory,update');
		Route::delete('/{id}', [WarehouseController::class, 'destroy'])->middleware('permission:inventory,delete');
	});
	
	Route::middleware('jwt')->prefix('products')->group(function () {
		Route::get('/', [ProductController::class, 'index'])->middleware('permission:products,view');
		Route::post('/', [ProductController::class, 'store'])->middleware('permission:products,create');
		Route::get('/{id}', [ProductController::class, 'show'])->middleware('permission:products,view');
		Route::put('/{id}', [ProductController::class, 'update'])->middleware('permission:products,update');
		Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('permission:products,delete');
	});
	
	Route::middleware('jwt')->prefix('customers')->group(function () {
		Route::get('/', [CustomerController::class, 'index'])->middleware('permission:customers,view');
		Route::post('/', [CustomerController::class, 'store'])->middleware('permission:customers,create');
		Route::get('/{id}', [CustomerController::class, 'show'])->middleware('permission:customers,view');
		Route::put('/{id}', [CustomerController::class, 'update'])->middleware('permission:customers,update');
		Route::delete('/{id}', [CustomerController::class, 'destroy'])->middleware('permission:customers,delete');
	});
	
	Route::middleware('jwt')->prefix('suppliers')->group(function () {
		Route::get('/', [SupplierController::class, 'index'])->middleware('permission:suppliers,view');
		Route::post('/', [SupplierController::class, 'store'])->middleware('permission:suppliers,create');
		Route::get('/{id}', [SupplierController::class, 'show'])->middleware('permission:suppliers,view');
		Route::put('/{id}', [SupplierController::class, 'update'])->middleware('permission:suppliers,update');
		Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers,delete');
	});
	
	Route::middleware('jwt')->prefix('orders')->group(function () {
		Route::get('/', [OrderController::class, 'index'])->middleware('permission:orders,view');
		Route::post('/', [OrderController::class, 'store'])->middleware('permission:orders,create');
		Route::get('/{id}', [OrderController::class, 'show'])->middleware('permission:orders,view');
		Route::put('/{id}', [OrderController::class, 'update'])->middleware('permission:orders,update');
		Route::post('/{id}/confirm', [OrderController::class, 'confirm'])->middleware('permission:orders,update');
		Route::post('/{id}/cancel', [OrderController::class, 'cancel'])->middleware('permission:orders,update');
	});
	
	Route::middleware('jwt')->prefix('stock-in')->group(function () {
		Route::get('/', [StockInController::class, 'index'])->middleware('permission:inventory,view');
		Route::post('/', [StockInController::class, 'store'])->middleware('permission:inventory,create');
		Route::get('/{id}', [StockInController::class, 'show'])->middleware('permission:inventory,view');
		Route::put('/{id}', [StockInController::class, 'update'])->middleware('permission:inventory,update');
		Route::post('/{id}/confirm', [StockInController::class, 'confirm'])->middleware('permission:inventory,update');
		Route::post('/{id}/cancel', [StockInController::class, 'cancel'])->middleware('permission:inventory,update');
	});
	
	Route::middleware('jwt')->prefix('stock-out')->group(function () {
		Route::get('/', [StockOutController::class, 'index'])->middleware('permission:inventory,view');
		Route::post('/', [StockOutController::class, 'store'])->middleware('permission:inventory,create');
		Route::get('/{id}', [StockOutController::class, 'show'])->middleware('permission:inventory,view');
		Route::put('/{id}', [StockOutController::class, 'update'])->middleware('permission:inventory,update');
		Route::post('/{id}/confirm', [StockOutController::class, 'confirm'])->middleware('permission:inventory,update');
		Route::post('/{id}/cancel', [StockOutController::class, 'cancel'])->middleware('permission:inventory,update');
	});
	
	Route::middleware('jwt')->prefix('stock-adjustments')->group(function () {
		Route::get('/', [StockAdjustmentController::class, 'index'])->middleware('permission:inventory,view');
		Route::post('/', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory,create');
		Route::get('/{id}', [StockAdjustmentController::class, 'show'])->middleware('permission:inventory,view');
		Route::put('/{id}', [StockAdjustmentController::class, 'update'])->middleware('permission:inventory,update');
		Route::post('/{id}/confirm', [StockAdjustmentController::class, 'confirm'])->middleware('permission:inventory,update');
		Route::post('/{id}/cancel', [StockAdjustmentController::class, 'cancel'])->middleware('permission:inventory,update');
	});
	
	Route::middleware('jwt')->prefix('payments')->group(function () {
		Route::get('/', [PaymentController::class, 'index'])->middleware('permission:payments,view');
		Route::post('/', [PaymentController::class, 'store'])->middleware('permission:payments,create');
		Route::get('/{id}', [PaymentController::class, 'show'])->middleware('permission:payments,view');
		Route::put('/{id}', [PaymentController::class, 'update'])->middleware('permission:payments,update');
		Route::post('/{id}/confirm', [PaymentController::class, 'confirm'])->middleware('permission:payments,update');
		Route::post('/{id}/cancel', [PaymentController::class, 'cancel'])->middleware('permission:payments,update');
	});
	
	Route::middleware('jwt')->prefix('inventory')->group(function () {
		Route::get('/stocks', [InventoryController::class, 'index'])->middleware('permission:inventory,view');
	});

// Route test thủ công được giữ lại để debug nhanh trong môi trường local.
	Route::get('/test', [TestController::class, 'twoSum']);
