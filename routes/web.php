<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Notifications API
    Route::get('/api/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::get('/api/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');

    // Dashboard & Zones
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/zone-map', [DashboardController::class, 'zoneMap'])->name('zone-map');
    Route::get('/audit-logs', [DashboardController::class, 'auditLogs'])->name('audit-logs');

    // Products Catalog
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        
        // Write Actions (Admin & Manager)
        Route::middleware('role:admin,manager')->group(function () {
            Route::post('/', [ProductController::class, 'store'])->name('store');
            Route::post('/{id}', [ProductController::class, 'update'])->name('update');
            Route::get('/delete/{id}', [ProductController::class, 'destroy'])->name('destroy');
        });
    });

    // Inbound (Nhập kho)
    Route::prefix('inbound')->name('inbound.')->group(function () {
        Route::get('/', [ReceiptController::class, 'index'])->name('index');
        Route::get('/view/{id}', [ReceiptController::class, 'show'])->name('show');

        // Write Actions (Admin & Staff)
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('/create', [ReceiptController::class, 'create'])->name('create');
            Route::post('/store', [ReceiptController::class, 'store'])->name('store');
            Route::get('/edit/{id}', [ReceiptController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [ReceiptController::class, 'update'])->name('update');
            Route::get('/delete/{id}', [ReceiptController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('outbound')->name('outbound.')->group(function () {
        Route::get('/', [IssueController::class, 'index'])->name('index');
        Route::get('/view/{id}', [IssueController::class, 'show'])->name('show');
        Route::get('/suggest-allocation', [IssueController::class, 'suggest'])->name('suggest');
        Route::get('/product-info', [IssueController::class, 'productInfo'])->name('product-info');

        // Write Actions (Admin & Staff)
        Route::middleware('role:admin,staff')->group(function () {
            Route::get('/create', [IssueController::class, 'create'])->name('create');
            Route::post('/store', [IssueController::class, 'store'])->name('store');
            Route::get('/edit/{id}', [IssueController::class, 'edit'])->name('edit');
            Route::post('/update/{id}', [IssueController::class, 'update'])->name('update');
            Route::get('/delete/{id}', [IssueController::class, 'destroy'])->name('destroy');
        });
    });

    // Reports (Báo cáo)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export');
        Route::get('/export-inbound', [ReportController::class, 'exportInbound'])->name('export.inbound');
        Route::get('/export-outbound', [ReportController::class, 'exportOutbound'])->name('export.outbound');
        Route::get('/export-inventory', [ReportController::class, 'exportInventory'])->name('export.inventory');
        Route::get('/export-occupancy', [ReportController::class, 'exportOccupancy'])->name('export.occupancy');
        Route::get('/export-audit', [ReportController::class, 'exportAudit'])->name('export.audit');
    });
});
