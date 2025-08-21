// User profile page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('User profile page loaded');
    
    // Load user data when page loads
    loadUserProfile();
    
    // Add event listeners
    addEventListeners();
    
    // Add page animations
    addPageAnimations();
});

// Function to load user profile data
async function loadUserProfile() {
    try {
        showLoadingState();
        
        const response = await fetch('/api/me');
        
        if (!response.ok) {
            if (response.status === 401) {
                // User not authenticated, redirect to login
                window.location.href = '/auth0/login';
                return;
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const userData = await response.json();
        console.log('User data loaded:', userData);
        
        // Display user data
        displayUserProfile(userData);
        
        // Show debug section if in development mode
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            showDebugSection(userData);
        }
        
    } catch (error) {
        console.error('Error loading user profile:', error);
        showErrorState(error.message);
    }
}

// Function to display user profile data
function displayUserProfile(userData) {
    // Update profile header
    const userName = document.getElementById('user-name');
    const userEmail = document.getElementById('user-email');
    const userAvatar = document.getElementById('user-avatar');
    
    if (userName) {
        userName.textContent = userData.name || userData.nickname || userData.email || 'User';
    }
    
    if (userEmail) {
        userEmail.textContent = userData.email || 'No email provided';
    }
    
    if (userAvatar && userData.picture) {
        userAvatar.src = userData.picture;
        userAvatar.alt = `Avatar for ${userData.name || userData.email}`;
    }
    
    // Update profile details
    updateProfileDetail('user-id', userData.sub || userData.user_id || 'N/A');
    updateProfileDetail('email-verified', userData.email_verified ? 'Yes' : 'No');
    updateProfileDetail('updated-at', formatDate(userData.updated_at));
    updateProfileDetail('created-at', formatDate(userData.created_at));
    
    // Hide loading state
    hideLoadingState();
}

// Function to update a profile detail field
function updateProfileDetail(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

// Function to format date
function formatDate(timestamp) {
    if (!timestamp) return 'N/A';
    
    try {
        const date = new Date(timestamp * 1000); // Auth0 uses Unix timestamp
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    } catch (error) {
        // If timestamp is already a date string, try parsing it directly
        try {
            const date = new Date(timestamp);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        } catch (error) {
            return timestamp; // Return as-is if we can't parse it
        }
    }
}

// Function to show debug section
function showDebugSection(userData) {
    const debugSection = document.getElementById('debug-section');
    const rawUserData = document.getElementById('raw-user-data');
    
    if (debugSection && rawUserData) {
        debugSection.style.display = 'block';
        rawUserData.textContent = JSON.stringify(userData, null, 2);
    }
}

// Function to show loading state
function showLoadingState() {
    const loadingElements = document.querySelectorAll('[id$="-loading"]');
    loadingElements.forEach(element => {
        element.style.display = 'block';
    });
    
    // Add loading class to profile card
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.classList.add('loading');
    }
}

// Function to hide loading state
function hideLoadingState() {
    const loadingElements = document.querySelectorAll('[id$="-loading"]');
    loadingElements.forEach(element => {
        element.style.display = 'none';
    });
    
    // Remove loading class from profile card
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.classList.remove('loading');
    }
}

// Function to show error state
function showErrorState(errorMessage) {
    hideLoadingState();
    
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.innerHTML = `
            <div class="error-state">
                <div class="error-icon">❌</div>
                <h2>Error Loading Profile</h2>
                <p>${errorMessage}</p>
                <div class="error-actions">
                    <button onclick="location.reload()" class="btn btn-primary">Retry</button>
                    <a href="/" class="btn btn-secondary">Go Home</a>
                </div>
            </div>
        `;
    }
}

// Function to add event listeners
function addEventListeners() {
    // Add click tracking for buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.textContent.trim();
            const href = this.getAttribute('href');
            console.log(`Button clicked: ${action} -> ${href}`);
            
            // Track profile page interaction
            trackEvent('profile_page_interaction', { 
                action, 
                destination: href,
                timestamp: new Date().toISOString()
            });
        });
    });
    
    // Add logout confirmation
    const logoutBtn = document.querySelector('a[href="/auth0/logout"]');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to sign out?')) {
                e.preventDefault();
            }
        });
    }
}

// Function to add page animations
function addPageAnimations() {
    // Add fade-in animation for profile card
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.style.opacity = '0';
        profileCard.style.transform = 'translateY(20px)';
        profileCard.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            profileCard.style.opacity = '1';
            profileCard.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Add stagger animation for detail items
    const detailItems = document.querySelectorAll('.detail-item');
    detailItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        item.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        
        setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 200 + (index * 100));
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

// Add CSS for loading and error states
const style = document.createElement('style');
style.textContent = `
    .profile-card.loading {
        opacity: 0.7;
        pointer-events: none;
    }
    
    .error-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .error-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    .error-state h2 {
        color: #e53e3e;
        margin-bottom: 1rem;
    }
    
    .error-state p {
        color: #718096;
        margin-bottom: 2rem;
    }
    
    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .profile-card {
        animation: fadeInUp 0.6s ease-out;
    }
`;
document.head.appendChild(style);
