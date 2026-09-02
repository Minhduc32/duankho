<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Roles
        echo "Gieo dữ liệu Roles...\n";
        $roles = [
            ['id' => 1, 'name' => 'admin', 'description' => 'Quản trị hệ thống'],
            ['id' => 2, 'name' => 'manager', 'description' => 'Quản lý kho'],
            ['id' => 3, 'name' => 'accountant', 'description' => 'Kế toán kho'],
            ['id' => 4, 'name' => 'staff', 'description' => 'Thủ kho']
        ];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['id' => $role['id']], $role);
        }

        // 2. Seed Users
        echo "Gieo dữ liệu Users...\n";
        $users = [
            ['id' => 1, 'username' => 'admin', 'password' => Hash::make('admin123'), 'full_name' => 'Nguyễn Quản Trị', 'role_id' => 1, 'is_active' => true],
            ['id' => 2, 'username' => 'manager', 'password' => Hash::make('manager123'), 'full_name' => 'Trần Quản Lý', 'role_id' => 2, 'is_active' => true],
            ['id' => 3, 'username' => 'accountant', 'password' => Hash::make('accountant123'), 'full_name' => 'Lê Kế Toán', 'role_id' => 3, 'is_active' => true],
            ['id' => 4, 'username' => 'staff', 'password' => Hash::make('staff123'), 'full_name' => 'Phạm Thủ Kho', 'role_id' => 4, 'is_active' => true],
        ];
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(['id' => $user['id']], $user);
        }

        // 3. Seed Products
        echo "Gieo dữ liệu Products...\n";
        $products = [
            ['id' => 1, 'sku' => 'PROD001', 'name' => 'Bia Heineken Lon 330ml', 'barcode' => '8934822200192', 'category' => 'Đồ uống', 'min_stock' => 100, 'max_stock' => 5000],
            ['id' => 2, 'sku' => 'PROD002', 'name' => 'Mì Hảo Hảo Tôm Chua Cay', 'barcode' => '8934561230044', 'category' => 'Mì ăn liền', 'min_stock' => 500, 'max_stock' => 10000],
            ['id' => 3, 'sku' => 'PROD003', 'name' => 'Sữa tươi TH True Milk ít đường 1L', 'barcode' => '8936079015012', 'category' => 'Sữa', 'min_stock' => 200, 'max_stock' => 3000],
            ['id' => 4, 'sku' => 'PROD004', 'name' => 'Nước ngọt Coca Cola 320ml', 'barcode' => '8935049500412', 'category' => 'Đồ uống', 'min_stock' => 300, 'max_stock' => 4000],
            ['id' => 5, 'sku' => 'PROD005', 'name' => 'Dầu ăn Simply 1L', 'barcode' => '8936015502125', 'category' => 'Gia vị', 'min_stock' => 100, 'max_stock' => 2000]
        ];
        foreach ($products as $p) {
            DB::table('products')->updateOrInsert(['id' => $p['id']], $p);
        }

        // 4. Seed Locations (140 positions: 7 zones x 5 racks x 4 levels)
        echo "Gieo dữ liệu Locations (140 vị trí)...\n";
        $zones = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $locations = [];
        $locId = 1;
        foreach ($zones as $z) {
            for ($r = 1; $r <= 5; $r++) {
                for ($l = 1; $l <= 4; $l++) {
                    $locations[] = [
                        'id' => $locId,
                        'zone' => $z,
                        'rack' => 'Kệ ' . $r,
                        'level' => 'Tầng ' . $l,
                        'barcode' => $z . $r . $l,
                        'is_active' => true
                    ];
                    $locId++;
                }
            }
        }
        
        // Chunk locations to insert efficiently
        foreach (array_chunk($locations, 50) as $chunk) {
            foreach ($chunk as $loc) {
                DB::table('locations')->updateOrInsert(['id' => $loc['id']], $loc);
            }
        }

        // 5. Seed Inbound Receipts & Cartons
        if (!app()->environment('testing') && DB::table('receipts')->count() === 0) {
            echo "Gieo dữ liệu Phiếu Nhập & Thùng Hàng mẫu...\n";
            // Receipt 1
            $receipt1Id = DB::table('receipts')->insertGetId([
                'receipt_code' => 'GDN-260819-001',
                'po_number'    => 'PO-2026-HEI',
                'receipt_date' => now()->subDays(5)->toDateTimeString(),
                'creator_id'   => 4, // staff
                'status'       => 'COMPLETED',
                'note'         => 'Nhập bia Heineken lon chuẩn bị cho lễ',
                'created_at'   => now()->subDays(5),
                'updated_at'   => now()->subDays(5)
            ]);

            $rd1Id = DB::table('receipt_details')->insertGetId([
                'receipt_id'    => $receipt1Id,
                'product_id'    => 1, // Bia Heineken
                'lot_number'    => 'LOT-HEI-001',
                'price'         => 15000,
                'total_cartons' => 2,
                'total_pieces'  => 240,
                'created_at'    => now()->subDays(5),
                'updated_at'    => now()->subDays(5)
            ]);

            DB::table('inventory_cartons')->insert([
                [
                    'receipt_detail_id' => $rd1Id,
                    'product_id'        => 1,
                    'carton_code'       => 'C-LOT-HEI-001-PROD001-01',
                    'original_pieces'   => 120,
                    'current_pieces'    => 120,
                    'location_id'       => 1, // A-1-1
                    'received_at'       => now()->subDays(5)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(5),
                    'updated_at'        => now()->subDays(5)
                ],
                [
                    'receipt_detail_id' => $rd1Id,
                    'product_id'        => 1,
                    'carton_code'       => 'C-LOT-HEI-001-PROD001-02',
                    'original_pieces'   => 120,
                    'current_pieces'    => 120,
                    'location_id'       => 2, // A-1-2
                    'received_at'       => now()->subDays(5)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(5),
                    'updated_at'        => now()->subDays(5)
                ]
            ]);

            // Receipt 2
            $receipt2Id = DB::table('receipts')->insertGetId([
                'receipt_code' => 'GDN-260819-002',
                'po_number'    => 'PO-2026-HAO',
                'receipt_date' => now()->subDays(3)->toDateTimeString(),
                'creator_id'   => 4, // staff
                'status'       => 'COMPLETED',
                'note'         => 'Nhập mì Hảo Hảo Tôm Chua Cay',
                'created_at'   => now()->subDays(3),
                'updated_at'   => now()->subDays(3)
            ]);

            $rd2Id = DB::table('receipt_details')->insertGetId([
                'receipt_id'    => $receipt2Id,
                'product_id'    => 2, // Mì Hảo Hảo
                'lot_number'    => 'LOT-HAO-99',
                'price'         => 3200,
                'total_cartons' => 2,
                'total_pieces'  => 400,
                'created_at'    => now()->subDays(3),
                'updated_at'    => now()->subDays(3)
            ]);

            DB::table('inventory_cartons')->insert([
                [
                    'receipt_detail_id' => $rd2Id,
                    'product_id'        => 2,
                    'carton_code'       => 'C-LOT-HAO-99-PROD002-01',
                    'original_pieces'   => 200,
                    'current_pieces'    => 200,
                    'location_id'       => 21, // B-1-1
                    'received_at'       => now()->subDays(3)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(3),
                    'updated_at'        => now()->subDays(3)
                ],
                [
                    'receipt_detail_id' => $rd2Id,
                    'product_id'        => 2,
                    'carton_code'       => 'C-LOT-HAO-99-PROD002-02',
                    'original_pieces'   => 200,
                    'current_pieces'    => 200,
                    'location_id'       => 22, // B-1-2
                    'received_at'       => now()->subDays(3)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(3),
                    'updated_at'        => now()->subDays(3)
                ]
            ]);

            // Receipt 3
            $receipt3Id = DB::table('receipts')->insertGetId([
                'receipt_code' => 'GDN-260819-003',
                'po_number'    => 'PO-2026-MILK',
                'receipt_date' => now()->subDays(1)->toDateTimeString(),
                'creator_id'   => 4, // staff
                'status'       => 'COMPLETED',
                'note'         => 'Nhập sữa TH True Milk',
                'created_at'   => now()->subDays(1),
                'updated_at'   => now()->subDays(1)
            ]);

            $rd3Id = DB::table('receipt_details')->insertGetId([
                'receipt_id'    => $receipt3Id,
                'product_id'    => 3, // TH Milk
                'lot_number'    => 'LOT-MILK-102',
                'price'         => 34000,
                'total_cartons' => 2,
                'total_pieces'  => 100,
                'created_at'    => now()->subDays(1),
                'updated_at'    => now()->subDays(1)
            ]);

            DB::table('inventory_cartons')->insert([
                [
                    'receipt_detail_id' => $rd3Id,
                    'product_id'        => 3,
                    'carton_code'       => 'C-LOT-MILK-102-PROD003-01',
                    'original_pieces'   => 50,
                    'current_pieces'    => 50,
                    'location_id'       => 41, // C-1-1
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ],
                [
                    'receipt_detail_id' => $rd3Id,
                    'product_id'        => 3,
                    'carton_code'       => 'C-LOT-MILK-102-PROD003-02',
                    'original_pieces'   => 50,
                    'current_pieces'    => 50,
                    'location_id'       => 42, // C-1-2
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ]
            ]);

            // Add Coca Cola to D, E, F, G
            $rd4Id = DB::table('receipt_details')->insertGetId([
                'receipt_id'    => $receipt3Id,
                'product_id'    => 4,
                'lot_number'    => 'LOT-COCA-55',
                'price'         => 8500,
                'total_cartons' => 4,
                'total_pieces'  => 960,
                'created_at'    => now()->subDays(1),
                'updated_at'    => now()->subDays(1)
            ]);

            DB::table('inventory_cartons')->insert([
                [
                    'receipt_detail_id' => $rd4Id,
                    'product_id'        => 4,
                    'carton_code'       => 'C-LOT-COCA-55-PROD004-01',
                    'original_pieces'   => 240,
                    'current_pieces'    => 240,
                    'location_id'       => 61, // D-1-1
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ],
                [
                    'receipt_detail_id' => $rd4Id,
                    'product_id'        => 4,
                    'carton_code'       => 'C-LOT-COCA-55-PROD004-02',
                    'original_pieces'   => 240,
                    'current_pieces'    => 240,
                    'location_id'       => 81, // E-1-1
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ],
                [
                    'receipt_detail_id' => $rd4Id,
                    'product_id'        => 4,
                    'carton_code'       => 'C-LOT-COCA-55-PROD004-03',
                    'original_pieces'   => 240,
                    'current_pieces'    => 240,
                    'location_id'       => 101, // F-1-1
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ],
                [
                    'receipt_detail_id' => $rd4Id,
                    'product_id'        => 4,
                    'carton_code'       => 'C-LOT-COCA-55-PROD004-04',
                    'original_pieces'   => 240,
                    'current_pieces'    => 240,
                    'location_id'       => 121, // G-1-1
                    'received_at'       => now()->subDays(1)->toDateTimeString(),
                    'status'            => 'IN_STOCK',
                    'created_at'        => now()->subDays(1),
                    'updated_at'        => now()->subDays(1)
                ]
            ]);
        }
        
        echo "Gieo dữ liệu thành công!\n";
    }
}
