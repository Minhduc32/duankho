@extends('layouts.app', ['title' => 'Lập phiếu xuất kho'])

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

    /* Outbound rows styling */
    .issue-item-row {
        background: rgba(30, 41, 59, 0.3);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        position: relative;
        animation: slideIn 0.25s ease;
    }

    .btn-remove-item {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        font-size: 1.1rem;
        transition: var(--transition);
    }

    .btn-remove-item:hover {
        transform: scale(1.1);
    }

    /* Suggestion Box */
    .suggestion-box {
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid var(--border-color);
        display: none;
    }

    .suggestion-box.active {
        display: block;
    }

    .suggestion-box.error {
        border-color: rgba(239, 68, 68, 0.3);
        background: rgba(239, 68, 68, 0.05);
        color: #f87171;
    }

    .suggestion-box.success {
        border-color: rgba(16, 185, 129, 0.3);
        background: rgba(16, 185, 129, 0.05);
    }

    .allocation-list {
        margin-top: 0.5rem;
        list-style: none;
        padding-left: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .allocation-item {
        display: flex;
        justify-content: space-between;
        color: #cbd5e1;
        font-family: monospace;
        font-size: 0.8rem;
    }

    .allocation-item i {
        color: var(--accent);
        margin-right: 0.25rem;
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
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Lập phiếu xuất kho mới</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Hệ thống tự động phân bổ lô hàng và chỉ định vị trí thùng hàng xuất theo quy tắc FIFO hoặc LIFO.</p>
    </div>

    <form action="{{ route('outbound.store') }}" method="POST" id="issueForm">
        @csrf
        <!-- Header Info -->
        <div class="form-section-title">
            <i class="fa-solid fa-circle-info"></i> Thông tin phiếu xuất
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="order_number" class="form-label">Mã đơn hàng / Yêu cầu xuất (SO #)</label>
                <input type="text" id="order_number" name="order_number" class="form-control" value="{{ old('order_number') }}" placeholder="Ví dụ: SO-20260820" required>
            </div>

            <div class="form-group">
                <label for="issue_date" class="form-label">Ngày xuất kho *</label>
                <input type="datetime-local" id="issue_date" name="issue_date" class="form-control" required value="{{ old('issue_date', date('Y-m-d\TH:i')) }}">
            </div>

            <div class="form-group">
                <label for="rule" class="form-label">Quy tắc gợi ý xuất kho *</label>
                <select id="rule" name="rule" class="form-control" onchange="triggerAllSuggestions()" required>
                    <option value="FIFO">FIFO (Nhập trước - Xuất trước)</option>
                    <option value="LIFO">LIFO (Nhập sau - Xuất trước)</option>
                </select>
            </div>

            <div class="form-group" style="grid-column: span 3; margin-top: 0.5rem;">
                <label for="note" class="form-label">Ghi chú</label>
                <input type="text" id="note" name="note" class="form-control" value="{{ old('note') }}" placeholder="Nội dung ghi chú xuất hàng...">
            </div>
        </div>

        <!-- Products to issue -->
        <div class="form-section-title" style="margin-top: 2rem;">
            <i class="fa-solid fa-truck-ramp-box"></i> Chi tiết mặt hàng yêu cầu xuất
        </div>

        <div id="issue-items-container">
            <!-- Dynamic rows appended here -->
        </div>

        <div>
            <button type="button" class="btn btn-secondary" style="width: 100%; border-style: dashed; padding: 0.8rem;" onclick="addItemRow()">
                <i class="fa-solid fa-plus"></i> Thêm sản phẩm yêu cầu xuất
            </button>
        </div>

        <!-- Submit & Cancel Actions -->
        <div class="footer-actions">
            <a href="{{ route('outbound.index') }}" class="btn btn-secondary">Quay lại</a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="fa-solid fa-truck-moving"></i> Xác nhận xuất kho
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const products = @json($products);
    const productInfoUrl = "{{ route('outbound.product-info') }}";
    const suggestUrl = "{{ route('outbound.suggest') }}";
    let itemRowIndex = 0;
    
    let rowValidity = {};

    function getProductSelectHtml(rowIndex) {
        let options = '<option value="" disabled selected>-- Chọn sản phẩm cần xuất --</option>';
        products.forEach(p => {
            options += `<option value="${p.id}">${p.sku} - ${p.name}</option>`;
        });
        return `<select name="items[${rowIndex}][product_id]" id="product_id_${rowIndex}" class="form-control" onchange="onProductChange(${rowIndex})" required>${options}</select>`;
    }

    function onProductChange(rowIndex) {
        fetchProductInfo(rowIndex);
        fetchSuggestion(rowIndex);
    }

    function fetchProductInfo(rowIndex) {
        const productId = document.getElementById(`product_id_${rowIndex}`).value;
        const panel = document.getElementById(`product_info_${rowIndex}`);

        if (!productId) {
            panel.style.display = 'none';
            return;
        }

        fetch(`${productInfoUrl}?product_id=${productId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) { panel.style.display = 'none'; return; }

                const { product, stock, cartons } = data;

                let cartonsHtml = '';
                if (cartons.length === 0) {
                    cartonsHtml = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1rem;">Không có thùng hàng nào trong kho.</td></tr>`;
                } else {
                    cartons.forEach((c, i) => {
                        cartonsHtml += `
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 0.4rem 0.6rem; font-family: monospace; color: white;">${c.carton_code}</td>
                                <td style="padding: 0.4rem 0.6rem; font-family: monospace;">${c.lot_number || '-'}</td>
                                <td style="padding: 0.4rem 0.6rem; text-align: right; color: var(--accent); font-weight: 600;">${c.current_pieces}</td>
                                <td style="padding: 0.4rem 0.6rem;">Dãy ${c.zone} - ${c.rack} - ${c.level}</td>
                                <td style="padding: 0.4rem 0.6rem; font-family: monospace; font-size: 0.8rem;">${c.receipt_code}</td>
                                <td style="padding: 0.4rem 0.6rem; font-size: 0.8rem; color: var(--text-muted);">${c.po_number || '-'}</td>
                                <td style="padding: 0.4rem 0.6rem; color: var(--primary); font-size: 0.8rem;"><i class="fa-solid fa-user"></i> ${c.creator_name}</td>
                            </tr>
                        `;
                    });
                }

                panel.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <span style="font-weight: 700; color: var(--accent);"><i class="fa-solid fa-boxes-stacked"></i> Thông tin tồn kho: <span style="color: white;">${product.name}</span></span>
                        <div style="display: flex; gap: 1.5rem; font-size: 0.85rem;">
                            <span>Tổng thùng: <strong style="color: white;">${stock.total_cartons}</strong></span>
                            <span>Tổng số cái: <strong style="color: var(--success);">${stock.total_pieces} ${product.unit || 'cái'}</strong></span>
                        </div>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); text-align: left;">
                                    <th style="padding: 0.4rem 0.6rem;">Mã Thùng</th>
                                    <th style="padding: 0.4rem 0.6rem;">Số Lô</th>
                                    <th style="padding: 0.4rem 0.6rem; text-align: right;">Còn lại</th>
                                    <th style="padding: 0.4rem 0.6rem;">Vị trí</th>
                                    <th style="padding: 0.4rem 0.6rem;">Phiếu nhập</th>
                                    <th style="padding: 0.4rem 0.6rem;">Số PO</th>
                                    <th style="padding: 0.4rem 0.6rem;">Nhân viên nhập</th>
                                </tr>
                            </thead>
                            <tbody>${cartonsHtml}</tbody>
                        </table>
                    </div>
                `;
                panel.style.display = 'block';
            })
            .catch(() => { panel.style.display = 'none'; });
    }

    function addItemRow() {
        const container = document.getElementById('issue-items-container');
        const rowIndex = itemRowIndex++;
        
        const row = document.createElement('div');
        row.className = 'issue-item-row';
        row.id = `item-row-${rowIndex}`;
        row.innerHTML = `
            <button type="button" class="btn-remove-item" onclick="removeItemRow(${rowIndex})" title="Xóa dòng này">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="form-row" style="margin-bottom: 0;">
                <div class="form-group" style="grid-column: span 3;">
                    <label class="form-label">Chọn sản phẩm *</label>
                    ${getProductSelectHtml(rowIndex)}
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng cái (PCS) *</label>
                    <input type="number" name="items[${rowIndex}][qty]" id="qty_${rowIndex}" class="form-control" placeholder="Ví dụ: 25" min="1" oninput="fetchSuggestion(${rowIndex})" required>
                </div>
            </div>

            <!-- Product Info Panel: hiện khi chọn sản phẩm -->
            <div id="product_info_${rowIndex}" style="display: none; margin-top: 0.75rem; background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 10px; padding: 0.9rem 1rem; font-size: 0.85rem;">
                <!-- Filled by fetchProductInfo() -->
            </div>
            
            <div class="suggestion-box" id="suggestion_box_${rowIndex}">
                <div style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;" id="suggestion_title_${rowIndex}"></div>
                <ul class="allocation-list" id="allocation_list_${rowIndex}"></ul>
            </div>
        `;
        
        container.appendChild(row);
        rowValidity[rowIndex] = false;
        checkFormValidity();
    }

    function removeItemRow(index) {
        const row = document.getElementById(`item-row-${index}`);
        if (row) {
            row.remove();
            delete rowValidity[index];
            checkFormValidity();
        }
    }

    function fetchSuggestion(rowIndex) {
        const productId = document.getElementById(`product_id_${rowIndex}`).value;
        const qty = document.getElementById(`qty_${rowIndex}`).value;
        const rule = document.getElementById('rule').value;
        
        const box = document.getElementById(`suggestion_box_${rowIndex}`);
        const title = document.getElementById(`suggestion_title_${rowIndex}`);
        const list = document.getElementById(`allocation_list_${rowIndex}`);

        if (!productId || !qty || qty <= 0) {
            box.style.display = 'none';
            rowValidity[rowIndex] = false;
            checkFormValidity();
            return;
        }

        // Gọi AJAX suggest route của Laravel
        const url = `${suggestUrl}?product_id=${productId}&qty=${qty}&rule=${rule}`;
        
        fetch(url)
            .then(res => res.json())
            .then(data => {
                box.style.display = 'block';
                list.innerHTML = '';
                
                if (data.error) {
                    box.className = 'suggestion-box error';
                    title.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> Lỗi: ${data.error}`;
                    rowValidity[rowIndex] = false;
                } else if (data.remaining_needed > 0) {
                    box.className = 'suggestion-box error';
                    title.innerHTML = `<i class="fa-solid fa-circle-xmark"></i> Không đủ tồn kho khả dụng! Thiếu ${data.remaining_needed} cái.`;
                    rowValidity[rowIndex] = false;
                } else {
                    box.className = 'suggestion-box success';
                    title.innerHTML = `<i class="fa-solid fa-circle-check" style="color: var(--success)"></i> Gợi ý phân bổ thành công (Lấy hàng từ các vị trí sau):`;
                    
                    data.allocations.forEach(alloc => {
                        const li = document.createElement('li');
                        li.className = 'allocation-item';
                        li.innerHTML = `
                            <span><i class="fa-solid fa-location-arrow"></i> <strong>${alloc.carton_code}</strong> (Dãy ${alloc.zone} - ${alloc.rack} - Tầng ${alloc.level})</span>
                            <span style="color: var(--accent); font-weight: 600;">Lấy ${alloc.pieces_issued} cái (Lô: ${alloc.lot_number})</span>
                        `;
                        list.appendChild(li);
                    });
                    rowValidity[rowIndex] = true;
                }
                checkFormValidity();
            })
            .catch(err => {
                box.style.display = 'block';
                box.className = 'suggestion-box error';
                title.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> Lỗi kết nối máy chủ gợi ý.`;
                rowValidity[rowIndex] = false;
                checkFormValidity();
            });
    }

    function triggerAllSuggestions() {
        const rows = document.querySelectorAll('.issue-item-row');
        rows.forEach(row => {
            const index = row.id.replace('item-row-', '');
            fetchSuggestion(index);
        });
    }

    function checkFormValidity() {
        const btn = document.getElementById('btnSubmit');
        const rows = document.querySelectorAll('.issue-item-row');
        
        if (rows.length === 0) {
            btn.disabled = true;
            btn.style.opacity = 0.5;
            btn.style.cursor = 'not-allowed';
            return;
        }

        let isAllValid = true;
        for (let key in rowValidity) {
            if (rowValidity[key] === false) {
                isAllValid = false;
                break;
            }
        }

        if (isAllValid) {
            btn.disabled = false;
            btn.style.opacity = 1;
            btn.style.cursor = 'pointer';
        } else {
            btn.disabled = true;
            btn.style.opacity = 0.5;
            btn.style.cursor = 'not-allowed';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        addItemRow();
    });
</script>
@endpush
