/**
 * Top Selling Products Period Filter
 * Handles Today/Weekly/Monthly filtering
 */

$(document).ready(function() {
    // Handle period filter click
    $('.top-selling-period-filter').on('click', function(e) {
        e.preventDefault();
        
        const period = $(this).data('period');
        const periodText = $(this).text();
        
        // Update active state with primary background
        $('.top-selling-period-filter').removeClass('active bg-primary text-white');
        $(this).addClass('active bg-primary text-white');
        
        // Update button text
        $('#topSellingPeriodText').text(periodText);
        
        // Reload products with new period
        loadTopSellingProducts(period);
    });
});

/**
 * Load top selling products with period filter
 */
function loadTopSellingProducts(period = 'today') {
    const container = $('#top-selling-products-list');
    
    // Show loading spinner
    container.html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading products...</p>
        </div>
    `);
    
    // Fetch data with period parameter
    $.ajax({
        url: 'backend/dashboard/get_top_selling_products.php?period=' + period,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Top Selling Response (' + period + '):', response);
            
            if (response.success && response.data && response.data.length > 0) {
                renderTopSellingProducts(response.data);
            } else {
                container.html(`
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-package-off fs-48 mb-3"></i>
                        <p>No sales data available for this period</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading top selling products:', error);
            container.html(`
                <div class="text-center py-4 text-danger">
                    <i class="ti ti-alert-circle fs-48 mb-3"></i>
                    <p>Error loading products</p>
                </div>
            `);
        }
    });
}

/**
 * Render top selling products HTML
 */
function renderTopSellingProducts(products) {
    let html = '';
    
    products.forEach(function(product) {
        html += `
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0 me-2">
                            <img src="${product.image}" alt="${product.name}" class="rounded">
                        </div>
                        <div>
                            <h6 class="fw-medium mb-1">${product.name}</h6>
                            <p class="text-muted mb-0">$${product.price.toLocaleString()}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge badge-success">${product.sales_count} Sales</span>
                        <p class="text-muted mb-0 mt-1 fs-12">${product.quantity_sold} units sold</p>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#top-selling-products-list').html(html);
}
