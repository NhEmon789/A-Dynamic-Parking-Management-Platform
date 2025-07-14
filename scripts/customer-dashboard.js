// Customer Dashboard JavaScript

document.addEventListener('DOMContentLoaded', () => {
    checkAuthentication();
    loadUserData();
    loadUpcomingBookings();
    loadNotifications();
    initializeAnimations();
});

// Check if user is authenticated
function checkAuthentication() {
    const isLoggedIn = utils.storage.get('isLoggedIn');
    const userType = utils.storage.get('userType');
    
    if (!isLoggedIn || userType !== 'customer') {
        window.location.href = 'login.html';
        return;
    }
}

// Load user data
function loadUserData() {
    const userData = utils.storage.get('userData');
    if (userData) {
        document.getElementById('userName').textContent = userData.name || 'User';
    }
    
    // Load booking statistics
    loadBookingStats();
}

// Load booking statistics
function loadBookingStats() {
    const bookings = utils.storage.get('customerBookings') || [];
    const upcomingBookings = bookings.filter(booking => {
        const bookingDate = new Date(booking.date);
        const today = new Date();
        return bookingDate >= today && booking.status !== 'cancelled';
    });
    
    // Calculate total spent
    const totalSpent = bookings.reduce((total, booking) => {
        if (booking.status === 'completed' || booking.status === 'confirmed') {
            const cost = parseInt(booking.totalCost.replace('৳', ''));
            return total + cost;
        }
        return total;
    }, 0);
    
    // Update stats with animation
    animateCounter('totalBookings', bookings.length);
    animateCounter('upcomingBookings', upcomingBookings.length);
    
    // Update total spent (if element exists)
    const totalSpentElement = document.querySelector('.stat-number:last-child');
    if (totalSpentElement) {
        animateCounter(totalSpentElement, totalSpent, '৳');
    }
}

// Animate counter
function animateCounter(elementId, targetValue, prefix = '') {
    const element = typeof elementId === 'string' ? document.getElementById(elementId) : elementId;
    if (!element) return;
    
    let currentValue = 0;
    const increment = targetValue / 50;
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        element.textContent = prefix + Math.floor(currentValue).toLocaleString();
    }, 20);
}

// Load upcoming bookings
function loadUpcomingBookings() {
    const bookings = utils.storage.get('customerBookings') || [];
    const upcomingBookings = bookings
        .filter(booking => {
            const bookingDate = new Date(booking.date);
            const today = new Date();
            return bookingDate >= today && booking.status !== 'cancelled';
        })
        .slice(0, 3); // Show only first 3
    
    const container = document.getElementById('upcomingBookingsGrid');
    
    if (upcomingBookings.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Bookings</h3>
                <p>You don't have any upcoming parking reservations.</p>
                <a href="search.html" class="btn btn-primary">Find Parking</a>
            </div>
        `;
        return;
    }
    
    container.innerHTML = upcomingBookings.map(booking => `
        <div class="booking-card">
            <div class="booking-header">
                <div class="booking-info">
                    <h3>${booking.spotName}</h3>
                    <div class="booking-address">
                        <i class="fas fa-map-marker-alt"></i>
                        Location details
                    </div>
                </div>
                <div class="booking-status ${booking.status}">
                    ${booking.status}
                </div>
            </div>
            
            <div class="booking-details">
                <div class="booking-detail">
                    <div class="detail-label">Date</div>
                    <div class="detail-value">${utils.formatDate(booking.date)}</div>
                </div>
                <div class="booking-detail">
                    <div class="detail-label">Time</div>
                    <div class="detail-value">${utils.formatTime(booking.startTime)} - ${utils.formatTime(booking.endTime)}</div>
                </div>
                <div class="booking-detail">
                    <div class="detail-label">Vehicle</div>
                    <div class="detail-value">${booking.carType.charAt(0).toUpperCase() + booking.carType.slice(1)}</div>
                </div>
                <div class="booking-detail">
                    <div class="detail-label">Total Cost</div>
                    <div class="detail-value">${booking.totalCost}</div>
                </div>
            </div>
            
            <div class="booking-actions">
                <button class="btn btn-secondary" onclick="viewBookingDetails('${booking.id}')">
                    <i class="fas fa-eye"></i>
                    View Details
                </button>
                <button class="btn btn-ghost" onclick="cancelBooking('${booking.id}')">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </div>
    `).join('');
}

// Load notifications
function loadNotifications() {
    // Mock notifications data
    const notifications = [
        {
            id: 1,
            type: 'success',
            title: 'Booking Confirmed',
            message: 'Your parking reservation at Dhanmondi Plaza has been confirmed.',
            time: '2 hours ago',
            icon: 'check-circle'
        },
        {
            id: 2,
            type: 'info',
            title: 'New Parking Spots Available',
            message: 'Check out new parking options in Gulshan area.',
            time: '1 day ago',
            icon: 'info-circle'
        },
        {
            id: 3,
            type: 'warning',
            title: 'Booking Reminder',
            message: 'Your parking reservation starts in 30 minutes.',
            time: '3 days ago',
            icon: 'clock'
        }
    ];
    
    const container = document.getElementById('notificationsContainer');
    
    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell"></i>
                <h3>No Notifications</h3>
                <p>You're all caught up! No new notifications.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notifications.map(notification => `
        <div class="notification-item" data-notification-id="${notification.id}">
            <div class="notification-icon ${notification.type}">
                <i class="fas fa-${notification.icon}"></i>
            </div>
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.message}</p>
                <div class="notification-time">${notification.time}</div>
            </div>
            <button class="notification-close" onclick="dismissNotification(${notification.id})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

// Initialize animations
function initializeAnimations() {
    // Stagger animation for action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Stagger animation for suggestion cards
    const suggestionCards = document.querySelectorAll('.suggestion-card');
    suggestionCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    // Observe animated elements
    document.querySelectorAll('[class*="fade"], [class*="slide"]').forEach(el => {
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });
}

// View booking details
function viewBookingDetails(bookingId) {
    utils.storage.set('selectedBookingId', bookingId);
    window.location.href = 'customer-bookings.html';
}

// Cancel booking
function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }
    
    let bookings = utils.storage.get('customerBookings') || [];
    const bookingIndex = bookings.findIndex(b => b.id === bookingId);
    
    if (bookingIndex !== -1) {
        bookings[bookingIndex].status = 'cancelled';
        utils.storage.set('customerBookings', bookings);
        
        // Show success message
        showNotification('Booking cancelled successfully', 'success');
        
        // Reload bookings
        loadUpcomingBookings();
        loadBookingStats();
    }
}

// Dismiss notification
function dismissNotification(notificationId) {
    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notificationElement) {
        notificationElement.style.animation = 'slideOutRight 0.3s ease-out forwards';
        setTimeout(() => {
            notificationElement.remove();
        }, 300);
    }
}

// Clear all notifications
function clearAllNotifications() {
    const container = document.getElementById('notificationsContainer');
    const notifications = container.querySelectorAll('.notification-item');
    
    notifications.forEach((notification, index) => {
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out forwards';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, index * 100);
    });
    
    setTimeout(() => {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-bell"></i>
                <h3>No Notifications</h3>
                <p>You're all caught up! No new notifications.</p>
            </div>
        `;
    }, notifications.length * 100 + 300);
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}"></i>
        </div>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Switch to host
function switchToHost() {
    utils.storage.set('userType', 'host');
    window.location.href = 'host-dashboard.html';
}

// Logout
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        utils.storage.remove('isLoggedIn');
        utils.storage.remove('userData');
        utils.storage.remove('userType');
        window.location.href = 'index.html';
    }
}

// Add notification toast styles
const style = document.createElement('style');
style.textContent = `
    .notification-toast {
        position: fixed;
        top: 100px;
        right: 20px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--text-primary);
        font-weight: 500;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease-out;
        max-width: 300px;
    }
    
    .notification-toast.show {
        transform: translateX(0);
    }
    
    .notification-toast.success {
        border-left: 4px solid #00aa00;
    }
    
    .notification-toast.error {
        border-left: 4px solid #ff4444;
    }
    
    .notification-toast.info {
        border-left: 4px solid #007bff;
    }
    
    .toast-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    
    .notification-toast.success .toast-icon {
        background: rgba(0, 170, 0, 0.1);
        color: #00aa00;
    }
    
    .notification-toast.error .toast-icon {
        background: rgba(255, 68, 68, 0.1);
        color: #ff4444;
    }
    
    .notification-toast.info .toast-icon {
        background: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    @keyframes slideOutRight {
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);