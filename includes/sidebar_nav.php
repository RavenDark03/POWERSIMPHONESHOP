<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../images/powersim logo.png" alt="Powersim" class="sidebar-logo-img">
            <span class="sidebar-title">Powersim</span>
        </div>
        <button class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="index.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
        </li>
         <li class="sidebar-item">
            <a href="appointments.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'appointments.php') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span class="sidebar-text">Appointments</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="customers.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'customers.php' || basename($_SERVER['PHP_SELF']) === 'view_customer.php' || basename($_SERVER['PHP_SELF']) === 'edit_customer.php' || basename($_SERVER['PHP_SELF']) === 'add_customer.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="sidebar-text">Customers</span>
            </a>
        </li>
        
       
        <li class="sidebar-item">
            <a href="pawning.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'pawning.php' || basename($_SERVER['PHP_SELF']) === 'new_pawn.php' || basename($_SERVER['PHP_SELF']) === 'view_pawn.php') ? 'active' : ''; ?>">
                <i class="fas fa-ring"></i>
                <span class="sidebar-text">Pawning</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="pawn_processing.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'pawn_processing.php' || basename($_SERVER['PHP_SELF']) === 'redeem_pawn.php' || basename($_SERVER['PHP_SELF']) === 'renew_pawn.php') ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i>
                <span class="sidebar-text">Pawn Processing</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="appraisal_queue.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'appraisal_queue.php') ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check"></i>
                <span class="sidebar-text">Appraisal Queue</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="inventory.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'inventory.php') ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-text">Inventory</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="auctioned.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'auctioned.php') ? 'active' : ''; ?>">
                <i class="fas fa-gavel"></i>
                <span class="sidebar-text">Auctioned Items</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="archived.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'archived.php') ? 'active' : ''; ?>">
                <i class="fas fa-box-archive"></i>
                <span class="sidebar-text">Archived</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="reports.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span class="sidebar-text">Reports</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="customer_accounts.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'customer_accounts.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span class="sidebar-text">Customer Accounts</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="users.php" class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'users.php' || basename($_SERVER['PHP_SELF']) === 'view_user.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span class="sidebar-text">Employee Accounts</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="../logout.php" class="sidebar-logout" onclick="return confirm('Are you sure you want to logout?');">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sidebar-text">Logout</span>
        </a>
    </div>
</nav>

<script>
// Manage the sidebar toggle button dynamically so it can be removed from the DOM
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content-wrapper');

    function createToggle() {
        const btn = document.createElement('button');
        btn.className = 'sidebar-toggle-btn';
        btn.id = 'sidebarToggle';
        btn.title = 'Toggle Sidebar';
        btn.innerHTML = '<i class="fas fa-chevron-left"></i>';

        attachToggleListener(btn);

        return btn;
    }

    function attachToggleListener(btn) {
        // Ensure we don't attach duplicate listeners
        btn.removeEventListener && btn.removeEventListener('click', toggleHandler);
        btn.addEventListener('click', toggleHandler);
    }

    function toggleHandler(e) {
        e && e.stopPropagation();
        sidebar.classList.toggle('collapsed');
        if (mainContent) mainContent.classList.toggle('expanded');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));

        // If collapsed after click, remove the toggle from DOM
        if (sidebar.classList.contains('collapsed')) {
            removeToggle();
        }
    }

    function removeToggle() {
        const existing = document.getElementById('sidebarToggle');
        if (existing) existing.remove();
    }

    function ensureTogglePresent() {
        const existing = document.getElementById('sidebarToggle');
        if (!existing) {
            const header = sidebar.querySelector('.sidebar-header');
            const btn = createToggle();
            header.appendChild(btn);
        } else {
            // If the static button was in the HTML, attach our listener to it
            attachToggleListener(existing);
        }
    }

    // Initialize based on saved state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
        // keep toggle out of DOM when collapsed
        removeToggle();
    } else {
        ensureTogglePresent();
    }

    // Show toggle only while hovering a collapsed sidebar
    sidebar.addEventListener('mouseenter', function() {
        if (sidebar.classList.contains('collapsed')) {
            ensureTogglePresent();
        }
    });

    sidebar.addEventListener('mouseleave', function() {
        if (sidebar.classList.contains('collapsed')) {
            removeToggle();
        }
    });
});
</script>
