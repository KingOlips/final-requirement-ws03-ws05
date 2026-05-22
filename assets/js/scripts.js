/* PharmTrack Global Scripts */

// Sidebar Functionality
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}

function closeSidebar() {
    document.body.classList.remove('sidebar-open');
}

// Password Visibility Toggle
function togglePasswordVisibility(id, el) {
    const input = document.getElementById(id);
    if (!input) return;

    if (input.type === 'password') {
        input.type = 'text';
        if (el.classList.contains('bx-hide')) {
            el.classList.replace('bx-hide', 'bx-show');
        }
    } else {
        input.type = 'password';
        if (el.classList.contains('bx-show')) {
            el.classList.replace('bx-show', 'bx-hide');
        }
    }
}

// Dark Mode Logic
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const moonIcon = document.getElementById('theme-icon-moon');
    const sunIcon = document.getElementById('theme-icon-sun');
    if (moonIcon && sunIcon) {
        if (theme === 'dark') {
            moonIcon.style.display = 'none';
            sunIcon.style.display = 'block';
        } else {
            moonIcon.style.display = 'block';
            sunIcon.style.display = 'none';
        }
    }
}

// Shopping Cart Functionality
function setCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

function addToCart(id, name) {
    const formData = new FormData();
    formData.append('medicine_id', id);

    fetch((window.BASE_URL || '') + 'actions/add_to_cart.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(name + ' Added!', 'Item saved to your cart.', 'success');
                if (typeof data.cart_count !== 'undefined') {
                    setCartBadge(parseInt(data.cart_count, 10));
                }
            } else {
                showToast('Error', data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Could not add to cart.', 'danger');
        });
}

function updateQuantity(id, action, btnEl) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', action);
    formData.append('ajax', '1');

    const card = btnEl.closest('.cart-item-card');
    const qtyDisplay = card.querySelector('.qty-val');
    const itemTotalDisplay = card.querySelector('.total-price');
    const grandTotalDisplay = document.getElementById('grand-total-val');
    const sidebarTotalDisplay = document.getElementById('sidebar-total-val');

    const url = (window.BASE_URL || '../') + 'actions/update_cart.php';
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            qtyDisplay.innerText = data.new_qty;
            if (itemTotalDisplay) itemTotalDisplay.innerText = '₱' + data.item_total;
            if (grandTotalDisplay) grandTotalDisplay.innerText = '₱' + data.grand_total;
            if (sidebarTotalDisplay) sidebarTotalDisplay.innerText = '₱' + data.grand_total;
            
            // Optional: Subtle animation on update
            qtyDisplay.style.transform = 'scale(1.2)';
            setTimeout(() => qtyDisplay.style.transform = 'scale(1)', 200);
        } else {
            showToast('Error', data.message || 'Could not update quantity.', 'danger');
        }
    })
    .catch(error => {
        showToast('Error', 'Connection failed to: ' + url, 'danger');
    });
}

function removeFromCart(id, el) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'remove');
    formData.append('ajax', '1');

    const url = (window.BASE_URL || '') + 'actions/update_cart.php';
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Remove the item card from DOM
            const card = el.closest('.cart-item-card');
            if (card) card.remove();

            // Update grand total
            const grandTotalEl = document.getElementById('grand-total-val');
            if (grandTotalEl && data.grand_total !== undefined) {
                grandTotalEl.innerText = '₱' + data.grand_total;
            }

            // Update cart badge
            if (typeof data.cart_count !== 'undefined') {
                setCartBadge(parseInt(data.cart_count, 10));
                // Update header count text if present
                const topSub = document.querySelector('.topnav-sub');
                if (topSub) {
                    topSub.innerText = (data.cart_count) + ' Medicines selected';
                }

                // If cart is empty, show empty state
                if (parseInt(data.cart_count, 10) === 0) {
                    const cartGrid = document.querySelector('.cart-grid');
                    if (cartGrid) {
                        // Replace the entire grid layout with your styled empty container
                        cartGrid.outerHTML = `
                        <div class="card" style="text-align: center; padding: 5rem 2rem;">
                            <div style="width: 80px; height: 80px; background: rgba(79, 70, 229, 0.05); color: var(--primary); border-radius: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                                <i class='bx bx-shopping-bag' style='font-size: 2.5rem;'></i>
                            </div>
                            <h2 style="font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">Your cart is empty</h2>
                            <p style="color: var(--text-muted); margin-bottom: 2rem;">Looks like you haven't added any medicines to your selection yet.</p>
                            <a href="medicines.php" class="btn btn-primary" style="padding: 1rem 2.5rem;">Browse Medicines</a>
                        </div>
                        `;
                    }
                    // also update summary totals
                    const subtotalEl = document.querySelector('.summary-row span[style*="color: var(--text-main)"]');
                    const grand = document.getElementById('grand-total-val');
                    if (grand) grand.innerText = '₱0.00';
                }
            }

            showToast('Removed', 'Item removed from your cart.', 'success');
        } else {
            showToast('Error', data.message || 'Could not remove item.', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Error', 'Connection failed to: ' + url, 'danger');
    });
}

function showToast(title, message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} animate-slide-in`;

    const icon = type === 'success' ? 'bx-check-circle' : (type === 'danger' ? 'bx-error' : 'bx-info-circle');

    toast.innerHTML = `
        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.5rem;">
            <div class="toast-icon" style="font-size: 1.5rem;"><i class='bx ${icon}'></i></div>
            <div style="flex: 1;">
                <div style="font-weight: 700; font-size: 0.9rem; margin-bottom: 2px;">${title}</div>
                <div style="font-size: 0.8rem; opacity: 0.9;">${message}</div>
            </div>
            <button onclick="this.closest('.toast').remove()" style="background:none; border:none; color:inherit; opacity:0.5; cursor:pointer; padding: 0.25rem;">
                <i class='bx bx-x' style='font-size: 1.25rem;'></i>
            </button>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

function showUndoToast(message, undoUrl) {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-info animate-slide-in`;
    toast.style.minWidth = '350px';

    toast.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="toast-icon" style="font-size: 1.5rem;"><i class='bx bx-trash'></i></div>
                <div style="font-size: 0.875rem;">${message}</div>
            </div>
            <a href="${undoUrl}" style="
                background: var(--surface); 
                color: var(--primary); 
                padding: 0.4rem 0.8rem; 
                border-radius: 6px; 
                text-decoration: none; 
                font-weight: 700; 
                font-size: 0.75rem;
                box-shadow: var(--shadow-sm);
            ">UNDO</a>
        </div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.add('animate-slide-out');
            setTimeout(() => toast.remove(), 400);
        }
    }, 5000); // Give users 5 seconds to undo
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position: fixed; bottom: 2rem; right: 2rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.75rem; max-width: 400px;';
    document.body.appendChild(container);
    return container;
}

function showConfirm(title, message, onConfirm) {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-danger animate-slide-in`;
    toast.style.background = 'var(--card-bg)';
    toast.style.border = '1px solid var(--border)';
    toast.style.color = 'var(--text-main)';
    toast.style.boxShadow = 'var(--shadow-lg)';

    toast.innerHTML = `
        <div style="padding: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <div class="toast-icon" style="color: var(--danger); font-size: 1.5rem;"><i class='bx bx-help-circle'></i></div>
                <div style="font-weight: 700; font-size: 1rem;">${title}</div>
            </div>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0 0 1.25rem 0; line-height: 1.5;">${message}</p>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button class="btn-cancel" style="padding: 0.5rem 1rem; font-size: 0.8125rem; font-weight: 600; background: var(--background); border: 1px solid var(--border); color: var(--text-main); border-radius: 8px; cursor: pointer;">Cancel</button>
                <button class="btn-confirm" style="padding: 0.5rem 1rem; font-size: 0.8125rem; font-weight: 600; background: var(--danger); border: none; color: white; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">Delete Now</button>
            </div>
        </div>
    `;

    container.appendChild(toast);

    toast.querySelector('.btn-cancel').onclick = () => {
        toast.classList.add('animate-slide-out');
        setTimeout(() => toast.remove(), 400);
    };

    toast.querySelector('.btn-confirm').onclick = () => {
        toast.remove();
        onConfirm();
    };
}

// Initialize components on load
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initPageTransitions();
    initDashboardHistoryLock();
});

// Auto-logout when user clicks browser Back button from the dashboard
function initDashboardHistoryLock() {
    const path = window.location.pathname;
    if (path.endsWith('index.php') || path === '/' || path.endsWith('/final-requirement-ws03-ws05/')) {
        // Push a dummy state so the back button triggers popstate first
        window.history.pushState({ dashboardLock: true }, '', window.location.href);
        window.addEventListener('popstate', function (e) {
            // Back button pressed — log the user out immediately
            window.location.replace('logout.php');
        });
    }
}

// Page Transition Logic
function initPageTransitions() {
    const bar = document.createElement('div');
    bar.className = 'loading-bar';
    document.body.appendChild(bar);

    document.addEventListener('click', e => {
        const link = e.target.closest('a');
        if (link &&
            link.href &&
            link.href.startsWith(window.location.origin) &&
            !link.href.includes('#') &&
            !link.href.includes('logout.php') &&
            !link.onclick &&
            link.target !== '_blank' &&
            !e.ctrlKey && !e.metaKey && !e.shiftKey) {

            const url = link.href;
            const currentUrl = window.location.href.split('?')[0];
            const targetUrl = url.split('?')[0];

            // Only animate if it's a different page
            if (currentUrl !== targetUrl) {
                e.preventDefault();
                bar.style.width = '60%';

                const content = document.querySelector('.main-content');
                if (content) {
                    content.style.opacity = '0';
                    content.style.transform = 'translateY(-8px)';
                    content.style.transition = 'all 0.3s ease-in';
                }

                setTimeout(() => {
                    bar.style.width = '100%';
                    window.location.href = url;
                }, 250);
            }
        }
    });
}

// Force reload when navigated via browser back/forward cache (bfcache)
window.addEventListener('pageshow', function (event) {
    if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
        window.location.reload();
    }
});
