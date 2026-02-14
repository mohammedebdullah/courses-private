/**
 * Dashboard Statistics Loader
 * Fetches and updates dashboard cards with real-time data
 */

$(document).ready(function() {
    // Load dashboard statistics
    loadDashboardStats();
    
    // Refresh every 5 minutes
    setInterval(loadDashboardStats, 300000);
});

function loadDashboardStats() {
    $.ajax({
        url: 'backend/dashboard/get_dashboard_stats.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Financial Summary Cards - Using PHP values from HTML, not updating via AJAX
                // The 8 financial summary cards are already populated by PHP in index.php
                // Keeping only the other dashboard widgets updated
                
                // Update Customer Due Card (revenue-widget 0)
                $('.revenue-widget:eq(0) h4').text('$' + formatNumber(data.customer_due));
                
                // Update Supplier Due Card (revenue-widget 1)
                $('.revenue-widget:eq(1) h4').text('$' + formatNumber(data.supplier_due));
                
                // Update Total Customers Card (revenue-widget 2)
                $('.revenue-widget:eq(2) h4').text(formatNumber(data.total_customers));
                
                // Update Today's Sales Card (revenue-widget 3) - Main count
                $('.revenue-widget:eq(3) h4').text(formatNumber(data.today_sales));
                
                // Update Today's Special Orders count in the subtitle
                $('.revenue-widget:eq(3) p.mb-0 .text-primary').text(data.today_special);
                
                // Update Total Orders in welcome message
                $('p.fw-medium .text-primary').text(formatNumber(data.total_orders) + '+');
                
                // Update Overall Information Section
                $('.info-item:eq(0) h5').text(formatNumber(data.total_sales_count)); // Total Sales
                $('.info-item:eq(1) h5').text(formatNumber(data.total_customers)); // Customer
                $('.info-item:eq(2) h5').text(formatNumber(data.total_expense_count)); // Total Expenses Count
                $('.info-item:eq(3) h5').text(formatNumber(data.total_products)); // Total Products
                $('.info-item:eq(4) h5').text(formatNumber(data.total_categories)); // Total Categories
                $('.info-item:eq(5) h5').text(formatNumber(data.total_admins)); // Total Admin
                
            } else {
                console.error('Failed to load dashboard stats:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard stats:', error);
        }
    });
}

function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
