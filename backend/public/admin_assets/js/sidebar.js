/**
 * MLM Book Admin - Navigation, Mobile Drawer, Desktop Collapse & Dropdowns
 */
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar-wrapper') || document.getElementById('adminSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const backdrop = document.getElementById('sidebarBackdrop');
    const pageWrapper = document.getElementById('pageWrapper') || document.querySelector('.page-wrapper');

    function isDesktop() {
        return window.innerWidth >= 992;
    }

    // Restore desktop preference on load if set
    if (isDesktop()) {
        try {
            if (localStorage.getItem('mlm_admin_sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
                if (pageWrapper) pageWrapper.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    }

    function toggleDesktopSidebar() {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        if (pageWrapper) {
            pageWrapper.classList.toggle('sidebar-collapsed', isCollapsed);
        }
        try {
            localStorage.setItem('mlm_admin_sidebar_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
    }

    function openMobileSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    function handleToggle(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (isDesktop()) {
            toggleDesktopSidebar();
        } else {
            if (sidebar && sidebar.classList.contains('open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        }
    }

    // Main Hamburger Toggle click listener
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', handleToggle);
    }

    // Mobile Close Button click listener
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeMobileSidebar();
        });
    }

    // Mobile Backdrop click listener
    if (backdrop) {
        backdrop.addEventListener('click', function (e) {
            e.preventDefault();
            closeMobileSidebar();
        });
    }

    // Escape key listener for mobile drawer
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            if (!isDesktop() && sidebar && sidebar.classList.contains('open')) {
                closeMobileSidebar();
            }
        }
    });

    // Window resize handler: auto clean up mobile state when switching to desktop
    let resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
            if (isDesktop()) {
                closeMobileSidebar();
            }
        }, 100);
    });

    // Submenu accordion toggle
    const sidebarTitles = document.querySelectorAll('.sidebar-title');
    sidebarTitles.forEach(function (title) {
        title.addEventListener('click', function (e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            if (submenu && submenu.classList.contains('sidebar-submenu')) {
                const isOpen = submenu.classList.contains('show');

                // Close other open submenus at the same level
                document.querySelectorAll('.sidebar-submenu.show').forEach(function (openMenu) {
                    if (openMenu !== submenu) {
                        openMenu.classList.remove('show');
                        if (openMenu.previousElementSibling) {
                            openMenu.previousElementSibling.classList.remove('active');
                        }
                    }
                });

                if (isOpen) {
                    submenu.classList.remove('show');
                    this.classList.remove('active');
                } else {
                    submenu.classList.add('show');
                    this.classList.add('active');
                }
            }
        });
    });

    // Header Dropdowns (Notifications & Profile)
    const notifToggle = document.getElementById('notificationToggle');
    const notifDropdown = document.getElementById('notificationDropdown');
    const profileToggle = document.getElementById('profileDropdownToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    if (notifToggle && notifDropdown) {
        notifToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (profileDropdown) profileDropdown.classList.remove('show');
            notifDropdown.classList.toggle('show');
        });
    }

    if (profileToggle && profileDropdown) {
        profileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (notifDropdown) notifDropdown.classList.remove('show');
            profileDropdown.classList.toggle('show');
        });
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function (e) {
        if (notifDropdown && !notifDropdown.contains(e.target) && notifToggle && !notifToggle.contains(e.target)) {
            notifDropdown.classList.remove('show');
        }
        if (profileDropdown && !profileDropdown.contains(e.target) && profileToggle && !profileToggle.contains(e.target)) {
            profileDropdown.classList.remove('show');
        }
    });

    // Auto-expand active submenus on page load
    const activeSubmenuLink = document.querySelector('.sidebar-submenu a.active');
    if (activeSubmenuLink) {
        const parentSubmenu = activeSubmenuLink.closest('.sidebar-submenu');
        if (parentSubmenu) {
            parentSubmenu.classList.add('show');
            if (parentSubmenu.previousElementSibling) {
                parentSubmenu.previousElementSibling.classList.add('active');
            }
        }
    }

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
