<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    LandController,
    CycleController,
    PhaseController,
    WarehouseController,
    ItemController,
    MovementController,
    NeedController
};
use App\Http\Controllers\Admin\{
    UserController,
    CropController,
    StageController,
    AdminCycleController,
    AdminPhaseController,
    AdminWarehouseController,
    AdminItemController,
    AdminLandController,
    AdminMovementController,
    AdminNeedController
};

Route::prefix('auth')->group(function () {
    // ========== PUBLIC ==========
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // ========== PROTECTED (login required) ==========
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::delete('/delete', [AuthController::class, 'deleteAccount']);

        // Token management (optional)
        Route::get('/tokens', [AuthController::class, 'listTokens']);
        Route::delete('/tokens', [AuthController::class, 'revokeAllTokens']);
        Route::delete('/tokens/{id}', [AuthController::class, 'revokeToken']);
    });

    // ========== ADMIN ==========
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::get('/roles', [UserController::class, 'roles']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes — CROPS & STAGES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // 🌱 CROPS MANAGEMENT
    Route::prefix('crops')->group(function () {
        Route::get('/', [CropController::class, 'index']);          // List semua crops
        Route::get('/{id}', [CropController::class, 'show']);       // Detail crop (bisa include stages)
        Route::post('/', [CropController::class, 'store']);         // Tambah crop baru
        Route::put('/{id}', [CropController::class, 'update']);     // Update crop
        Route::delete('/{id}', [CropController::class, 'destroy']); // Hapus crop

        // 🌿 STAGES NESTED UNDER CROP
        Route::get('/{id}/stages', [StageController::class, 'indexByCrop']);    // List stages by crop
        Route::post('/{id}/stages', [StageController::class, 'storeByCrop']);   // Tambah stage ke crop
    });

    // 🌿 STAGES MANAGEMENT (direct access)
    Route::prefix('stages')->group(function () {
        Route::get('/', [StageController::class, 'index']);          // List semua stages
        Route::get('/{id}', [StageController::class, 'show']);       // Detail stage
        Route::put('/{id}', [StageController::class, 'update']);     // Update stage
        Route::delete('/{id}', [StageController::class, 'destroy']); // Hapus stage
    });
});

/*
|--------------------------------------------------------------------------
| Farmer Routes — CROPS (Template List)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:farmer'])->prefix('farmer')->group(function () {
    Route::get('/crops', [CropController::class, 'listTemplates']);  // List crop template untuk farmer
});

// Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
//     return $request->user();
// });

// FARMER ROUTES
Route::middleware(['auth:sanctum', 'role:farmer'])->prefix('farmer')->group(function () {
    Route::prefix('cycles')->group(function () {
        Route::get('/', [CycleController::class, 'index']);
        Route::get('/{cycle}', [CycleController::class, 'show']);
        Route::post('/', [CycleController::class, 'store']);
        Route::put('/{cycle}', [CycleController::class, 'update']);
        Route::delete('/{cycle}', [CycleController::class, 'destroy']);

        Route::prefix('{cycle}/phases')->group(function () {
            Route::get('/', [PhaseController::class, 'index']);
            Route::get('/{phase}', [PhaseController::class, 'show']);
            Route::put('/{phase}', [PhaseController::class, 'update']);
        });
    });

    Route::get('lands/{land}/cycles', [CycleController::class, 'listByLand']);
});

// ADMIN ROUTES (READ-ONLY)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::prefix('cycles')->group(function () {
        Route::get('/', [AdminCycleController::class, 'index']);
        Route::get('/{cycle}', [AdminCycleController::class, 'show']);
    });

    Route::prefix('phases')->group(function () {
        Route::get('/', [AdminPhaseController::class, 'index']);
        Route::get('/{phase}', [AdminPhaseController::class, 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Warehouse Routes —
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:farmer'])
    ->prefix('farmer')
    ->group(function () {

        // WAREHOUSE (owned by farmer)
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index']);       
            Route::post('/', [WarehouseController::class, 'store']);      
            Route::put('/{warehouse}', [WarehouseController::class, 'update']);
            Route::get('/{warehouse}', [WarehouseController::class, 'show']);
            Route::delete('/{warehouse}', [WarehouseController::class, 'destroy']);
        });

        // ITEM MANAGEMENT (dalam warehouse)
        Route::prefix('items')->group(function () {
            Route::get('/warehouse/{warehouse_id}', [ItemController::class, 'indexByWarehouse']); 
            Route::post('/warehouse/{warehouse_id}', [ItemController::class, 'store']);           
            Route::get('/{item}', [ItemController::class, 'show']);                                 
            Route::put('/{item}', [ItemController::class, 'update']);                               
            Route::delete('/{item}', [ItemController::class, 'destroy']);                           
        });

        // MOVEMENTS (stok keluar-masuk)
        Route::prefix('movements')->group(function () {
            Route::get('/', [MovementController::class, 'index']);           
            Route::post('/', [MovementController::class, 'store']);          
            Route::get('/{id}', [MovementController::class, 'show']);        
        });

        // NEEDS (hubungan ke cycle_stages)
        Route::prefix('needs')->group(function () {
            Route::get('/stage/{cycle_stage_id}', [NeedController::class, 'index']); 
            Route::post('/stage/{cycle_stage_id}/request', [NeedController::class, 'requestFromWarehouse']); 
            Route::put('/{id}/fulfill', [NeedController::class, 'fulfill']); 
        });
    });

// ADMIN ROUTES (READ-ONLY)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // WAREHOUSE MONITORING
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [AdminWarehouseController::class, 'index']);     
        Route::get('/{warehouse}', [AdminWarehouseController::class, 'show']);   
    });

    // ITEM MONITORING
    Route::prefix('items')->group(function () {
        Route::get('/', [AdminItemController::class, 'index']);           
        Route::get('/{id}', [AdminItemController::class, 'show']);        
    });

    // MOVEMENT MONITORING
    Route::prefix('movements')->group(function () {
        Route::get('/', [AdminMovementController::class, 'index']);       
        Route::get('/{id}', [AdminMovementController::class, 'show']);    
    });

    // NEEDS MONITORING
    Route::prefix('needs')->group(function () {
        Route::get('/', [AdminNeedController::class, 'index']);           
        Route::get('/{id}', [AdminNeedController::class, 'show']);        
    });
});

Route::middleware(['auth:sanctum', 'role:farmer'])->prefix('farmer')->group(function () {
    Route::prefix('lands')->group(function () {
        Route::get('/', [LandController::class, 'index']);
        Route::post('/', [LandController::class, 'store']);
        Route::get('{id}', [LandController::class, 'show']);
        Route::put('{id}', [LandController::class, 'update']);
        Route::delete('{id}', [LandController::class, 'destroy']);
    });
});
Route::middleware(['auth:sanctum', 'role: admin'])->prefix('admin')->group(function () {
    Route::prefix('lands')->group(function () {
        Route::get('/', [AdminLandController::class, 'index']);
        Route::get('{id}', [AdminLandController::class, 'show']);
    });
});

