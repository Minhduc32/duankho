@extends('layouts.app', ['title' => 'Danh mục sản phẩm'])

@push('styles')
<style>
    .table-container {
        overflow-x: auto;
        margin-top: 1rem;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.95rem;
    }

    .table th, .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
    }

    .table th {
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .table tr:hover {
        background: rgba(255, 255, 255, 0.02);
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-success { background: rgba(16, 185, 129, 0.15); color: var(--success); }
    .badge-warning { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
    .badge-danger { background: rgba(239, 68, 68, 0.15); color: var(--danger); }

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1000;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: var(--bg-sidebar);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        width: 100%;
        max-width: 500px;
        padding: 2rem;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        position: relative;
        animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
    }

    .btn-close {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: 1.25rem;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-close:hover {
        color: white;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .form-group-full {
        grid-column: span 2;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: white;
        font-size: 0.95rem;
        transition: var(--transition);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Danh mục sản phẩm</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">Quản lý mã hàng SKU, mã vạch, danh mục và cấu hình định mức an toàn.</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <a href="{{ route('reports.index', ['tab' => 'inventory']) }}" class="btn btn-secondary" style="color: var(--success); border-color: rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-file-invoice"></i> Báo cáo tồn kho
            </a>
            @if (in_array(Auth::user()->role->name, ['admin', 'manager']))
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fa-solid fa-plus"></i> Thêm sản phẩm
                </button>
            @endif
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('products.index') }}" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
        <div style="flex: 2; min-width: 220px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm theo mã SKU, tên sản phẩm hoặc barcode...">
        </div>
        <div style="flex: 1; min-width: 160px;">
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tất cả danh mục --</option>
                @foreach ($categories as $c)
                    <option value="{{ $c }}" {{ request('category') == $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex: 1; min-width: 170px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="normal" {{ request('status') == 'normal' ? 'selected' : '' }}>Tồn kho bình thường</option>
                <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Dưới định mức (Cần nhập)</option>
                <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Hết hàng (0 cái)</option>
                <option value="over_stock" {{ request('status') == 'over_stock' ? 'selected' : '' }}>Vượt định mức tối đa</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fa-solid fa-filter"></i> Lọc
        </button>
        @if (request()->hasAny(['q', 'category', 'status']))
            <a href="{{ route('products.index') }}" class="btn btn-secondary" style="color: var(--text-muted);">
                <i class="fa-solid fa-xmark"></i> Xóa lọc
            </a>
        @endif
    </form>

    <!-- Products Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Tên sản phẩm</th>
                    <th>Mã vạch</th>
                    <th>Danh mục</th>
                    <th>Định mức (Min/Max)</th>
                    <th>Tồn Thùng</th>
                    <th>Tồn Cái (PCS)</th>
                    <th>Trạng thái</th>
                    @if (in_array(Auth::user()->role->name, ['admin', 'manager']))
                        <th style="text-align: right;">Hành động</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if ($products->isEmpty())
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                            Chưa có sản phẩm nào trong hệ thống.
                        </td>
                    </tr>
                @else
                    @foreach ($products as $p)
                        @php
                            $pieces = (int)$p->total_pieces;
                            $min = (int)$p->min_stock;
                            $max = (int)$p->max_stock;
                            
                            $statusText = "Bình thường";
                            $statusClass = "badge-success";
                            
                            if ($pieces < $min) {
                                $statusText = "Dưới định mức";
                                $statusClass = "badge-danger";
                            } elseif ($pieces > $max) {
                                $statusText = "Vượt định mức";
                                $statusClass = "badge-warning";
                            }
                        @endphp
                        <tr>
                            <td style="font-weight: 700; color: white;">{{ $p->sku }}</td>
                            <td>{{ $p->name }}</td>
                            <td style="font-family: monospace; font-size: 0.85rem;">{{ $p->barcode ?: '-' }}</td>
                            <td>{{ $p->category ?: '-' }}</td>
                            <td>{{ number_format($min) }} / {{ number_format($max) }}</td>
                            <td style="font-weight: 600;">{{ number_format($p->total_cartons) }}</td>
                            <td style="font-weight: 600; color: var(--accent);">{{ number_format($pieces) }}</td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </td>
                            @if (in_array(Auth::user()->role->name, ['admin', 'manager']))
                                <td style="text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                                        <button class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" 
                                                onclick="openEditModal({{ json_encode($p) }})">
                                            <i class="fa-solid fa-pen"></i> Sửa
                                        </button>
                                        <a href="{{ route('products.destroy', ['id' => $p->product_id]) }}" 
                                           class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: var(--danger);"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                                            <i class="fa-solid fa-trash"></i> Xóa
                                        </a>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm sản phẩm -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Thêm sản phẩm mới</h3>
            <button class="btn-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form action="{{ route('products.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="add_sku" class="form-label">Mã SKU *</label>
                    <input type="text" id="add_sku" name="sku" class="form-control" placeholder="Ví dụ: PROD001" required>
                </div>
                <div class="form-group">
                    <label for="add_barcode" class="form-label">Mã vạch (Barcode)</label>
                    <input type="text" id="add_barcode" name="barcode" class="form-control" placeholder="Nhập mã vạch">
                </div>
                <div class="form-group-full form-group">
                    <label for="add_name" class="form-label">Tên sản phẩm *</label>
                    <input type="text" id="add_name" name="name" class="form-control" placeholder="Tên đầy đủ của sản phẩm" required>
                </div>
                <div class="form-group-full form-group">
                    <label for="add_category" class="form-label">Danh mục</label>
                    <input type="text" id="add_category" name="category" class="form-control" placeholder="Ví dụ: Đồ uống, Gia vị...">
                </div>
                <div class="form-group">
                    <label for="add_unit" class="form-label">Đơn vị tính</label>
                    <input type="text" id="add_unit" name="unit" class="form-control" placeholder="Ví dụ: cái, hộp, thùng...">
                </div>
                <div class="form-group">
                    <label for="add_min_stock" class="form-label">Tồn tối thiểu</label>
                    <input type="number" id="add_min_stock" name="min_stock" class="form-control" value="100" min="0">
                </div>
                <div class="form-group">
                    <label for="add_max_stock" class="form-label">Tồn tối đa</label>
                    <input type="number" id="add_max_stock" name="max_stock" class="form-control" value="5000" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu lại</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Sửa sản phẩm -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Cập nhật sản phẩm</h3>
            <button class="btn-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form action="" method="POST" id="editForm">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="edit_sku" class="form-label">Mã SKU *</label>
                    <input type="text" id="edit_sku" name="sku" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_barcode" class="form-label">Mã vạch (Barcode)</label>
                    <input type="text" id="edit_barcode" name="barcode" class="form-control">
                </div>
                <div class="form-group-full form-group">
                    <label for="edit_name" class="form-label">Tên sản phẩm *</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>
                <div class="form-group-full form-group">
                    <label for="edit_category" class="form-label">Danh mục</label>
                    <input type="text" id="edit_category" name="category" class="form-control">
                </div>
                <div class="form-group">
                    <label for="edit_unit" class="form-label">Đơn vị tính</label>
                    <input type="text" id="edit_unit" name="unit" class="form-control" placeholder="cái, hộp, thùng...">
                </div>
                <div class="form-group">
                    <label for="edit_min_stock" class="form-label">Tồn tối thiểu</label>
                    <input type="number" id="edit_min_stock" name="min_stock" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label for="edit_max_stock" class="form-label">Tồn tối đa</label>
                    <input type="number" id="edit_max_stock" name="max_stock" class="form-control" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function openEditModal(prod) {
        // Cập nhật lại Action form sửa cho đúng ID
        let actionUrl = "{{ route('products.update', ['id' => ':id']) }}".replace(':id', prod.product_id);
        document.getElementById('editForm').action = actionUrl;
        
        document.getElementById('edit_sku').value = prod.sku;
        document.getElementById('edit_name').value = prod.name;
        document.getElementById('edit_barcode').value = prod.barcode || '';
        document.getElementById('edit_category').value = prod.category || '';
        document.getElementById('edit_unit').value = prod.unit || '';
        document.getElementById('edit_min_stock').value = prod.min_stock;
        document.getElementById('edit_max_stock').value = prod.max_stock;
        openModal('editModal');
    }
</script>
@endpush
