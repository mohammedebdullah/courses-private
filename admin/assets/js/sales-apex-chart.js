/**
 * Sales & Purchase ApexChart
 * Displays stacked bar chart with sales and purchase data
 */

'use strict';

$(document).ready(function() {
    let salesChart = null;
    
    // Load chart data for a specific period
    function loadSalesChart(period) {
        $.ajax({
            url: 'backend/dashboard/get_sales_chart_data.php',
            type: 'GET',
            data: { period: period },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    renderSalesChart(data.labels, data.sales_data, data.purchase_data || []);
                    updateChartTotals(data.total_sales);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sales chart data:', error);
            }
        });
    }
    
    // Update the total sales display
    function updateChartTotals(totalSales) {
        $('#chart-sales-total').text('$' + formatLargeNumber(totalSales));
    }
    
    // Format large numbers with K/M suffix
    function formatLargeNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return Math.round(num).toString();
    }
    
    // Render the ApexChart
    function renderSalesChart(labels, salesData, purchaseData) {
        // Destroy existing chart if it exists
        if (salesChart) {
            salesChart.destroy();
        }
        
        // If no purchase data, generate sample data based on sales
        if (!purchaseData || purchaseData.length === 0) {
            purchaseData = salesData.map(val => val * 0.7 + Math.random() * val * 0.3);
        }
        
        var chartOptions = {
            chart: {
                height: 245,
                type: 'bar',
                stacked: false,
                toolbar: {
                    show: false,
                }
            },
            colors: ['#6366f1'],
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: {
                        position: 'bottom',
                        offsetX: -10,
                        offsetY: 0
                    }
                }
            }],
            plotOptions: {
                bar: {
                    borderRadius: 8, 
                    borderRadiusWhenStacked: 'all',
                    horizontal: false,
                    endingShape: 'rounded'
                },
            },
            series: [{
                name: 'فرۆشتن',
                data: salesData
            }],
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        colors: '#6B7280', 
                        fontSize: '13px',
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        if (val >= 1000) {
                            return (val / 1000).toFixed(0) + 'K';
                        }
                        return Math.round(val);
                    },
                    offsetX: -15,
                    style: {
                        colors: '#6B7280', 
                        fontSize: '13px',
                    }
                }
            },
            grid: {
                borderColor: '#E5E7EB',
                strokeDashArray: 5,
                padding: {
                    left: -16,
                    top: 0,
                    bottom: 0,
                    right: 0, 
                },
            },
            legend: {
                show: false
            },
            dataLabels: {
                enabled: false
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toFixed(0);
                    }
                }
            }
        };
        
        salesChart = new ApexCharts(
            document.querySelector("#sales-daychart"),
            chartOptions
        );
        
        salesChart.render();
    }
    
    // Handle period button clicks
    $('.custom-btn-group .btn').on('click', function(e) {
        e.preventDefault();
        const period = $(this).data('period');
        
        // Update active state
        $('.custom-btn-group .btn').removeClass('active');
        $(this).addClass('active');
        
        // Load chart for selected period
        loadSalesChart(period);
    });
    
    // Initial load with 1Y period
    loadSalesChart('1Y');
});
