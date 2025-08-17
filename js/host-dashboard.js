// Host dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeHostDashboard();
    loadDashboardData();
    initializeChart();
    animateStats();
});

function initializeHostDashboard() {
    // Initialize role toggle
    const roleToggle = document.getElementById('roleToggle');
    if (roleToggle) {
        roleToggle.addEventListener('click', function() {
            window.location.href = 'customer-dashboard.html';
        });
    }
    
    // Initialize chart period selector
    const chartPeriod = document.getElementById('chartPeriod');
    if (chartPeriod) {
        chartPeriod.addEventListener('change', updateChart);
    }
}

function loadDashboardData() {
    // Load user data from localStorage
    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
    
    // Update host name
    const hostName = document.getElementById('hostName');
    if (hostName && userData.name) {
        hostName.textContent = userData.name;
    }
    
    // Load mock dashboard data
    loadMockDashboardData();
}

function loadMockDashboardData() {
    // Mock KPI data
    const kpiData = {
        totalListings: 3,
        monthlyEarnings: 1245,
        totalBookings: 47,
        averageRating: 4.8
    };
    
    // Update KPI values
    document.getElementById('totalListings').textContent = kpiData.totalListings;
    document.getElementById('monthlyEarnings').textContent = `$${kpiData.monthlyEarnings.toLocaleString()}`;
    document.getElementById('totalBookings').textContent = kpiData.totalBookings;
    document.getElementById('averageRating').textContent = kpiData.averageRating;
}

function animateStats() {
    const statElements = document.querySelectorAll('.kpi-value');
    
    statElements.forEach(element => {
        const finalValue = element.textContent;
        const isMonetary = finalValue.includes('$');
        const isRating = finalValue.includes('.');
        
        let numericValue = parseFloat(finalValue.replace(/[^\d.]/g, ''));
        
        if (isNaN(numericValue)) return;
        
        // Animate counter
        animateCounter(element, 0, numericValue, 2000, (value) => {
            if (isMonetary) {
                return `$${Math.round(value).toLocaleString()}`;
            } else if (isRating) {
                return value.toFixed(1);
            } else {
                return Math.round(value).toString();
            }
        });
    });
}

function animateCounter(element, start, end, duration, formatter) {
    const startTime = Date.now();
    const endTime = startTime + duration;
    
    function updateCounter() {
        const now = Date.now();
        const remaining = Math.max((endTime - now) / duration, 0);
        const progress = 1 - remaining;
        
        // Ease out animation
        const easeProgress = 1 - Math.pow(1 - progress, 3);
        const value = start + (end - start) * easeProgress;
        
        element.textContent = formatter(value);
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    updateCounter();
}

function initializeChart() {
    const ctx = document.getElementById('performanceChart');
    if (!ctx) return;
    
    const chartData = {
        labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Earnings ($)',
            data: [850, 920, 1100, 1050, 1200, 1245],
            backgroundColor: 'rgba(63, 63, 63, 0.1)',
            borderColor: '#3F3F3F',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: 'Bookings',
            data: [28, 32, 38, 35, 42, 47],
            backgroundColor: 'rgba(102, 102, 102, 0.1)',
            borderColor: '#666666',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    };
    
    const config = {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#262626',
                    bodyColor: '#262626',
                    borderColor: '#E7E7E7',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Month'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Earnings ($)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Bookings'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    };
    
    new Chart(ctx, config);
}

function updateChart() {
    const period = document.getElementById('chartPeriod').value;
    
    // In a real application, this would fetch new data based on the period
    // For now, we'll just show a loading animation
    const chartContainer = document.querySelector('.chart-container');
    chartContainer.style.opacity = '0.5';
    
    setTimeout(() => {
        chartContainer.style.opacity = '1';
        showSuccessMessage('Chart updated successfully!');
    }, 1000);
}

function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-toast';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #2ed573;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        animation: slideInFromRight 0.5s ease-out;
        box-shadow: 0 4px 12px rgba(46, 213, 115, 0.3);
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            successDiv.remove();
        }, 500);
    }, 3000);
}

function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}