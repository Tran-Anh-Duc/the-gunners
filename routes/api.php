<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ModuleController;



// Các route auth (login, register, logout, me)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('jwt')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Quản lý user trong auth, vì muốn nó nằm trong auth
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware('permission:user_management,view');
            Route::post('/', [UserController::class, 'store'])->middleware('permission:user_management,add');
            Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:user_management,edit');
            Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:user_management,delete');
        });
    });
});

// Route riêng cho actions, nằm ngoài auth, nhưng vẫn cần token (jwt)
Route::middleware('jwt')->prefix('actions')->group(function () {
    Route::get('/', [ActionController::class, 'index'])->name('actions.index');
    Route::post('/', [ActionController::class, 'store'])->name('actions.store');
    Route::get('/{id}', [ActionController::class, 'show'])->name('actions.show');
    Route::put('/{id}', [ActionController::class, 'update'])->name('actions.update');
    Route::delete('/{id}', [ActionController::class, 'destroy'])->name('actions.destroy');
    Route::put('/restore/{id}', [ActionController::class, 'restore'])->name('actions.restore');
});


// Route riêng cho role, nằm ngoài auth, nhưng vẫn cần token (jwt)
Route::middleware('jwt')->prefix('role')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('role.index');
    Route::post('/', [RoleController::class, 'store'])->name('role.store');
    Route::get('/{id}', [RoleController::class, 'show'])->name('role.show');
    Route::put('/{id}', [RoleController::class, 'updateRole'])->name('role.updateRole');
    Route::delete('/{id}', [RoleController::class, 'destroy'])->name('role.destroy');
    Route::put('/restore/{id}', [RoleController::class, 'restore'])->name('role.restore');

    //update roles users.
    Route::post('/update-role-user', [RoleController::class, 'updateRoleUser'])->name('role.update_role_user');
    //update permissions users
    Route::post('/update-permission-user', [RoleController::class, 'updatePermissionUser'])->name('role.permission_user');
    //update  permissions roles
    Route::post('/update-permission-role', [RoleController::class, 'updatePermissionRole'])->name('role.permission_role');
});

// Route riêng cho module, nằm ngoài auth, nhưng vẫn cần token (jwt)
Route::middleware('jwt')->prefix('module')->group(function () {
    Route::get('/', [ModuleController::class, 'index'])->name('module.index');
    Route::post('/', [ModuleController::class, 'store'])->name('module.store');
    Route::get('/{id}', [ModuleController::class, 'show'])->name('module.show');
    Route::put('/{id}', [ModuleController::class, 'update'])->name('module.update');
    Route::delete('/{id}', [ModuleController::class, 'destroy'])->name('module.destroy');
    Route::put('/restore/{id}', [ModuleController::class, 'restore'])->name('module.restore');
});


