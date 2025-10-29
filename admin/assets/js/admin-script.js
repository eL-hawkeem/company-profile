/**
 * Admin Dashboard JavaScript
 * PT. Sarana Sentra Teknologi Utama
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const headerSidebarToggle = document.getElementById('headerSidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    let isSidebarCollapsed = false;
    let isMobile = window.innerWidth < 992;

    function initSidebar() {
        if (!isMobile) {
            const savedState = localStorage.getItem('sidebarCollapsed');
            if (savedState !== null) {
                isSidebarCollapsed = savedState === 'true';
            }
        }

        updateSidebarState();
    }
    
    function updateSidebarState() {
        const footer = document.querySelector('.footer');
        
        if (isMobile) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            if (mainContent) {
                mainContent.style.marginLeft = '0';
            }
            if (footer) {
                footer.style.marginLeft = '0';
            }
        } else {
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
                if (mainContent) {
                    mainContent.style.marginLeft = '60px';
                }
                if (footer) {
                    footer.style.marginLeft = '60px';
                }
            } else {
                sidebar.classList.remove('collapsed');
                if (mainContent) {
                    mainContent.style.marginLeft = '260px';
                }
                if (footer) {
                    footer.style.marginLeft = '260px';
                }
            }

            localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
        }
    }
    
    // Toggle sidebar
    function toggleSidebar() {
        if (isMobile) {
            const isVisible = sidebar.classList.contains('show');
            if (isVisible) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            } else {
                sidebar.classList.add('show');
                sidebarOverlay.classList.add('show');
            }
        } else {
            isSidebarCollapsed = !isSidebarCollapsed;
            updateSidebarState();
        }
    }
    
    // Handle window resize
    function handleResize() {
        const wasMobile = isMobile;
        isMobile = window.innerWidth < 992;
        
        if (wasMobile !== isMobile) {
            if (isMobile) {
                // Switch to mobile mode
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                }
                const footer = document.querySelector('.footer');
                if (footer) {
                    footer.style.marginLeft = '0';
                }
            } else {
                // Switch to desktop mode
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
                updateSidebarState();
            }
        }
    }
    
    // Event listeners
    if (headerSidebarToggle) {
        headerSidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleSidebar();
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            if (isMobile) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    }
    
    // Window resize listener
    window.addEventListener('resize', handleResize);
    
    // Initialize
    initSidebar();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
        
        // Escape to close sidebar on mobile
        if (e.key === 'Escape' && isMobile) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
    
    // Auto-expand submenu based on current page
    function autoExpandSubmenus() {
        const currentPath = window.location.pathname;
        const submenuToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
        
        submenuToggles.forEach(toggle => {
            const target = toggle.getAttribute('data-bs-target');
            const submenu = document.querySelector(target);
            
            if (submenu) {
                const links = submenu.querySelectorAll('a');
                let shouldExpand = false;
                
                links.forEach(link => {
                    if (link.href && currentPath.includes(link.getAttribute('href'))) {
                        shouldExpand = true;
                    }
                });
                
                if (shouldExpand) {
                    submenu.classList.add('show');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        });
    }
    
    // Initialize auto-expand
    autoExpandSubmenus();
});

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Export functions for global use
window.AdminDashboard = {
    showNotification,
    toggleSidebar: function() {
        const headerSidebarToggle = document.getElementById('headerSidebarToggle');
        if (headerSidebarToggle) {
            headerSidebarToggle.click();
        }
    }
};
