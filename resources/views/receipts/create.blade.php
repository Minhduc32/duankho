@extends('layouts.app', ['title' => 'Lập phiếu nhập kho'])

@push('styles')
<style>
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.25rem;
        color: var(--accent);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 0.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    /* Product row card styling */
    .product-row-card {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .btn-remove-row {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        font-size: 1.2rem;
        transition: var(--transition);
    }

    .btn-remove-row:hover {
        transform: scale(1.1);
    }

    /* Carton grid styling */
    .carton-grid-header {
        display: grid;
        grid-template-columns: 80px 1fr 2fr 50px;
        gap: 1rem;
        margin-top: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px dashed var(--border-color);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
    }

    .carton-row {
        display: grid;
        grid-template-columns: 80px 1fr 2fr 50px;
        gap: 1rem;
        align-items: center;
        margin-top: 0.75rem;
        animation: fadeIn 0.25s ease;
    }

    .carton-index-badge {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 6px;
        text-align: center;
        padding: 0.45rem;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-main);
    }

    .btn-add-carton {
        margin-top: 1rem;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        background: rgba(6, 182, 212, 0.1);
        color: var(--accent);
        border: 1px solid rgba(6, 182, 212, 0.2);
    }

    .btn-add-carton:hover {
        background: rgba(6, 182, 212, 0.15);
        color: white;
    }

    .btn-remove-carton {
        background: none;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 1.1rem;
        transition: var(--transition);
        text-align: center;
    }

    .btn-remove-carton:hover {
        color: var(--danger);
    }

    .footer-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 2rem;
        border-top: 1px solid var(--border-color);
        padding-top: 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Lập phiếu nhập kho mới</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Nhập thông tin phiếu nhập, số lô và số lượng cái trong từng thùng tương ứng.</p>
    </div>

    <!-- Quick Add Section -->
    <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; color: var(--accent); font-weight: 600;">
            <i class="fa-solid fa-bolt"></i> Nhập nhanh cho sản phẩm cũ (đã có lô / kệ lưu trữ)
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.25rem;">
            Chọn sản phẩm để xem danh sách lô hàng & vị trí hiện tại trong kho. Nhập số lượng và nhấn "Thêm nhanh" để tự động điền thông tin hoặc cộng dồn vào mặt hàng đã có sẵn trong danh sách.
        </p>

        <div class="form-row" style="margin-bottom: 1rem; grid-template-columns: 2fr 1fr;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">Chọn sản phẩm</label>
                <select id="quick_product_id" class="form-control" onchange="loadQuickProductInfo()">
                    <option value="" disabled selected>-- Chọn sản phẩm để xem lô/vị trí cũ --</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Quick Add Table (Hidden by default, shown when product selected) -->
        <div id="quick_add_panel" style="display: none; margin-top: 1rem;">
            <div style="overflow-x: auto; background: rgba(15, 23, 42, 0.4); border-radius: 12px; border: 1px solid var(--border-color); padding: 0.5rem;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                            <th style="padding: 0.75rem 1rem;">Số Lô</th>
                            <th style="padding: 0.75rem 1rem;">Vị trí cũ</th>
                            <th style="padding: 0.75rem 1rem; text-align: right;">Đơn giá nhập cũ (VNĐ)</th>
                            <th style="padding: 0.75rem 1rem; text-align: right; width: 150px;">Số cái nhập thêm *</th>
                            <th style="padding: 0.75rem 1rem; text-align: center; width: 120px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="quick_add_tbody">
                        <!-- Filled dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form action="{{ route('inbound.store') }}" method="POST" id="receiptForm">
        @csrf
        <!-- Header Info -->
        <div class="form-section-title">
            <i class="fa-solid fa-circle-info"></i> Thông tin phiếu nhập
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="po_number" class="form-label">Đơn đặt mua hàng (PO #)</label>
                <input type="text" id="po_number" name="po_number" class="form-control" value="{{ old('po_number') }}" placeholder="Ví dụ: PO-20260820">
            </div>

            <div class="form-group">
                <label for="receipt_date" class="form-label">Ngày nhập kho *</label>
                <input type="datetime-local" id="receipt_date" name="receipt_date" class="form-control" required value="{{ old('receipt_date', date('Y-m-d\TH:i')) }}">
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label for="note" class="form-label">Ghi chú</label>
                <input type="text" id="note" name="note" class="form-control" value="{{ old('note') }}" placeholder="Nội dung ghi chú nhập hàng...">
            </div>
        </div>

        <!-- Product Entries list -->
        <div class="form-section-title" style="margin-top: 2rem;">
            <i class="fa-solid fa-boxes-packing"></i> Danh sách mặt hàng nhập kho
        </div>

        <div id="product-rows-container">
            <!-- Dynamic product cards will be appended here -->
        </div>

        <div>
            <button type="button" class="btn btn-secondary" style="width: 100%; border-style: dashed; padding: 0.8rem;" onclick="addProductRow()">
                <i class="fa-solid fa-plus"></i> Thêm mặt hàng / lô hàng mới
            </button>
        </div>

        <!-- Submit & Cancel Actions -->
        <div class="footer-actions">
            <a href="{{ route('inbound.index') }}" class="btn btn-secondary">Quay lại</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Lưu phiếu nhập
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const products = @json($products);
    const locations = @json($locations);
    const productInfoUrl = "{{ route('outbound.product-info') }}";

    let productRowIndex = 0;

    // Tìm kiếm ô nhập số lượng của thùng hàng đã có sẵn trong form
    function findExistingCartonInput(productId, lotNumber, locationId) {
        const cards = document.querySelectorAll('.product-row-card');
        for (let card of cards) {
            const rowIdx = card.getAttribute('data-index');
            const prodSelect = card.querySelector(`select[name="items[${rowIdx}][product_id]"]`);
            const lotInput = card.querySelector(`input[name="items[${rowIdx}][lot_number]"]`);
            
            if (prodSelect && lotInput && prodSelect.value == productId && lotInput.value == lotNumber) {
                const cartonRows = card.querySelectorAll('.carton-row');
                for (let cartonRow of cartonRows) {
                    const parts = cartonRow.id.split('-');
                    const cartonIdx = parts[parts.length - 1];
                    
                    const locSelect = cartonRow.querySelector(`select[name="items[${rowIdx}][cartons][${cartonIdx}][location_id]"]`);
                    const qtyInput = cartonRow.querySelector(`input[name="items[${rowIdx}][cartons][${cartonIdx}][pieces]"]`);
                    
                    if (locSelect && qtyInput && locSelect.value == locationId) {
                        return qtyInput;
                    }
                }
            }
        }
        return null;
    }

    // Load thông tin các lô hàng và vị trí cũ
    function loadQuickProductInfo() {
        const productId = document.getElementById('quick_product_id').value;
        const panel = document.getElementById('quick_add_panel');
        const tbody = document.getElementById('quick_add_tbody');

        if (!productId) {
            panel.style.display = 'none';
            return;
        }

        fetch(`${productInfoUrl}?product_id=${productId}`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.cartons.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">Sản phẩm này chưa có lô hàng hoặc vị trí lưu trữ cũ nào trong kho. Vui lòng thêm thủ công phía dưới.</td></tr>`;
                } else {
                    const uniqueOptions = [];
                    const seen = new Set();
                    data.cartons.forEach(c => {
                        const key = `${c.lot_number}_${c.zone}_${c.rack}_${c.level}`;
                        if (!seen.has(key)) {
                            seen.add(key);
                            uniqueOptions.push(c);
                        }
                    });

                    uniqueOptions.forEach((c, idx) => {
                        tbody.innerHTML += `
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.03);">
                                <td style="padding: 0.75rem 1rem; font-family: monospace; color: white; font-weight: 600;">${c.lot_number}</td>
                                <td style="padding: 0.75rem 1rem;">Dãy ${c.zone} - ${c.rack} - Tầng ${c.level}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right;">${Number(c.price).toLocaleString()}</td>
                                <td style="padding: 0.75rem 1rem; text-align: right;">
                                    <input type="number" id="quick_qty_${idx}" class="form-control" style="padding: 0.35rem 0.5rem; font-size: 0.85rem; text-align: right;" placeholder="Ví dụ: 100" min="1">
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: center;">
                                    <button type="button" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" 
                                            onclick="executeQuickAdd(${c.product_id}, '${c.lot_number}', ${c.price}, ${idx}, ${c.location_id})">
                                        <i class="fa-solid fa-plus"></i> Thêm nhanh
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                panel.style.display = 'block';
            });
    }

    function executeQuickAdd(productId, lotNumber, price, idx, locationId) {
        const qtyInput = document.getElementById(`quick_qty_${idx}`);
        const qty = parseInt(qtyInput.value);

        if (isNaN(qty) || qty <= 0) {
            alert('Vui lòng nhập số cái hợp lệ (lớn hơn 0).');
            qtyInput.focus();
            return;
        }

        // Kiểm tra xem dòng sản phẩm cùng lô, cùng vị trí đã tồn tại chưa
        const existingInput = findExistingCartonInput(productId, lotNumber, locationId);
        if (existingInput) {
            // Cộng dồn vào ô cũ
            const currentQty = parseInt(existingInput.value) || 0;
            existingInput.value = currentQty + qty;
            
            // Tạo hiệu ứng nhấp nháy làm nổi bật ô vừa cập nhật
            existingInput.style.transition = 'all 0.3s ease';
            existingInput.style.boxShadow = '0 0 15px var(--accent)';
            existingInput.style.borderColor = 'var(--accent)';
            existingInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            setTimeout(() => {
                existingInput.style.boxShadow = '';
                existingInput.style.borderColor = '';
            }, 1500);
        } else {
            // Tạo dòng sản phẩm với dữ liệu điền sẵn nếu chưa tồn tại
            addProductRowWithPreFilledData(productId, lotNumber, price, qty, locationId);
            
            // Cuộn màn hình tới dòng vừa thêm
            const container = document.getElementById('product-rows-container');
            container.lastElementChild.scrollIntoView({ behavior: 'smooth' });
        }

        // Reset ô input số lượng
        qtyInput.value = '';
    }

    // Hàm tạo dropdown chọn sản phẩm có giá trị chọn trước
    function getProductSelectHtmlWithSelected(rowIndex, selectedProductId) {
        let options = '<option value="" disabled>-- Chọn sản phẩm --</option>';
        products.forEach(p => {
            let selected = (p.id == selectedProductId) ? 'selected' : '';
            options += `<option value="${p.id}" ${selected}>${p.sku} - ${p.name}</option>`;
        });
        return `<select name="items[${rowIndex}][product_id]" class="form-control" required>${options}</select>`;
    }

    // Hàm tạo dropdown chọn sản phẩm mặc định
    function getProductSelectHtml(rowIndex) {
        let options = '<option value="" disabled selected>-- Chọn sản phẩm --</option>';
        products.forEach(p => {
            options += `<option value="${p.id}">${p.sku} - ${p.name}</option>`;
        });
        return `<select name="items[${rowIndex}][product_id]" class="form-control" required>${options}</select>`;
    }

    // Hàm tạo dropdown chọn vị trí có giá trị chọn trước
    function getLocationSelectHtmlWithSelected(productIndex, cartonIndex, selectedLocationId) {
        let options = '<option value="" disabled>-- Chọn vị trí --</option>';
        locations.forEach(l => {
            let selected = (l.id == selectedLocationId) ? 'selected' : '';
            options += `<option value="${l.id}" ${selected}>Dãy ${l.zone} - ${l.rack} - Tầng ${l.level} (${l.barcode})</option>`;
        });
        return `<select name="items[${productIndex}][cartons][${cartonIndex}][location_id]" class="form-control" required>${options}</select>`;
    }

    // Hàm tạo dropdown chọn vị trí mặc định
    function getLocationSelectHtml(productIndex, cartonIndex) {
        let options = '<option value="" disabled selected>-- Chọn vị trí --</option>';
        locations.forEach(l => {
            options += `<option value="${l.id}">Dãy ${l.zone} - ${l.rack} - Tầng ${l.level} (${l.barcode})</option>`;
        });
        return `<select name="items[${productIndex}][cartons][${cartonIndex}][location_id]" class="form-control" required>${options}</select>`;
    }

    // Thêm một dòng sản phẩm với thông tin được điền sẵn
    function addProductRowWithPreFilledData(productId, lotNumber, price, pieces, locationId) {
        const container = document.getElementById('product-rows-container');
        const rowIndex = productRowIndex++;
        
        const card = document.createElement('div');
        card.className = 'product-row-card';
        card.id = `product-row-${rowIndex}`;
        card.setAttribute('data-index', rowIndex);
        card.setAttribute('data-carton-count', 0);
        
        card.innerHTML = `
            <button type="button" class="btn-remove-row" onclick="removeProductRow(${rowIndex})" title="Xóa dòng mặt hàng">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Chọn sản phẩm *</label>
                    ${getProductSelectHtmlWithSelected(rowIndex, productId)}
                </div>
                <div class="form-group">
                    <label class="form-label">Số Lô (Lot Number) *</label>
                    <input type="text" name="items[${rowIndex}][lot_number]" class="form-control" placeholder="Ví dụ: LOT2608A" value="${lotNumber}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Đơn giá nhập</label>
                    <input type="number" name="items[${rowIndex}][price]" class="form-control" placeholder="0" min="0" value="${price}">
                </div>
            </div>
            
            <div style="margin-top: 1rem;">
                <label class="form-label" style="color: white; font-weight: 600;">Quy cách & Vị trí thùng hàng</label>
                <div class="carton-grid-header">
                    <span>Thùng #</span>
                    <span>Số Cái / Thùng *</span>
                    <span>Vị trí xếp hàng *</span>
                    <span style="text-align: center;">Xóa</span>
                </div>
                <div id="carton-container-${rowIndex}">
                    <!-- Cartons will go here -->
                </div>
                <button type="button" class="btn btn-add-carton" onclick="addCartonRow(${rowIndex})">
                    <i class="fa-solid fa-plus"></i> Thêm thùng hàng
                </button>
            </div>
        `;
        
        container.appendChild(card);
        addCartonRowWithPreFilledData(rowIndex, pieces, locationId);
    }

    // Thêm một dòng sản phẩm mới mặc định
    function addProductRow() {
        const container = document.getElementById('product-rows-container');
        const rowIndex = productRowIndex++;
        
        const card = document.createElement('div');
        card.className = 'product-row-card';
        card.id = `product-row-${rowIndex}`;
        card.setAttribute('data-index', rowIndex);
        card.setAttribute('data-carton-count', 0);
        
        card.innerHTML = `
            <button type="button" class="btn-remove-row" onclick="removeProductRow(${rowIndex})" title="Xóa dòng mặt hàng">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Chọn sản phẩm *</label>
                    ${getProductSelectHtml(rowIndex)}
                </div>
                <div class="form-group">
                    <label class="form-label">Số Lô (Lot Number) *</label>
                    <input type="text" name="items[${rowIndex}][lot_number]" class="form-control" placeholder="Ví dụ: LOT2608A" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Đơn giá nhập</label>
                    <input type="number" name="items[${rowIndex}][price]" class="form-control" placeholder="0" min="0">
                </div>
            </div>
            
            <div style="margin-top: 1rem;">
                <label class="form-label" style="color: white; font-weight: 600;">Quy cách & Vị trí thùng hàng</label>
                <div class="carton-grid-header">
                    <span>Thùng #</span>
                    <span>Số Cái / Thùng *</span>
                    <span>Vị trí xếp hàng *</span>
                    <span style="text-align: center;">Xóa</span>
                </div>
                <div id="carton-container-${rowIndex}">
                    <!-- Cartons will go here -->
                </div>
                <button type="button" class="btn btn-add-carton" onclick="addCartonRow(${rowIndex})">
                    <i class="fa-solid fa-plus"></i> Thêm thùng hàng
                </button>
            </div>
        `;
        
        container.appendChild(card);
        addCartonRow(rowIndex);
    }

    // Xóa một dòng sản phẩm
    function removeProductRow(index) {
        if (confirm('Bạn có chắc chắn muốn xóa dòng mặt hàng này?')) {
            const row = document.getElementById(`product-row-${index}`);
            if (row) row.remove();
        }
    }

    // Thêm một dòng thùng hàng cho sản phẩm có vị trí chọn trước
    function addCartonRowWithPreFilledData(productIndex, pieces, locationId) {
        const card = document.getElementById(`product-row-${productIndex}`);
        const cartonContainer = document.getElementById(`carton-container-${productIndex}`);
        
        let cartonCount = parseInt(card.getAttribute('data-carton-count'));
        const cartonIndex = cartonCount++;
        card.setAttribute('data-carton-count', cartonCount);

        const row = document.createElement('div');
        row.className = 'carton-row';
        row.id = `carton-row-${productIndex}-${cartonIndex}`;
        row.innerHTML = `
            <div class="carton-index-badge">${cartonCount}</div>
            <div>
                <input type="number" name="items[${productIndex}][cartons][${cartonIndex}][pieces]" class="form-control" placeholder="Số cái" min="1" value="${pieces}" required>
            </div>
            <div>
                ${getLocationSelectHtmlWithSelected(productIndex, cartonIndex, locationId)}
            </div>
            <div style="text-align: center;">
                <button type="button" class="btn-remove-carton" onclick="removeCartonRow(${productIndex}, ${cartonIndex})" title="Xóa thùng">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        
        cartonContainer.appendChild(row);
    }

    // Thêm một dòng thùng hàng mặc định
    function addCartonRow(productIndex) {
        const card = document.getElementById(`product-row-${productIndex}`);
        const cartonContainer = document.getElementById(`carton-container-${productIndex}`);
        
        let cartonCount = parseInt(card.getAttribute('data-carton-count'));
        const cartonIndex = cartonCount++;
        card.setAttribute('data-carton-count', cartonCount);

        const row = document.createElement('div');
        row.className = 'carton-row';
        row.id = `carton-row-${productIndex}-${cartonIndex}`;
        row.innerHTML = `
            <div class="carton-index-badge">${cartonCount}</div>
            <div>
                <input type="number" name="items[${productIndex}][cartons][${cartonIndex}][pieces]" class="form-control" placeholder="Số cái" min="1" required>
            </div>
            <div>
                ${getLocationSelectHtml(productIndex, cartonIndex)}
            </div>
            <div style="text-align: center;">
                <button type="button" class="btn-remove-carton" onclick="removeCartonRow(${productIndex}, ${cartonIndex})" title="Xóa thùng">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        
        cartonContainer.appendChild(row);
    }

    // Xóa một dòng thùng hàng
    function removeCartonRow(productIndex, cartonIndex) {
        const cartonRow = document.getElementById(`carton-row-${productIndex}-${cartonIndex}`);
        if (cartonRow) {
            cartonRow.remove();
            
            const cartonContainer = document.getElementById(`carton-container-${productIndex}`);
            const badges = cartonContainer.querySelectorAll('.carton-index-badge');
            badges.forEach((badge, idx) => {
                badge.textContent = idx + 1;
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        addProductRow();
    });
</script>
@endpush
