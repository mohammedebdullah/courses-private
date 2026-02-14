/**
 * Quick Stock Update Handler
 * Handles stock quantity updates for door products
 */

$(document).ready(function() {
    let currentProductId = null;

    // Open stock update modal
    $(document).on('click', '.update-stock-btn', function() {
        currentProductId = $(this).data('id');
        const productName = $(this).data('name');
        const doorDesign = $(this).data('design');

        // Display product info
        $('#stock-product-info').text(`${productName} - ${doorDesign}`);

        // Load variants
        loadVariants(currentProductId);

        // Show modal
        $('#stock-update-modal').modal('show');
    });

    // Load product variants
    function loadVariants(productId) {
        $('#stock-variants-container').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">بارکرن...</span>
                </div>
                <p class="mt-2">بارکرنا جۆران...</p>
            </div>
        `);

        $.ajax({
            url: 'backend/product/get-variants-for-stock.php',
            method: 'GET',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayVariants(response.variants);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خەلەتی',
                        text: response.message || 'نەشیا جۆران بار بکەت'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'خەلەتی',
                    text: 'پەیوەندی ب سێرڤەری نەهاتە کرن'
                });
            }
        });
    }

    // Display variants in the modal
    function displayVariants(variants) {
        if (variants.length === 0) {
            $('#stock-variants-container').html(`
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>چ جۆر بۆ ڤی بەرهەمی نەهاتنە دیتن.
                </div>
            `);
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>رەنگ</th>
                            <th>قەبارە</th>
                            <th>هژمارا نوکە</th>
                            <th>زیادکرنا هژمارێ</th>
                            <th>سەرجەمێ نوی</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        variants.forEach(function(variant) {
            html += `
                <tr data-variant-id="${variant.id}">
                    <td><span class="badge bg-primary">${variant.color}</span></td>
                    <td>${variant.size}</td>
                    <td><strong>${variant.quantity}</strong></td>
                    <td>
                        <input type="number" 
                               class="form-control stock-add-input" 
                               data-variant-id="${variant.id}"
                               data-current-qty="${variant.quantity}"
                               min="0" 
                               value="0" 
                               style="width: 100px;">
                    </td>
                    <td class="new-total-qty" data-current="${variant.quantity}">
                        <strong class="text-success">${variant.quantity}</strong>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        $('#stock-variants-container').html(html);

        // Update new total on input change
        $(document).on('input', '.stock-add-input', function() {
            const $row = $(this).closest('tr');
            const currentQty = parseInt($(this).data('current-qty')) || 0;
            const addQty = parseInt($(this).val()) || 0;
            const newTotal = currentQty + addQty;

            $row.find('.new-total-qty strong').text(newTotal);
        });
    }

    // Save stock updates
    $('#saveStockUpdate').on('click', function() {
        const updates = [];
        let hasChanges = false;

        $('.stock-add-input').each(function() {
            const addQty = parseInt($(this).val()) || 0;
            if (addQty > 0) {
                hasChanges = true;
                updates.push({
                    variant_id: $(this).data('variant-id'),
                    add_quantity: addQty
                });
            }
        });

        if (!hasChanges) {
            Swal.fire({
                icon: 'warning',
                title: 'چ گهورین نەهاتنە کرن',
                text: 'هیڤیە هژمارەکێ بۆ کێمترین ئێک جۆر زیاد بکە.'
            });
            return;
        }

        // Show loading
        Swal.fire({
            title: 'نویکرنا کۆگەی...',
            text: 'هیڤیە چاڤەرێ بە',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit updates
        $.ajax({
            url: 'backend/product/stock-update-backend.php',
            method: 'POST',
            data: {
                product_id: currentProductId,
                updates: JSON.stringify(updates)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'سەرکەفتن!',
                        text: response.message || 'کۆگە ب سەرکەفتیانە هاتە نویکرن',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#stock-update-modal').modal('hide');
                        // Reload DataTable
                        if ($.fn.DataTable.isDataTable('#door-products-table')) {
                            $('#door-products-table').DataTable().ajax.reload();
                        } else {
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'نویکرن سەرنەکەفت',
                        text: response.message || 'نەشیا کۆگەی نوی بکەت'
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'خەلەتیەک ل سێرڤەری رویدا';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch(e) {}

                Swal.fire({
                    icon: 'error',
                    title: 'خەلەتی',
                    text: errorMsg
                });
            }
        });
    });

    // Reset modal on close
    $('#stock-update-modal').on('hidden.bs.modal', function() {
        currentProductId = null;
        $('#stock-variants-container').html('');
        $('#stock-product-info').text('');
    });
});
