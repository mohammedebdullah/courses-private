/**
 * Top Customers Loader
 * Loads top customers by purchase amount
 */

$(document).ready(function() {
    loadTopCustomers();
});

/**
 * Load top customers
 */
function loadTopCustomers() {
    const container = $('#top-customers-list');
    
    // Show loading spinner
    container.html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading customers...</p>
        </div>
    `);
    
    $.ajax({
        url: 'backend/dashboard/get_top_customers.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Top Customers Response:', response);
            
            if (response.success && response.data && response.data.length > 0) {
                renderTopCustomers(response.data);
            } else {
                container.html(`
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-users-off fs-48 mb-3"></i>
                        <p>No customer data available</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading top customers:', error);
            container.html(`
                <div class="text-center py-4 text-danger">
                    <i class="ti ti-alert-circle fs-48 mb-3"></i>
                    <p>Error loading customers</p>
                </div>
            `);
        }
    });
}

/**
 * Render top customers HTML
 */
function renderTopCustomers(customers) {
    let html = '';
    
    customers.forEach(function(customer, index) {
        const isLast = index === customers.length - 1;
        const borderClass = isLast ? '' : 'border-bottom mb-3 pb-3';
        
        // Avatar HTML - either image or initials
        let avatarHtml = '';
        if (customer.image) {
            avatarHtml = `<img src="${customer.image}" alt="${customer.name}">`;
        } else {
            // Generate random color for initials avatar
            const colors = ['text-primary', 'text-success', 'text-info', 'text-warning', 'text-danger', 'text-secondary'];
            const colorClass = colors[customer.id % colors.length];
            avatarHtml = `<span class="avatar-text ${colorClass}">${customer.initials}</span>`;
        }
        
        html += `
            <div class="d-flex align-items-center justify-content-between ${borderClass} flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <a href="javascript:void(0);" class="avatar avatar-lg flex-shrink-0">
                        ${avatarHtml}
                    </a>
                    <div class="ms-2">
                        <h6 class="fs-14 fw-bold mb-1"><a href="javascript:void(0);">${customer.name}</a></h6>
                        <div class="d-flex align-items-center item-list">			
                            <p class="d-inline-flex align-items-center"><i class="ti ti-map-pin me-1"></i>${customer.location}</p>
                            <p>${customer.total_orders} Orders</p>
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    <h5>$${customer.total_amount.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0})}</h5>
                </div>									
            </div>
        `;
    });
    
    $('#top-customers-list').html(html);
}
