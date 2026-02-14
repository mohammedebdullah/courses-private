/**
 * Payment Due Notifications System
 * Loads and displays real-time payment due notifications in header
 */

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPaymentNotifications();
    
    // Refresh notifications every 5 minutes
    setInterval(loadPaymentNotifications, 5 * 60 * 1000);
});

/**
 * Load payment notifications from backend
 */
function loadPaymentNotifications() {
    fetch('backend/notifications/get_payment_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayNotifications(data.notifications, data.count);
            } else {
                console.error('Failed to load notifications:', data.message);
                displayEmptyNotifications();
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            displayEmptyNotifications();
        });
}

/**
 * Display notifications in the dropdown
 */
function displayNotifications(notifications, count) {
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (!notificationList) return;
    
    // Update badge count
    if (count > 0) {
        notificationBadge.textContent = count;
        notificationBadge.style.display = 'inline-block';
    } else {
        notificationBadge.style.display = 'none';
    }
    
    // Clear loading spinner
    notificationList.innerHTML = '';
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="text-center py-4">
                <i class="ti ti-check-circle text-success" style="font-size: 48px;"></i>
                <p class="text-muted mt-2">هیچ قەرزەکێ ئەڤرۆ نینە!</p>
            </div>
        `;
        return;
    }
    
    // Build notification list
    const ul = document.createElement('ul');
    ul.className = 'notification-list';
    
    notifications.forEach(notification => {
        const li = document.createElement('li');
        li.className = 'notification-message';
        
        const timeClass = notification.days_overdue > 0 ? 'text-danger' : 'text-warning';
        const urgencyIcon = notification.days_overdue > 0 ? 'ti-alert-circle' : 'ti-clock-hour-4';
        
        li.innerHTML = `
            <a href="${notification.link}">
                <div class="media d-flex">
                    <span class="avatar flex-shrink-0 bg-soft-warning d-flex align-items-center justify-content-center">
                        <i class="ti ${urgencyIcon} text-warning fs-20"></i>
                    </span>
                    <div class="flex-grow-1">
                        <p class="noti-details">
                            <span class="noti-title fw-bold">${escapeHtml(notification.customer_name)}</span> 
                            قەرزا پارەیێ هەیە <span class="text-danger fw-bold">${notification.amount_due} IQD</span>
                            <br>
                            <small class="text-muted">پسولە: ${notification.invoice_number} | ناڤبەر ${notification.payment_cycle}</small>
                        </p>
                        <p class="noti-time ${timeClass}">
                            <i class="ti ti-calendar me-1"></i>${notification.time_ago} - ${notification.due_date}
                        </p>
                    </div>
                </div>
            </a>
        `;
        
        ul.appendChild(li);
    });
    
    notificationList.appendChild(ul);
}

/**
 * Display empty notifications state
 */
function displayEmptyNotifications() {
    const notificationList = document.getElementById('notificationList');
    const notificationBadge = document.getElementById('notificationBadge');
    
    if (!notificationList) return;
    
    notificationBadge.style.display = 'none';
    
    notificationList.innerHTML = `
        <div class="text-center py-4">
            <i class="ti ti-info-circle text-muted" style="font-size: 48px;"></i>
            <p class="text-muted mt-2">نەشێت ئاگەهدارکرن بار بکەت</p>
        </div>
    `;
}

/**
 * Refresh notifications manually
 */
function refreshNotifications() {
    const notificationList = document.getElementById('notificationList');
    if (notificationList) {
        notificationList.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">بارکرن...</span>
                </div>
            </div>
        `;
    }
    loadPaymentNotifications();
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
