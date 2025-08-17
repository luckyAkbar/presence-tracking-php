// Logout success page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Logout success page loaded');
    
    // Add a small delay before showing the page content for better UX
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
    
    // Add click tracking for buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent.trim();
            const href = this.getAttribute('href');
            console.log(`Button clicked: ${action} -> ${href}`);
            
            // Track logout success page interaction
            trackEvent('logout_success_interaction', { 
                action, 
                destination: href,
                timestamp: new Date().toISOString()
            });
        });
    });

    // Auto-redirect to home page after 10 seconds (optional)
    let redirectTimer = setTimeout(() => {
        console.log('Auto-redirecting to home page');
        window.location.href = '/';
    }, 10000);

    // Clear timer if user interacts with the page
    document.addEventListener('click', function() {
        clearTimeout(redirectTimer);
        console.log('User interaction detected, auto-redirect cancelled');
    });

    // Add security reminder functionality
    addSecurityReminders();
    
    // Add page visibility tracking
    addPageVisibilityTracking();
});

// Function to add security reminders
function addSecurityReminders() {
    const securityNote = document.querySelector('.security-note');
    if (securityNote) {
        // Add a subtle animation to draw attention
        securityNote.style.animation = 'pulse 2s infinite';
        
        // Add click to expand functionality
        securityNote.addEventListener('click', function() {
            this.style.transform = 'scale(1.02)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    }
}

// Function to add page visibility tracking
function addPageVisibilityTracking() {
    let pageHidden = false;
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            pageHidden = true;
            console.log('Page hidden - user may have switched tabs or minimized');
        } else if (pageHidden) {
            pageHidden = false;
            console.log('Page visible again');
            
            // Track when user returns to the page
            trackEvent('logout_success_page_return', {
                timestamp: new Date().toISOString(),
                timeHidden: Date.now() - (performance.now() + performance.timing.navigationStart)
            });
        }
    });
}

// Function to track events (placeholder for analytics)
function trackEvent(eventName, properties = {}) {
    // This is a placeholder for analytics tracking
    // You can integrate with Google Analytics, Mixpanel, or other services
    console.log('Analytics event:', eventName, properties);
    
    // Example: Send to your analytics service
    // analytics.track(eventName, properties);
}

// Add CSS animation for security note
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(229, 62, 62, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(229, 62, 62, 0); }
        100% { box-shadow: 0 0 0 0 rgba(229, 62, 62, 0); }
    }
`;
document.head.appendChild(style);

// Add smooth page transition
document.body.style.opacity = '0';
document.body.style.transition = 'opacity 0.5s ease-in-out';
