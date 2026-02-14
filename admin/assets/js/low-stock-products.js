/**
 * Low Stock Products Loader
 * Fetches and displays low stock products
 */

$(document).ready(function() {
    loadLowStockProducts();
});

function loadLowStockProducts() {
    const container = $('#low-stock-products-list');
    container.html('<div class="text-center p-4"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading products...</p></div>');
    
    $.ajax({
        url: 'backend/dashboard/get_low_stock_products.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Low Stock Response:', response);
            if (response.success && response.data.length > 0) {
                renderLowStockProducts(response.data);
            } else {
                container.html('<div class="text-center p-4 text-muted">No low stock products</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading low stock products:', error);
            console.error('Response:', xhr.responseText);
            container.html('<div class="text-center p-4 text-danger">Error loading products</div>');
        }
    });
}

function renderLowStockProducts(products) {
    const container = $('#low-stock-products-list');
    container.empty();
    
    products.forEach(function(product, index) {
        const isLast = index === products.length - 1;
        const marginClass = isLast ? 'mb-0' : 'mb-4';
        
        const productHtml = `
            <div class="d-flex align-items-center justify-content-between ${marginClass}">
                <div class="d-flex align-items-center">
                    <a href="javascript:void(0);" class="avatar avatar-lg">
                        <img src="${product.image}" alt="${product.name}" onerror="this.src='assets/img/products/product-06.jpg'">
                    </a>
                    <div class="ms-2">
                        <h6 class="fw-bold mb-1"><a href="javascript:void(0);">${product.name}</a></h6>
                        <p class="fs-13">SKU : ${product.sku || 'N/A'}</p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="fs-13 mb-1">د کۆگەی دا</p>
                    <h6 class="text-orange fw-medium">${product.stock.toString().padStart(2, '0')}</h6>
                </div>
            </div>
        `;
        
        container.append(productHtml);
    });
}
