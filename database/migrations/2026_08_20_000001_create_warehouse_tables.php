<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // CREATE, UPDATE, DELETE...
            $table->string('table_name');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('barcode')->nullable();
            $table->string('category')->nullable();
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->default(99999);
            $table->timestamps();
        });

        // 3. Locations
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('zone'); // A, B, C, D, E, F, G
            $table->string('rack'); // Kệ 1, Kệ 2...
            $table->string('level'); // Tầng 1, Tầng 2...
            $table->string('barcode')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unique(['zone', 'rack', 'level']);
        });

        // 4. Receipts (Inbound Vouchers)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_code')->unique();
            $table->string('po_number')->nullable();
            $table->dateTime('receipt_date');
            $table->foreignId('creator_id')->constrained('users');
            $table->string('status')->default('COMPLETED'); // DRAFT, COMPLETED, CANCELLED
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 5. Receipt Details
        Schema::create('receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('lot_number');
            $table->integer('total_cartons');
            $table->integer('total_pieces');
            $table->decimal('price', 15, 2)->default(0);
            $table->timestamps();
        });

        // 6. Inventory Cartons (Single units of dynamic packaging cartons)
        Schema::create('inventory_cartons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_detail_id')->constrained('receipt_details')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('carton_code')->unique();
            $table->integer('original_pieces');
            $table->integer('current_pieces');
            $table->foreignId('location_id')->constrained('locations');
            $table->string('status')->default('IN_STOCK'); // IN_STOCK, EXPORTED, DAMAGED
            $table->dateTime('received_at');
            $table->timestamps();
        });

        // 7. Issues (Outbound Vouchers)
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_code')->unique();
            $table->string('order_number')->nullable();
            $table->dateTime('issue_date');
            $table->foreignId('creator_id')->constrained('users');
            $table->string('status')->default('COMPLETED'); // DRAFT, COMPLETED, CANCELLED
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // 8. Issue Details
        Schema::create('issue_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('requested_pieces');
            $table->integer('actual_pieces');
            $table->timestamps();
        });

        // 9. Issue Allocations (Cartons picking allocation details)
        Schema::create('issue_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_detail_id')->constrained('issue_details')->cascadeOnDelete();
            $table->foreignId('inventory_carton_id')->constrained('inventory_cartons');
            $table->integer('pieces_issued');
            $table->timestamps();
        });

        // 10. Stock Adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('inventory_carton_id')->nullable()->constrained('inventory_cartons');
            $table->foreignId('user_id')->constrained('users');
            $table->string('type'); // DAMAGE, STOCKTAKE_DIFF, LOSS
            $table->integer('pieces_delta');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('issue_allocations');
        Schema::dropIfExists('issue_details');
        Schema::dropIfExists('issues');
        Schema::dropIfExists('inventory_cartons');
        Schema::dropIfExists('receipt_details');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('audit_logs');
    }
};
