// Landing page specific JavaScript

document.addEventListener('DOMContentLoaded', () => {
    // Hero title word animation
    const words = document.querySelectorAll('.hero-title .word');
    words.forEach((word, index) => {
        word.style.animationDelay = `${index * 0.2}s`;
    });

    // Testimonials carousel
    initTestimonialsCarousel();
    
    // Benefits cards animation
    initBenefitsAnimation();
    
    // Timeline animation
    initTimelineAnimation();
    
    // Auto-scroll testimonials
    startAutoScroll();
});

// Testimonials Carousel
function initTestimonialsCarousel() {
    const cards = document.querySelectorAll('.testimonial-card');
    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    let currentSlide = 0;
    const totalSlides = cards.length;
    
    function showSlide(index) {
        // Hide all cards
        cards.forEach(card => {
            card.classList.remove('active');
        });
        
        // Remove active class from all dots
        dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show current card and activate dot
        if (cards[index] && dots[index]) {
            cards[index].classList.add('active');
            dots[index].classList.add('active');
        }
        
        currentSlide = index;
    }
    
    function nextSlide() {
        const next = (currentSlide + 1) % totalSlides;
        showSlide(next);
    }
    
    function prevSlide() {
        const prev = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(prev);
    }
    
    // Event listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', nextSlide);
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', prevSlide);
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            showSlide(index);
        });
    });
    
    // Initialize first slide
    showSlide(0);
    
    // Return control functions for auto-scroll
    return { nextSlide, showSlide };
}

// Auto-scroll testimonials
function startAutoScroll() {
    const carousel = initTestimonialsCarousel();
    
    setInterval(() => {
        carousel.nextSlide();
    }, 5000); // Change slide every 5 seconds
}

// Benefits animation
function initBenefitsAnimation() {
    const benefitCards = document.querySelectorAll('.benefit-card');
    
    const benefitObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = entry.target.dataset.aosDelay || '0ms';
                entry.target.classList.add('aos-animate');
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '0px 0px -100px 0px'
    });
    
    benefitCards.forEach(card => {
        benefitObserver.observe(card);
    });
}

// Timeline animation
function initTimelineAnimation() {
    const timelineItems = document.querySelectorAll('.timeline-item');
    
    const timelineObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = entry.target.dataset.aosDelay || '0ms';
                entry.target.classList.add('aos-animate');
                
                // Animate the number with a pop effect
                const number = entry.target.querySelector('.timeline-number');
                if (number) {
                    number.style.animation = 'numberPop 0.6s ease-out';
                }
            }
        });
    }, {
        threshold: 0.3,
        rootMargin: '0px 0px -50px 0px'
    });
    
    timelineItems.forEach(item => {
        timelineObserver.observe(item);
    });
}

// Parallax effect for hero background
window.addEventListener('scroll', utils.throttle(() => {
    const scrolled = window.pageYOffset;
    const heroBackground = document.querySelector('.hero-background');
    
    if (heroBackground) {
        const speed = scrolled * 0.5;
        heroBackground.style.transform = `translateY(${speed}px)`;
    }
}, 16));

// Icon pulse animation
document.addEventListener('DOMContentLoaded', () => {
    const icons = document.querySelectorAll('.benefit-icon i');
    
    icons.forEach((icon, index) => {
        icon.style.animationDelay = `${index * 0.2}s`;
    });
});

// Star twinkle animation
document.addEventListener('DOMContentLoaded', () => {
    const stars = document.querySelectorAll('.stars i');
    
    stars.forEach((star, index) => {
        star.style.animationDelay = `${index * 0.2}s`;
    });
});

// Button hover effects
document.querySelectorAll('.btn').forEach(button => {
    button.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.05)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
});

// Card hover effects
document.querySelectorAll('.benefit-card, .timeline-content').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px) scale(1.02)';
    });
    
    button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Smooth reveal animation for sections
const revealSections = document.querySelectorAll('.benefits, .how-it-works, .testimonials');

const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
});

revealSections.forEach(section => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(50px)';
    section.style.transition = 'all 0.8s ease-out';
    sectionObserver.observe(section);
});

// Add loading animation
window.addEventListener('load', () => {
    document.body.classList.add('loaded');
    
    // Trigger hero animations
    const heroTitle = document.querySelector('.hero-title');
    const heroSubtitle = document.querySelector('.hero-subtitle');
    const heroButtons = document.querySelector('.hero-buttons');
    
    if (heroTitle) {
        heroTitle.classList.add('animate');
    }
    if (heroSubtitle) {
        heroSubtitle.classList.add('animate');
    }
    if (heroButtons) {
        heroButtons.classList.add('animate');
    }
});

// Performance optimization: Lazy load images
const images = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            imageObserver.unobserve(img);
        }
    });
});

images.forEach(img => {
    imageObserver.observe(img);
});