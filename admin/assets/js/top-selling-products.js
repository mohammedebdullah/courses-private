/**
 * Top Selling Products Loader
 * Fetches and displays top selling products
 */

$(document).ready(function() {
    loadTopSellingProducts();
});

function loadTopSellingProducts() {
    const container = $('#top-selling-products-list');
    container.html('<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading products...</p></div>');
    
    $.ajax({
        url: 'backend/dashboard/get_top_selling_products.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Top Selling Response:', response);
            if (response.success && response.data.length > 0) {
                renderTopSellingProducts(response.data);
            } else {
                container.html('<div class="text-center p-4 text-muted">No sales data available</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading top selling products:', error);
            console.error('Response:', xhr.responseText);
            container.html('<div class="text-center p-4 text-danger">Error loading products</div>');
        }
    });
}

function renderTopSellingProducts(products) {
    const container = $('#top-selling-products-list');
    container.empty();
    
    products.forEach(function(product, index) {
        const isLast = index === products.length - 1;
        const borderClass = isLast ? '' : 'border-bottom';
        
        const productHtml = `
            <div class="d-flex align-items-center justify-content-between ${borderClass}">
                <div class="d-flex align-items-center">
                    <a href="javascript:void(0);" class="avatar avatar-lg">
                        <img src="${product.image}" alt="${product.name}" onerror="this.src='assets/img/products/product-01.jpg'">
                    </a>
                    <div class="ms-2">
                        <h6 class="fw-bold mb-1"><a href="javascript:void(0);">${product.name}</a></h6>
                        <div class="d-flex align-items-center item-list">			
                            <p>$${product.price.toFixed(0)}</p>
                            <p>${product.sales_count}+ فرۆشتن</p>
                        </div>
                    </div>
                </div>
                <span class="badge bg-outline-success badge-xs d-inline-flex align-items-center">
                    <i class="ti ti-arrow-up-left me-1"></i>${product.quantity_sold} یەکە
                </span>
            </div>
        `;
        
        container.append(productHtml);
    });
}
