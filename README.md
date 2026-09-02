# 🏭 Hệ Thống Quản Lý Kho Xưởng Thông Minh (Smart Warehouse Management System - WMS)

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Status](https://img.shields.io/badge/Status-Completed-success?style=for-the-badge)](#)
[![License](https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge)](LICENSE)

Hệ thống Quản lý Kho xưởng hiện đại, tối ưu hóa quy trình luân chuyển hàng hóa, theo dõi vị trí chính xác đến từng thùng hàng/vị trí kệ, tự động hóa xuất kho theo nguyên tắc **FIFO/FEFO**, cảnh báo tồn kho thời gian thực và trích xuất báo cáo Nhập - Xuất - Tồn chuyên nghiệp.

---

## 🌟 Tính Năng Nổi Bật

### 1. 📥 Quản lý Nhập Kho (Inbound)
- **Quy cách đóng thùng linh hoạt**: Cho phép nhập hàng theo quy cách động (thùng chẵn, thùng lẻ, số lượng cái/thùng tùy biến).
- **Định vị vị trí lưu kho chi tiết**: Gán từng mã thùng hàng (`Carton Code`) vào từng vị trí tọa độ kệ cụ thể (Dãy/Zone - Kệ/Rack - Tầng/Level).
- **Mã vạch & Lô hàng (Lot/Batch)**: Quản lý theo số Lot, hạn dùng và mã Barcode chuẩn EAN/UPC.
- **Hỗ trợ đầy đủ CRUD**: Tạo mới, xem chi tiết, cập nhật phiếu và xóa phiếu (tự động đồng bộ trừ tồn kho tương ứng).

### 2. 📤 Quản lý Xuất Kho (Outbound) & Thuật toán Gợi ý Tối ưu
- **Gợi ý xuất kho thông minh (FIFO / FEFO)**: Tự động tìm kiếm các thùng nhập trước hoặc có hạn sử dụng gần nhất để đề xuất lấy hàng trước, giảm thiểu hư hao hàng hóa.
- **Tự động trừ số lượng thùng**: Xử lý linh hoạt việc xuất nguyên thùng hoặc cắt lẻ thùng hàng, tự động cập nhật trạng thái thùng (`IN_STOCK` -> `EXPORTED`).
- **Khôi phục tồn khi hủy phiếu**: Nếu xóa hoặc chỉnh sửa phiếu xuất, hệ thống hoàn trả chính xác số lượng về đúng thùng hàng cũ.

### 3. 🗺️ Sơ đồ Kho Trực quan (Warehouse Zone Map)
- **Bản đồ 140 vị trí kho**: Trực quan hóa 7 dãy (Zone A đến G), mỗi dãy 5 kệ (Rack 1-5), mỗi kệ 4 tầng (Level 1-4).
- **Màu sắc trạng thái vị trí**: Trực quan ô vị trí trống (Empty) và ô đang chứa hàng (Occupied), hỗ trợ click xem nhanh chi tiết hàng đang lưu trong vị trí đó.

### 4. 🔔 Hệ thống Cảnh báo & Giám sát Thời gian thực (Real-time Notification)
- **Cảnh báo hết hàng (Out of stock)**: Báo động tức thời khi số lượng tồn = 0.
- **Cảnh báo tồn kho dưới ngưỡng an toàn (Low stock)**: So sánh với định mức tối thiểu (`min_stock`) của từng sản phẩm.
- **Cảnh báo thùng sắp cạn**: Phát hiện các thùng lẻ còn dưới 10% số lượng.
- **Cảnh báo vượt mức tồn kho (Over stock)**: Cảnh báo khi tồn kho vượt quá dung lượng cho phép (`max_stock`).
- **Real-time Polling & Toast Notification**: Tự động thông báo khi có đồng nghiệp vừa tạo phiếu nhập/xuất mới trên hệ thống.

### 5. 📊 Báo cáo Nhập - Xuất - Tồn (NXT) & Xuất Excel
- Thống kê chi tiết: **Tồn đầu kỳ**, **Nhập trong kỳ**, **Xuất trong kỳ**, **Tồn cuối kỳ**, **Đơn giá & Thành tiền**.
- Bộ lọc linh hoạt theo ngày hoặc khoảng thời gian.
- Hỗ trợ xuất dữ liệu ra file Excel định dạng bảng chuẩn cho kế toán và quản lý.

### 6. 🛡️ Nhật ký Hệ thống (Audit Logs) & Phân quyền (RBAC)
- **Audit Logs**: Ghi vết lịch sử mọi tác vụ quan trọng (Ai đã nhập, xuất, sửa phiếu lúc mấy giờ).
- **Phân quyền đa cấp độ (Role-Based Access Control)**:
  - 👑 **Admin**: Toàn quyền cấu hình người dùng, danh mục và quản trị hệ thống.
  - 💼 **Manager (Quản lý kho)**: Quản lý danh mục, xem báo cáo, giám sát nhập/xuất.
  - 📋 **Accountant (Kế toán)**: Xem báo cáo NXT, số liệu giá trị kho và xuất Excel.
  - 📦 **Staff (Thủ kho)**: Trực tiếp tạo phiếu nhập kho, thực hiện xuất kho và theo dõi sơ đồ.

---

## 🏗️ Kiến Trúc Công Nghệ

- **Backend**: Laravel 12.x / PHP 8.2+
- **Database**: MySQL 8.0+
- **Kiến trúc mã nguồn**: Service Pattern (`InboundService`, `OutboundService`, `ReportService`) tách biệt nghiệp vụ với Controller.
- **Frontend / UI**: Laravel Blade Templates, Custom Vanilla CSS Dark Mode / Glassmorphism, FontAwesome 6, Google Font Outfit.
- **Testing**: PHPUnit / Laravel Feature Tests (đạt 100% assertions thành công).

---

## 🚀 Hướng Dẫn Cài Đặt & Chạy Dự Án

### Yêu cầu môi trường
- PHP >= 8.2 (kèm các extension: pdo_mysql, mbstring, openssl, xml, curl, zip)
- Composer 2.x
- MySQL hoặc MariaDB
- Git

### Các bước cài đặt:

1. **Clone mã nguồn về máy**:
   ```bash
   git clone https://github.com/Minhduc32/duankho.git
   cd duankho
   ```

2. **Cài đặt các thư viện phụ thuộc (Dependencies)**:
   ```bash
   composer install
   ```

3. **Cấu hình file môi trường (.env)**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Mở file `.env` và thiết lập kết nối cơ sở dữ liệu MySQL:*
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=kho_xuong
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Chạy Migration và nạp dữ liệu mẫu (Seeder)**:
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Khởi chạy ứng dụng**:
   ```bash
   php artisan serve
   ```
   Truy cập trên trình duyệt tại: `http://127.0.0.1:8000`

---

## 👥 Tài Khoản Đăng Nhập Mẫu

Hệ thống đã chuẩn bị sẵn các tài khoản demo tương ứng với 4 vai trò:

| Vai trò | Tên tài khoản | Mật khẩu | Chức năng chính |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin` | `admin123` | Toàn quyền hệ thống |
| **Quản lý kho (Manager)** | `manager` | `manager123` | Quản lý sản phẩm, sơ đồ, báo cáo |
| **Kế toán (Accountant)** | `accountant` | `accountant123` | Kiểm tra tồn kho, xuất báo cáo NXT |
| **Thủ kho (Staff)** | `staff` | `staff123` | Thực hiện Nhập/Xuất kho hàng ngày |

---

## 🧪 Kiểm Thử Tự Động (Testing)

Dự án đi kèm bộ kiểm thử tự động toàn diện bao gồm: luồng nhập kho, thuật toán xuất kho FIFO, sửa/xóa phiếu và tính toán bảng NXT.

Chạy kiểm thử:
```bash
php artisan test
```

Kết quả:
```text
PASS  Tests\Feature\WarehouseTest
✓ full warehouse flow
✓ inbound edit and delete
✓ outbound edit and delete
✓ user registration

Tests:    4 passed (36 assertions)
Duration: ~1.2s
```

---

## 📄 Bản Quyền (License)

Dự án được phân phối dưới giấy phép **MIT License**. Tự do sử dụng, chỉnh sửa và đóng góp cho mục đích học tập cũng như thương mại.
