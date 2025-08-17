// Front page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Front page loaded');
    
    // Add smooth scrolling for anchor links
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add hover effects for feature cards
    const featureCards = document.querySelectorAll('.feature-card');
    featureCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add click tracking for analytics (placeholder)
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent.trim();
            const href = this.getAttribute('href');
            console.log(`Button clicked: ${action} -> ${href}`);
            
            // You can add analytics tracking here
            // Example: trackEvent('button_click', { action, destination: href });
        });
    });

    // Add loading animation for page elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe sections for animation
    const sections = document.querySelectorAll('section');
    sections.forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });

    // Check if user is authenticated and update UI accordingly
    checkAuthStatus();
});

// Function to check authentication status
async function checkAuthStatus() {
    try {
        const response = await fetch('/api/me');
        if (response.ok) {
            // User is authenticated
            updateUIForAuthenticatedUser();
        } else {
            // User is not authenticated
            updateUIForUnauthenticatedUser();
        }
    } catch (error) {
        console.log('User not authenticated');
        updateUIForUnauthenticatedUser();
    }
}

// Update UI for authenticated users
function updateUIForAuthenticatedUser() {
    const signInBtn = document.querySelector('a[href="/login"]');
    if (signInBtn) {
        signInBtn.textContent = 'Dashboard';
        signInBtn.href = '/users/me';
        signInBtn.classList.remove('btn-primary');
        signInBtn.classList.add('btn-secondary');
    }
}

// Update UI for unauthenticated users
function updateUIForUnauthenticatedUser() {
    // Keep default state for unauthenticated users
    console.log('User not authenticated, showing default UI');
}

// Utility function for analytics tracking (placeholder)
function trackEvent(eventName, properties = {}) {
    // This is a placeholder for analytics tracking
    // You can integrate with Google Analytics, Mixpanel, or other services
    console.log('Analytics event:', eventName, properties);
}
