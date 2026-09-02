@extends('layouts.app', ['title' => 'Chỉnh sửa phiếu xuất kho'])

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
        <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.25rem;">Chỉnh sửa phiếu xuất kho</h3>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Mã phiếu: <strong>{{ $issue->issue_code }}</strong></p>
    </div>

    <!-- Edit warning banner -->
    <div style="background-color: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2); color: #cbd5e1; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-info" style="font-size: 1.2rem; color: var(--primary);"></i>
        <span><strong>Gợi ý:</strong> Khi chỉnh sửa, hệ thống sẽ tạm thời khôi phục số hàng cũ của phiếu này về kho, sau đó chạy lại thuật toán phân bổ xuất kho dựa trên cấu hình số lượng mới để đảm bảo tính chính xác của tồn kho.</span>
    </div>

    <form action="{{ route('outbound.update', ['id' => $issue->id]) }}" method="POST" id="issueForm">
        @csrf
        <!-- Header Info -->
        <div class="form-section-title">
            <i class="fa-solid fa-circle-info"></i> Thông tin phiếu xuất
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="order_number" class="form-label">Mã đơn hàng / Yêu cầu xuất (SO #)</label>
                <input type="text" id="order_number" name="order_number" class="form-control" value="{{ old('order_number', $issue->order_number) }}" placeholder="Ví dụ: SO-20260820" required>
            </div>

            <div class="form-group">
                <label for="issue_date" class="form-label">Ngày xuất kho *</label>
                <input type="datetime-local" id="issue_date" name="issue_date" class="form-control" required value="{{ old('issue_date', date('Y-m-d\TH:i', strtotime($issue->issue_date))) }}">
            </div>

            <div class="form-group">
                <label for="rule" class="form-label">Quy tắc gợi ý xuất kho *</label>
                <select id="rule" name="rule" class="form-control" onchange="triggerAllSuggestions()" required>
                    <option value="FIFO" {{ old('rule', $issue->rule ?? 'FIFO') == 'FIFO' ? 'selected' : '' }}>FIFO (Nhập trước - Xuất trước)</option>
                    <option value="LIFO" {{ old('rule', $issue->rule ?? 'FIFO') == 'LIFO' ? 'selected' : '' }}>LIFO (Nhập sau - Xuất trước)</option>
                </select>
            </div>

            <div class="form-group" style="grid-column: span 3; margin-top: 0.5rem;">
                <label for="note" class="form-label">Ghi chú</label>
                <input type="text" id="note" name="note" class="form-control" value="{{ old('note', $issue->note) }}" placeholder="Nội dung ghi chú xuất hàng...">
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
            <a href="{{ route('outbound.show', ['id' => $issue->id]) }}" class="btn btn-secondary">Hủy bỏ</a>
            <button type="submit" class="btn btn-primary" id="btnSubmit">
                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const products = @json($products);
    let itemRowIndex = 0;
    
    let rowValidity = {};

    @php
        $existingItems = [];
        foreach ($details as $detail) {
            $existingItems[] = [
                'product_id' => $detail->product_id,
                'qty' => $detail->requested_pieces
            ];
        }
    @endphp
    const existingItems = @json($existingItems);

    function getProductSelectHtml(rowIndex, selectedValue = '') {
        let options = '<option value="" disabled selected>-- Chọn sản phẩm cần xuất --</option>';
        products.forEach(p => {
            let selected = (selectedValue == p.id) ? 'selected' : '';
            options += `<option value="${p.id}" ${selected}>${p.sku} - ${p.name}</option>`;
        });
        return `<select name="items[${rowIndex}][product_id]" id="product_id_${rowIndex}" class="form-control" onchange="fetchSuggestion(${rowIndex})" required>${options}</select>`;
    }

    function addItemRowWithData(item) {
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
                    ${getProductSelectHtml(rowIndex, item.product_id)}
                </div>
                <div class="form-group">
                    <label class="form-label">Số lượng cái (PCS) *</label>
                    <input type="number" name="items[${rowIndex}][qty]" id="qty_${rowIndex}" class="form-control" placeholder="Ví dụ: 25" min="1" value="${item.qty}" oninput="fetchSuggestion(${rowIndex})" required>
                </div>
            </div>
            
            <div class="suggestion-box" id="suggestion_box_${rowIndex}">
                <div style="font-weight: 600; display: flex; align-items: center; gap: 0.5rem;" id="suggestion_title_${rowIndex}"></div>
                <ul class="allocation-list" id="allocation_list_${rowIndex}"></ul>
            </div>
        `;
        
        container.appendChild(row);
        rowValidity[rowIndex] = false;
        fetchSuggestion(rowIndex);
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
        // CHÚ Ý: Vì ta đang sửa phiếu, ta gửi thêm tham số `exclude_issue_id` để thuật toán suggest của server biết 
        // cần tính cả số lượng đang phân bổ của phiếu này như là hàng có sẵn trong kho.
        // Tuy nhiên, ở backend OutboundService.php, ta đã khôi phục tồn kho trước khi chạy gợi ý,
        // nên ta chỉ cần gọi suggest như bình thường và nó sẽ hoạt động chính xác!
        const url = `{{ route('outbound.suggest') }}?product_id=${productId}&qty=${qty}&rule=${rule}`;
        
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
                    // Ta cũng hiển thị cảnh báo cho phép biết cần thêm bao nhiêu
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
        if (existingItems.length > 0) {
            existingItems.forEach(item => {
                addItemRowWithData(item);
            });
        } else {
            addItemRow();
        }
    });
</script>
@endpush
