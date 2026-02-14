/**
 * Dashboard Date Range Filter
 * Filters all dashboard data based on selected date range
 */

// Global date range variables
let dashboardStartDate = moment().startOf('year').format('YYYY-MM-DD');
let dashboardEndDate = moment().endOf('year').format('YYYY-MM-DD');

$(document).ready(function() {
    // Initialize date range picker
    initializeDateRangePicker();
    
    // Load initial data on page load
    setTimeout(function() {
        reloadAllDashboardData();
    }, 500);
});

function initializeDateRangePicker() {
    $('#dashboard-date-filter').daterangepicker({
        startDate: moment().startOf('year'),
        endDate: moment().endOf('year'),
        locale: {
            format: 'DD/MM/YYYY',
            separator: ' - ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            firstDay: 1
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
        }
    });

    // Handle date range change
    $('#dashboard-date-filter').on('apply.daterangepicker', function(ev, picker) {
        dashboardStartDate = picker.startDate.format('YYYY-MM-DD');
        dashboardEndDate = picker.endDate.format('YYYY-MM-DD');
        
        // Show loading indicator
        showLoadingIndicator();
        
        // Reload all dashboard data
        reloadAllDashboardData();
    });
}

function showLoadingIndicator() {
    // Add loading class to cards
    $('.sale-widget, .revenue-widget, .info-item').addClass('loading');
    
    // Show a subtle loading overlay
    if (!$('#dashboard-loading-overlay').length) {
        $('body').append('<div id="dashboard-loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
    }
}

function hideLoadingIndicator() {
    $('.sale-widget, .revenue-widget, .info-item').removeClass('loading');
    $('#dashboard-loading-overlay').fadeOut(300, function() {
        $(this).remove();
    });
}

function reloadAllDashboardData() {
    // Create a counter to track all AJAX calls
    let ajaxCalls = 0;
    let completedCalls = 0;
    
    function checkAllComplete() {
        completedCalls++;
        if (completedCalls >= ajaxCalls) {
            hideLoadingIndicator();
        }
    }
    
    // 1. Reload financial summary cards
    ajaxCalls++;
    reloadFinancialCards().then(checkAllComplete);
    
    // 2. Reload revenue widgets (customer due, supplier due, etc.)
    ajaxCalls++;
    reloadRevenueWidgets().then(checkAllComplete);
    
    // 3. Reload overall information section
    ajaxCalls++;
    reloadOverallInfo().then(checkAllComplete);
    
    // 4. Reload sales chart
    ajaxCalls++;
    reloadSalesChart().then(checkAllComplete);
    
    // 5. Reload top selling products
    ajaxCalls++;
    reloadTopSellingProducts().then(checkAllComplete);
    
    // 6. Reload low stock products (not date filtered, but reload for consistency)
    ajaxCalls++;
    reloadLowStockProducts().then(checkAllComplete);
    
    // 7. Reload sales statistics chart
    ajaxCalls++;
    reloadSalesStatistics().then(checkAllComplete);
    
    // 8. Reload top customers
    ajaxCalls++;
    reloadTopCustomers().then(checkAllComplete);
}

function reloadFinancialCards() {
    console.log('Reloading financial cards with dates:', dashboardStartDate, dashboardEndDate);
    
    return $.ajax({
        url: 'backend/dashboard/get_financial_summary.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate
        },
        dataType: 'json',
        success: function(response) {
            console.log('Financial cards response:', response);
            
            if (response.success) {
                const data = response.data;
                
                // Update all 8 financial cards
                $('.sale-widget:eq(0) h3').text('$' + formatNumber(data.total_sales));
                $('.sale-widget:eq(1) h3').text('$' + formatNumber(data.total_special));
                $('.sale-widget:eq(2) h3').text('$' + formatNumber(data.total_purchase));
                $('.sale-widget:eq(3) h3').text('$' + formatNumber(data.total_income));
                $('.sale-widget:eq(4) h3').text('$' + formatNumber(data.total_expense));
                $('.sale-widget:eq(5) h3').text('$' + formatNumber(data.total_sales_profit));
                $('.sale-widget:eq(6) h3').text('$' + formatNumber(data.total_special_profit));
                $('.sale-widget:eq(7) h3').text('$' + formatNumber(data.total_net_revenue));
                
                console.log('Updated financial cards successfully');
            } else {
                console.error('Financial cards error:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error in reloadFinancialCards:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
        }
    });
}

function reloadRevenueWidgets() {
    console.log('Reloading revenue widgets with dates:', dashboardStartDate, dashboardEndDate);
    
    return $.ajax({
        url: 'backend/dashboard/get_dashboard_stats.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate
        },
        dataType: 'json',
        success: function(response) {
            console.log('Revenue widgets response:', response);
            
            if (response.success) {
                const data = response.data;
                
                // Update revenue widgets
                $('.revenue-widget:eq(0) h4').text('$' + formatNumber(data.customer_due));
                $('.revenue-widget:eq(1) h4').text('$' + formatNumber(data.supplier_due));
                $('.revenue-widget:eq(2) h4').text(formatNumber(data.total_customers));
                $('.revenue-widget:eq(3) h4').text(formatNumber(data.today_sales));
                $('.revenue-widget:eq(3) p.mb-0 .text-primary').text(data.today_special);
                
                console.log('Updated revenue widgets:', {
                    customer_due: data.customer_due,
                    supplier_due: data.supplier_due,
                    total_customers: data.total_customers,
                    today_sales: data.today_sales,
                    today_special: data.today_special
                });
            } else {
                console.error('Revenue widgets error:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error in reloadRevenueWidgets:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
        }
    });
}

function reloadOverallInfo() {
    return $.ajax({
        url: 'backend/dashboard/get_dashboard_stats.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update info items
                $('.info-item:eq(0) h5').text(formatNumber(data.total_sales_count));
                $('.info-item:eq(1) h5').text(formatNumber(data.total_customers));
                $('.info-item:eq(2) h5').text(formatNumber(data.total_expense_count));
                $('.info-item:eq(3) h5').text(formatNumber(data.total_products));
                $('.info-item:eq(4) h5').text(formatNumber(data.total_categories));
                $('.info-item:eq(5) h5').text(formatNumber(data.total_admins));
            }
        }
    });
}

function reloadSalesChart() {
    return $.ajax({
        url: 'backend/dashboard/get_sales_chart_data.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate,
            period: '1Y'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof updateSalesChart === 'function') {
                updateSalesChart(response.data);
            }
        }
    });
}

function reloadTopSellingProducts() {
    return $.ajax({
        url: 'backend/dashboard/get_top_selling_products.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate,
            period: 'custom'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof updateTopSellingProducts === 'function') {
                updateTopSellingProducts(response.data);
            }
        }
    });
}

function reloadLowStockProducts() {
    return $.ajax({
        url: 'backend/dashboard/get_low_stock_products.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof updateLowStockProducts === 'function') {
                updateLowStockProducts(response.data);
            }
        }
    });
}

function reloadSalesStatistics() {
    return $.ajax({
        url: 'backend/dashboard/get_sales_statistics.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate,
            year: moment(dashboardEndDate).year()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof updateSalesStatistics === 'function') {
                updateSalesStatistics(response.data);
            }
        }
    });
}

function reloadTopCustomers() {
    return $.ajax({
        url: 'backend/dashboard/get_top_customers.php',
        type: 'GET',
        data: {
            start_date: dashboardStartDate,
            end_date: dashboardEndDate
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && typeof updateTopCustomers === 'function') {
                updateTopCustomers(response.data);
            }
        }
    });
}

function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
