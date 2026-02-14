/**
 * Sales Statistics Loader
 * Loads sales value and revenue with chart
 */

$(document).ready(function() {
    loadSalesStatistics();
    
    // Handle year filter
    $('.sales-stats-year-filter').on('click', function(e) {
        e.preventDefault();
        
        const year = $(this).data('year');
        const yearText = $(this).text();
        
        // Update active state
        $('.sales-stats-year-filter').removeClass('active bg-primary text-white');
        $(this).addClass('active bg-primary text-white');
        
        // Update button text
        $('#salesStatsYearText').text(yearText);
        
        // Reload statistics
        loadSalesStatistics(year);
    });
});

/**
 * Load sales statistics
 */
function loadSalesStatistics(year = new Date().getFullYear()) {
    $.ajax({
        url: 'backend/dashboard/get_sales_statistics.php?year=' + year,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Sales Statistics Response:', response);
            
            if (response.success && response.data) {
                renderSalesStatistics(response.data);
                renderSalesStatisticsChart(response.data.monthly);
            } else {
                console.error('Failed to load sales statistics');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading sales statistics:', error);
        }
    });
}

/**
 * Render sales statistics cards
 */
function renderSalesStatistics(data) {
    // Format sales value
    const salesValue = '$' + data.total_sales.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    // Format revenue
    const revenueValue = '$' + data.total_revenue.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
    
    // Sales change badge
    const salesChangeClass = data.sales_change >= 0 ? 'badge-success' : 'badge-danger';
    const salesChangeIcon = data.sales_change >= 0 ? 'ti-arrow-up-left' : 'ti-arrow-down-right';
    const salesChangeText = Math.abs(data.sales_change).toFixed(1) + '%';
    
    // Revenue change badge
    const revenueChangeClass = data.revenue_change >= 0 ? 'badge-success' : 'badge-danger';
    const revenueChangeIcon = data.revenue_change >= 0 ? 'ti-arrow-up-left' : 'ti-arrow-down-right';
    const revenueChangeText = Math.abs(data.revenue_change).toFixed(1) + '%';
    
    // Update sales card
    $('#sales-value-amount').html(salesValue + '<span class="badge ' + salesChangeClass + ' badge-xs d-inline-flex align-items-center ms-2"><i class="ti ' + salesChangeIcon + ' me-1"></i>' + salesChangeText + '</span>');
    
    // Update revenue card
    $('#revenue-amount').html(revenueValue + '<span class="badge ' + revenueChangeClass + ' badge-xs d-inline-flex align-items-center ms-2"><i class="ti ' + revenueChangeIcon + ' me-1"></i>' + revenueChangeText + '</span>');
}

/**
 * Render sales statistics chart
 */
function renderSalesStatisticsChart(monthlyData) {
    const salesData = monthlyData.map(m => m.sales);
    const revenueData = monthlyData.map(m => m.revenue);
    
    const options = {
        series: [{
            name: 'فرۆشتن',
            data: salesData
        }, {
            name: 'داهات',
            data: revenueData
        }],
        chart: {
            type: 'area',
            height: 250,
            toolbar: {
                show: false
            }
        },
        colors: ['#6366f1', '#10b981'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.1
            }
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        },
        yaxis: {
            labels: {
                formatter: function(value) {
                    return '$' + (value / 1000).toFixed(0) + 'K';
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return '$' + value.toLocaleString();
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        }
    };
    
    // Destroy existing chart if any
    if (window.salesStatsChart) {
        window.salesStatsChart.destroy();
    }
    
    // Create new chart
    window.salesStatsChart = new ApexCharts(document.querySelector("#sales-statistics-chart"), options);
    window.salesStatsChart.render();
}
