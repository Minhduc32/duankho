<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Location;
use App\Models\Receipt;
use App\Models\InventoryCarton;
use App\Models\Issue;
use App\Services\InboundService;
use App\Services\OutboundService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_full_warehouse_flow()
    {
        $this->seed();

        // 1. Giả lập đăng nhập Thủ kho (username: staff, role_id = 4)
        $user = User::where('username', 'staff')->first();
        $this->assertNotNull($user, "Thủ kho mẫu 'staff' phải tồn tại.");
        
        $this->actingAs($user);

        // Lấy sản phẩm và vị trí test
        $prod1 = Product::where('sku', 'PROD001')->first();
        $prod2 = Product::where('sku', 'PROD002')->first();
        
        $loc1 = Location::find(1); // Dãy A Kệ 1 Tầng 1
        $loc2 = Location::find(2); // Dãy A Kệ 1 Tầng 2
        $loc3 = Location::find(21); // Dãy B Kệ 1 Tầng 1

        $this->assertNotNull($prod1);
        $this->assertNotNull($prod2);
        $this->assertNotNull($loc1);
        $this->assertNotNull($loc2);

        // 2. Kiểm thử InboundService (Nhập kho quy cách động)
        $inboundService = app(InboundService::class);

        $items = [
            [
                'product_id' => $prod1->id,
                'lot_number' => 'LOT-TEST-01',
                'price'      => 12000,
                'cartons'    => [
                    ['pieces' => 10, 'location_id' => $loc1->id], // Thùng 1: 10 cái ở loc1
                    ['pieces' => 20, 'location_id' => $loc2->id], // Thùng 2: 20 cái ở loc2
                ]
            ],
            [
                'product_id' => $prod2->id,
                'lot_number' => 'LOT-TEST-02',
                'price'      => 5000,
                'cartons'    => [
                    ['pieces' => 15, 'location_id' => $loc3->id], // Thùng 3: 15 cái ở loc3
                ]
            ]
        ];

        $receipt = $inboundService->createReceipt(
            'PO-FEATURE-TEST',
            now()->toDateTimeString(),
            $user->id,
            'Phiếu nhập kiểm thử tính năng',
            $items
        );

        $this->assertNotNull($receipt);
        $this->assertEquals('COMPLETED', $receipt->status);

        // Kiểm tra tồn Heineken (PROD001) trong db
        $stock1 = Product::getStockStatus($prod1->id);
        $this->assertEquals(30, $stock1->total_pieces);
        $this->assertEquals(2, $stock1->total_cartons);

        // 3. Kiểm thử OutboundService (Xuất kho thuật toán FIFO)
        $outboundService = app(OutboundService::class);

        // Heineken yêu cầu xuất 15 cái -> theo FIFO sẽ lấy:
        // - Hết Thùng 1: 10 cái (Carton C-LOT-TEST-01-PROD001-01)
        // - 5 cái từ Thùng 2: 20 - 5 = 15 cái còn lại
        $suggest = $outboundService->suggest($prod1->id, 15, 'FIFO');
        $this->assertEquals(0, $suggest['remaining_needed']);
        $this->assertCount(2, $suggest['allocations']);

        $issueItems = [
            [
                'product_id' => $prod1->id,
                'qty'        => 15
            ]
        ];

        $issue = $outboundService->createIssue(
            'SO-FEATURE-TEST',
            now()->toDateTimeString(),
            $user->id,
            'Phiếu xuất kiểm thử tính năng',
            $issueItems,
            'FIFO'
        );

        $this->assertNotNull($issue);

        // Kiểm tra tồn Heineken sau khi xuất
        $stockAfter = Product::getStockStatus($prod1->id);
        $this->assertEquals(15, $stockAfter->total_pieces);
        $this->assertEquals(1, $stockAfter->total_cartons); // Thùng 1 hết hàng nên status thành EXPORTED, chỉ còn lại 1 thùng

        // 4. Kiểm thử ReportService (Báo cáo NXT)
        $reportService = app(ReportService::class);
        $nxt = $reportService->getDailyNXT(date('Y-m-d'));
        
        $row1 = collect($nxt)->firstWhere('sku', 'PROD001');
        $this->assertEquals(0, $row1['opening_pieces']); // Tồn đầu = 0
        $this->assertEquals(30, $row1['in_pieces']);    // Nhập = 30
        $this->assertEquals(15, $row1['out_pieces']);   // Xuất = 15
        $this->assertEquals(15, $row1['closing_pieces']); // Tồn cuối = 15
    }

    public function test_inbound_edit_and_delete()
    {
        $this->seed();

        $user = User::where('username', 'staff')->first();
        $this->actingAs($user);

        $prod1 = Product::where('sku', 'PROD001')->first();
        $loc1 = Location::find(1);
        $loc2 = Location::find(2);

        $inboundService = app(InboundService::class);

        // 1. Tạo phiếu nhập ban đầu
        $items = [
            [
                'product_id' => $prod1->id,
                'lot_number' => 'LOT-EDIT-TEST',
                'price'      => 10000,
                'cartons'    => [
                    ['pieces' => 50, 'location_id' => $loc1->id],
                ]
            ]
        ];

        $receipt = $inboundService->createReceipt(
            'PO-EDIT-1',
            now()->toDateTimeString(),
            $user->id,
            'Ghi chú ban đầu',
            $items
        );

        $this->assertDatabaseHas('receipts', ['po_number' => 'PO-EDIT-1']);
        $this->assertDatabaseHas('inventory_cartons', ['original_pieces' => 50, 'location_id' => $loc1->id]);

        // 2. Cập nhật phiếu nhập (đổi sang loc2 và tăng số lượng lên 60)
        $newItems = [
            [
                'product_id' => $prod1->id,
                'lot_number' => 'LOT-EDIT-TEST',
                'price'      => 11000,
                'cartons'    => [
                    ['pieces' => 60, 'location_id' => $loc2->id],
                ]
            ]
        ];

        $inboundService->updateReceipt(
            $receipt->id,
            'PO-EDIT-2',
            now()->toDateTimeString(),
            $user->id,
            'Ghi chú cập nhật',
            $newItems
        );

        $this->assertDatabaseHas('receipts', ['po_number' => 'PO-EDIT-2', 'note' => 'Ghi chú cập nhật']);
        $this->assertDatabaseMissing('inventory_cartons', ['location_id' => $loc1->id]);
        $this->assertDatabaseHas('inventory_cartons', ['original_pieces' => 60, 'location_id' => $loc2->id]);

        // 3. Xóa phiếu nhập
        $inboundService->deleteReceipt($receipt->id, $user->id);

        $this->assertDatabaseMissing('receipts', ['id' => $receipt->id]);
        $this->assertDatabaseMissing('receipt_details', ['receipt_id' => $receipt->id]);
        $this->assertDatabaseMissing('inventory_cartons', ['location_id' => $loc2->id]);
    }

    public function test_outbound_edit_and_delete()
    {
        $this->seed();

        $user = User::where('username', 'staff')->first();
        $this->actingAs($user);

        $prod1 = Product::where('sku', 'PROD001')->first();
        $loc1 = Location::find(1);

        $inboundService = app(InboundService::class);
        $outboundService = app(OutboundService::class);

        // 1. Nhập hàng ban đầu: 50 cái Heineken tại loc1
        $receipt = $inboundService->createReceipt(
            'PO-OUT-TEST',
            now()->toDateTimeString(),
            $user->id,
            'Nhập hàng để test xuất',
            [
                [
                    'product_id' => $prod1->id,
                    'lot_number' => 'LOT-OUT-TEST',
                    'price'      => 10000,
                    'cartons'    => [
                        ['pieces' => 50, 'location_id' => $loc1->id],
                    ]
                ]
            ]
        );

        $carton = InventoryCarton::where('product_id', $prod1->id)->first();
        $this->assertEquals(50, $carton->current_pieces);

        // 2. Tạo phiếu xuất: xuất 20 cái Heineken
        $issue = $outboundService->createIssue(
            'SO-OUT-TEST-1',
            now()->toDateTimeString(),
            $user->id,
            'Xuất test',
            [
                [
                    'product_id' => $prod1->id,
                    'qty'        => 20
                ]
            ],
            'FIFO'
        );

        // Xác minh tồn kho bị trừ: 50 - 20 = 30
        $carton->refresh();
        $this->assertEquals(30, $carton->current_pieces);

        // 3. Cập nhật phiếu xuất: đổi số lượng xuất thành 30 cái
        $outboundService->updateIssue(
            $issue->id,
            'SO-OUT-TEST-2',
            now()->toDateTimeString(),
            $user->id,
            'Ghi chú cập nhật',
            [
                [
                    'product_id' => $prod1->id,
                    'qty'        => 30
                ]
            ],
            'FIFO'
        );

        // Xác minh tồn kho cập nhật: 50 - 30 = 20
        $carton->refresh();
        $this->assertEquals(20, $carton->current_pieces);

        // 4. Xóa phiếu xuất kho
        $outboundService->deleteIssue($issue->id, $user->id);

        // Xác minh tồn kho được khôi phục về ban đầu: 50
        $carton->refresh();
        $this->assertEquals(50, $carton->current_pieces);
        $this->assertEquals('IN_STOCK', $carton->status);
    }

    public function test_user_registration()
    {
        $this->seed();

        $response = $this->post(route('register.submit'), [
            'full_name' => 'Nguyễn Văn Đăng Ký',
            'username' => 'testregister',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'username' => 'testregister',
            'full_name' => 'Nguyễn Văn Đăng Ký',
            'role_id' => 4
        ]);
    }
}
