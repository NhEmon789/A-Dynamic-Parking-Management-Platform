// Customer bookings page functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeBookingsPage();
    loadBookingsData();
});

let bookingsData = {
    upcoming: [
        {
            id: 'PKG-2024-001',
            spotName: 'Downtown Parking Garage',
            date: 'Today, Dec 15',
            time: '2:00 PM - 6:00 PM',
            location: '123 Main St, Downtown',
            vehicle: 'Sedan',
            cost: '$34.00',
            status: 'confirmed'
        },
        {
            id: 'PKG-2024-002',
            spotName: 'Airport Long-term Parking',
            date: 'Dec 18 - Dec 22',
            time: '6:00 AM - 11:00 PM',
            location: 'Airport Terminal 1',
            vehicle: 'SUV',
            cost: '$240.00',
            status: 'confirmed'
        }
    ],
    past: [
        {
            id: 'PKG-2024-003',
            spotName: 'City Center Parking',
            date: 'Dec 10, 2024',
            time: '9:00 AM - 5:00 PM',
            location: '456 Center St, City',
            vehicle: 'Hatchback',
            cost: '$48.00',
            status: 'completed'
        },
        {
            id: 'PKG-2024-004',
            spotName: 'Mall Parking Lot',
            date: 'Dec 5, 2024',
            time: '2:00 PM - 8:00 PM',
            location: '789 Shopping Mall Dr',
            vehicle: 'Sedan',
            cost: '$24.00',
            status: 'completed'
        }
    ],
    cancelled: [
        {
            id: 'PKG-2024-005',
            spotName: 'Business District Parking',
            date: 'Dec 8, 2024',
            time: '10:00 AM - 4:00 PM',
            location: '321 Business Ave',
            vehicle: 'SUV',
            cost: '$36.00',
            status: 'cancelled'
        }
    ]
};

function initializeBookingsPage() {
    // Initialize role toggle
    const roleToggle = document.getElementById('roleToggle');
    if (roleToggle) {
        roleToggle.addEventListener('click', function() {
            window.location.href = 'host-dashboard.html';
        });
    }
    
    // Initialize tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.textContent.toLowerCase();
            showTab(tab);
        });
    });
}

function showTab(tabName) {
    // Update active tab button
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === tabName) {
            btn.classList.add('active');
        }
    });
    
    // Update active tab content
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.remove('active');
        if (content.id === tabName) {
            content.classList.add('active');
        }
    });
    
    // Add animation to tab content
    const activeContent = document.getElementById(tabName);
    if (activeContent) {
        activeContent.style.opacity = '0';
        activeContent.style.transform = 'translateY(20px)';
        activeContent.style.transition = 'all 0.3s ease';
        
        setTimeout(() => {
            activeContent.style.opacity = '1';
            activeContent.style.transform = 'translateY(0)';
        }, 100);
    }
}

function loadBookingsData() {
    // Load bookings from localStorage or use mock data
    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
    
    // Populate each tab with bookings
    populateBookings('upcoming', bookingsData.upcoming);
    populateBookings('past', bookingsData.past);
    populateBookings('cancelled', bookingsData.cancelled);
}

function populateBookings(tabName, bookings) {
    const tabContent = document.getElementById(tabName);
    const bookingsGrid = tabContent.querySelector('.bookings-grid');
    
    if (bookings.length === 0) {
        bookingsGrid.innerHTML = `
            <div class="no-bookings">
                <h3>No ${tabName} bookings</h3>
                <p>Start by <a href="search.html">finding a parking spot</a>.</p>
            </div>
        `;
        return;
    }
    
    bookingsGrid.innerHTML = bookings.map(booking => `
        <div class="booking-card glass-card">
            <div class="booking-header">
                <h3>${booking.spotName}</h3>
                <span class="booking-status ${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
            </div>
            <div class="booking-details">
                <div class="detail-item">
                    <span class="label">Date:</span>
                    <span class="value">${booking.date}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Time:</span>
                    <span class="value">${booking.time}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Location:</span>
                    <span class="value">${booking.location}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Vehicle:</span>
                    <span class="value">${booking.vehicle}</span>
                </div>
                <div class="detail-item">
                    <span class="label">${booking.status === 'cancelled' ? 'Refund:' : 'Total Cost:'}</span>
                    <span class="value">${booking.cost}</span>
                </div>
            </div>
            <div class="booking-actions">
                <button class="btn btn-ghost btn-sm" onclick="viewBookingDetails('${booking.id}')">View Details</button>
                ${getBookingActions(booking)}
            </div>
        </div>
    `).join('');
}

function getBookingActions(booking) {
    switch (booking.status) {
        case 'confirmed':
            return `<button class="btn btn-danger btn-sm" onclick="cancelBooking('${booking.id}')">Cancel</button>`;
        case 'completed':
        case 'cancelled':
            return `<button class="btn btn-primary btn-sm" onclick="rebookSpot('${booking.id}')">Rebook</button>`;
        default:
            return '';
    }
}

function viewBookingDetails(bookingId) {
    // Find booking details
    const booking = findBookingById(bookingId);
    if (!booking) return;
    
    // Show booking details modal
    const modal = document.getElementById('bookingDetailsModal');
    const modalContent = modal.querySelector('.booking-details-content');
    
    modalContent.innerHTML = `
        <div class="booking-detail-section">
            <h4>Booking Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Booking ID:</span>
                    <span class="value">${booking.id}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Spot Name:</span>
                    <span class="value">${booking.spotName}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Date & Time:</span>
                    <span class="value">${booking.date} | ${booking.time}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Location:</span>
                    <span class="value">${booking.location}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Vehicle:</span>
                    <span class="value">${booking.vehicle}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Status:</span>
                    <span class="value status-${booking.status}">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Cost:</span>
                    <span class="value">${booking.cost}</span>
                </div>
            </div>
        </div>
        
        <div class="booking-detail-section">
            <h4>Host Information</h4>
            <div class="host-info">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=50&h=50&fit=crop&crop=face" alt="Host">
                <div>
                    <h5>John Smith</h5>
                    <p>Host since 2023</p>
                </div>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
    
    // Add entrance animation
    const modalContentEl = modal.querySelector('.modal-content');
    modalContentEl.style.opacity = '0';
    modalContentEl.style.transform = 'scale(0.9)';
    modalContentEl.style.transition = 'all 0.3s ease';
    
    setTimeout(() => {
        modalContentEl.style.opacity = '1';
        modalContentEl.style.transform = 'scale(1)';
    }, 100);
}

function closeBookingDetailsModal() {
    const modal = document.getElementById('bookingDetailsModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function cancelBooking(bookingId) {
    const booking = findBookingById(bookingId);
    if (!booking) return;
    
    // Show cancel confirmation modal
    const modal = document.getElementById('cancelBookingModal');
    document.getElementById('cancelBookingId').textContent = bookingId;
    document.getElementById('refundAmount').textContent = booking.cost;
    
    modal.style.display = 'flex';
    
    // Add entrance animation
    const modalContent = modal.querySelector('.modal-content');
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    modalContent.style.transition = 'all 0.3s ease';
    
    setTimeout(() => {
        modalContent.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';
    }, 100);
}

function closeCancelBookingModal() {
    const modal = document.getElementById('cancelBookingModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function confirmCancellation() {
    const bookingId = document.getElementById('cancelBookingId').textContent;
    
    // Simulate cancellation process
    const confirmButton = document.querySelector('#cancelBookingModal .btn-danger');
    const originalText = confirmButton.textContent;
    
    confirmButton.textContent = 'Cancelling...';
    confirmButton.disabled = true;
    
    setTimeout(() => {
        // Move booking from upcoming to cancelled
        const upcomingIndex = bookingsData.upcoming.findIndex(b => b.id === bookingId);
        if (upcomingIndex !== -1) {
            const booking = bookingsData.upcoming.splice(upcomingIndex, 1)[0];
            booking.status = 'cancelled';
            bookingsData.cancelled.push(booking);
        }
        
        // Refresh the bookings display
        loadBookingsData();
        
        // Close modal
        closeCancelBookingModal();
        
        // Show success message
        showSuccessMessage('Booking cancelled successfully. Refund will be processed within 3-5 business days.');
        
        // Reset button
        confirmButton.textContent = originalText;
        confirmButton.disabled = false;
    }, 2000);
}

function rebookSpot(bookingId) {
    const booking = findBookingById(bookingId);
    if (!booking) return;
    
    // Store booking data for rebooking
    localStorage.setItem('rebookingData', JSON.stringify(booking));
    
    // Redirect to search page
    window.location.href = 'search.html';
}

function findBookingById(bookingId) {
    for (const category of Object.values(bookingsData)) {
        const booking = category.find(b => b.id === bookingId);
        if (booking) return booking;
    }
    return null;
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
        max-width: 300px;
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            successDiv.remove();
        }, 500);
    }, 4000);
}

function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}