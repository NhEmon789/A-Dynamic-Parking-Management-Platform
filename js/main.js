// Global Variables
let isScrolled = false;
let currentRole = 'customer';
let currentWorkflow = 'customer';

// FAQ functionality
function initializeFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Close all other FAQ items
            faqItems.forEach(otherItem => {
                otherItem.classList.remove('active');
            });
             
            // Toggle current item
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
}

// Scroll animations
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    }, observerOptions);
    // Only observe elements that are not in the hero section
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        if (!el.closest('.hero')) {
            observer.observe(el);
        } else {
            // Instantly animate hero section elements on page load
            el.classList.add('animated');
        }
    });
}

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeHeader();
    initializeScrollAnimations();
    initializeFAQ();
    initializeWorkflowToggle();
    initializeTestimonialCarousel();
    initializeScrollReveal();
    initializeRippleEffect();
    
    // Initialize hero animations
    animateHeroContent();
});

// Header functionality
function initializeHeader() {
    const header = document.querySelector('.header');
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('navMenu');
    const navLinks = document.querySelectorAll('.nav-link');
    // Mobile menu toggle
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navMenu.contains(e.target)) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }
    // Only one nav-link active at a time
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Hero content animations
function animateHeroContent() {
    const heroTitle = document.querySelector('.hero-title');
    const heroSubtitle = document.querySelector('.hero-subtitle');
    const heroButtons = document.querySelector('.hero-buttons');
    
    if (heroTitle) {
        // Animate title word by word, ensuring correct spacing
        const words = heroTitle.textContent.split(/\s+/).filter(Boolean);
        heroTitle.innerHTML = '';
        words.forEach((word, index) => {
            const span = document.createElement('span');
            span.textContent = word;
            span.style.opacity = '0';
            span.style.transform = 'translateY(50px)';
            span.style.display = 'inline-block';
            span.style.transition = 'all 0.8s ease';
            span.style.transitionDelay = `${index * 0.1}s`;
            heroTitle.appendChild(span);
            // Add a space after each word except the last
            if (index < words.length - 1) {
                heroTitle.appendChild(document.createTextNode(' '));
            }
            setTimeout(() => {
                span.style.opacity = '1';
                span.style.transform = 'translateY(0)';
            }, 100);
        });
    }
    
    // Animate subtitle and buttons
    setTimeout(() => {
        if (heroSubtitle) {
            heroSubtitle.style.opacity = '1';
            heroSubtitle.style.transform = 'translateY(0)';
        }
        if (heroButtons) {
            heroButtons.style.opacity = '1';
            heroButtons.style.transform = 'translateY(0)';
        }
    }, 600);
}

// Workflow toggle functionality
function initializeWorkflowToggle() {
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const workflows = document.querySelectorAll('.workflow');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const role = this.dataset.role;
            
            // Update active button
            toggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update active workflow
            workflows.forEach(w => {
                w.classList.remove('active');
                if (w.classList.contains(`${role}-workflow`)) {
                    w.classList.add('active');
                }
            });
            
            currentWorkflow = role;
            
            // Animate workflow steps
            setTimeout(() => {
                animateWorkflowSteps(role);
            }, 100);
        });
    });
}

// Animate workflow steps
function animateWorkflowSteps(role) {
    const steps = document.querySelectorAll(`.${role}-workflow .step`);
    
    steps.forEach((step, index) => {
        step.style.opacity = '0';
        step.style.transform = 'translateY(30px)';
        step.style.transition = 'all 0.6s ease';
        step.style.transitionDelay = `${index * 0.1}s`;
        
        setTimeout(() => {
            step.style.opacity = '1';
            step.style.transform = 'translateY(0)';
        }, 50);
    });
}

// Testimonial carousel
function initializeTestimonialCarousel() {
    const leftRow = document.querySelector('.row-left');
    const rightRow = document.querySelector('.row-right');
    
    if (leftRow && rightRow) {
        // Clone testimonial cards for infinite scroll
        const leftCards = leftRow.querySelectorAll('.testimonial-card');
        const rightCards = rightRow.querySelectorAll('.testimonial-card');
        
        leftCards.forEach(card => {
            const clone = card.cloneNode(true);
            leftRow.appendChild(clone);
        });
        
        rightCards.forEach(card => {
            const clone = card.cloneNode(true);
            rightRow.appendChild(clone);
        });
    }
}

// Scroll reveal animations
function initializeScrollReveal() {
    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    
    const revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    revealElements.forEach(el => {
        revealObserver.observe(el);
    });
}

// Ripple effect for buttons
function initializeRippleEffect() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.3)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s linear';
            ripple.style.pointerEvents = 'none';
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Utility functions
function animateCounter(element, start, end, duration) {
    const startTime = Date.now();
    const endTime = startTime + duration;
    
    function updateCounter() {
        const now = Date.now();
        const remaining = Math.max((endTime - now) / duration, 0);
        const progress = 1 - remaining;
        const value = Math.round(start + (end - start) * progress);
        
        element.textContent = value;
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }
    
    updateCounter();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Loading state management
function showLoading(element) {
    element.classList.add('loading');
    element.style.pointerEvents = 'none';
}

function hideLoading(element) {
    element.classList.remove('loading');
    element.style.pointerEvents = 'auto';
}

// Error handling
function showError(message, duration = 3000) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-toast';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff4757;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        animation: slideInFromRight 0.5s ease-out;
    `;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            errorDiv.remove();
        }, 500);
    }, duration);
}

function showSuccess(message, duration = 3000) {
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
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            successDiv.remove();
        }, 500);
    }, duration);
}

// Local storage helpers
function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
    } catch (error) {
        console.error('Error saving to localStorage:', error);
    }
}

function loadFromLocalStorage(key) {
    try {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    } catch (error) {
        console.error('Error loading from localStorage:', error);
        return null;
    }
}

// Initialize benefit cards animation
function initializeBenefitCards() {
    const benefitCards = document.querySelectorAll('.benefit-card');
    
    benefitCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(50px)';
        card.style.transition = 'all 0.8s ease';
        card.style.transitionDelay = `${index * 0.2}s`;
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        observer.observe(card);
    });
}

// Call initialization functions
document.addEventListener('DOMContentLoaded', function() {
    initializeBenefitCards();
});

// Close notification function
function closeNotification(button) {
    const notification = button.closest('.notification-item');
    if (notification) {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// View booking details function
function viewBookingDetails(bookingId) {
    // Mock booking details modal
    alert(`Viewing details for booking ${bookingId}`);
}

// Cancel booking function
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        alert('Booking cancelled successfully. Refund will be processed within 3-5 business days.');
        // In a real app, this would make an API call
    }
}

// Logout function
function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}

// Export functions for use in other files
window.FindMySpot = {
    showLoading,
    hideLoading,
    showError,
    showSuccess,
    saveToLocalStorage,
    loadFromLocalStorage,
    animateCounter,
    debounce,
    logout,
    closeNotification,
    viewBookingDetails,
    cancelBooking
};

function getAverageSectionLuminance() {
    // Find the section currently under the header
    const header = document.querySelector('.header');
    const sections = Array.from(document.querySelectorAll('section, main, .hero, .dashboard, .profile-page, .bookings-page, .add-spot-page, .listings-page'));
    const headerRect = header.getBoundingClientRect();
    const headerMid = headerRect.top + headerRect.height / 2;

    let currentSection = null;
    for (const section of sections) {
        const rect = section.getBoundingClientRect();
        if (rect.top <= headerMid && rect.bottom >= headerMid) {
            currentSection = section;
            break;
        }
    }
    if (!currentSection) return 255; // fallback to light

    // Try to get background color of the section
    let bg = window.getComputedStyle(currentSection).backgroundColor;
    if (!bg || bg === 'rgba(0, 0, 0, 0)' || bg === 'transparent') bg = '#fff';

    // Parse rgb/rgba or hex
    let r = 255, g = 255, b = 255;
    if (bg.startsWith('rgb')) {
        [r, g, b] = bg.match(/\d+/g).map(Number);
    } else if (bg.startsWith('#')) {
        if (bg.length === 7) {
            r = parseInt(bg.substr(1,2),16);
            g = parseInt(bg.substr(3,2),16);
            b = parseInt(bg.substr(5,2),16);
        }
    }
    // Perceived luminance formula
    return 0.299 * r + 0.587 * g + 0.114 * b;
}

function updateHeaderTheme() {
    const header = document.querySelector('.header');
    if (!header) return;
    const luminance = getAverageSectionLuminance();
    if (luminance < 128) {
        header.classList.add('header-light');
        header.classList.remove('header-dark');
    } else {
        header.classList.add('header-dark');
        header.classList.remove('header-light');
    }
}

window.addEventListener('scroll', updateHeaderTheme);
window.addEventListener('resize', updateHeaderTheme);
document.addEventListener('DOMContentLoaded', updateHeaderTheme);
