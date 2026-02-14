/**
 * Sales Chart Handler
 * Loads and displays sales data in chart format
 */

var salesChart = null;

$(document).ready(function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded!');
        return;
    }
    
    // Small delay to ensure DOM is fully ready
    setTimeout(function() {
        // Load initial chart with 1Y data
        loadSalesChart('1Y');
    }, 100);
    
    // Handle period button clicks
    $('.custom-btn-group a').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        $('.custom-btn-group a').removeClass('active');
        
        // Add active class to clicked button
        $(this).addClass('active');
        
        // Get period from button text
        var period = $(this).text().trim();
        
        // Load chart data
        loadSalesChart(period);
    });
});

function loadSalesChart(period) {
    $.ajax({
        url: 'backend/dashboard/get_sales_chart_data.php',
        type: 'GET',
        data: { period: period },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update total sales display
                updateSalesTotal(data.total_sales, data.total_orders);
                
                // Update or create chart
                renderSalesChart(data.labels, data.sales_data);
            } else {
                console.error('Failed to load sales chart:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading sales chart:', error);
        }
    });
}

function updateSalesTotal(totalSales, totalOrders) {
    // Format number with K/M suffix
    var formattedSales = formatLargeNumber(totalSales);
    
    // Update the display
    $('.border.p-2.br-8 h4').text(formattedSales);
}

function formatLargeNumber(num) {
    if (num >= 1000000) {
        return '$' + (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return '$' + (num / 1000).toFixed(1) + 'K';
    } else {
        return '$' + Math.round(num);
    }
}

function renderSalesChart(labels, salesData) {
    console.log('Rendering chart with labels:', labels, 'and data:', salesData);
    
    var chartElement = document.getElementById('sales-daychart');
    
    if (!chartElement) {
        console.error('Chart canvas element #sales-daychart not found in DOM');
        return;
    }
    
    console.log('Canvas element found:', chartElement);
    
    // Check if Chart is available
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library not loaded');
        return;
    }
    
    // Destroy existing chart if it exists
    if (salesChart !== null) {
        console.log('Destroying existing chart');
        salesChart.destroy();
    }
    
    try {
        // Create new chart
        var ctx = chartElement.getContext('2d');
        
        console.log('Creating new chart...');
        
        salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Sales',
                data: salesData,
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                borderColor: '#6366f1',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#000',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Sales: $' + context.parsed.y.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0',
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return '$' + (value / 1000).toFixed(0) + 'K';
                            }
                            return '$' + value;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            }
        }
    });
    
    console.log('Chart created successfully!');
    } catch (error) {
        console.error('Error creating chart:', error);
    }
}
