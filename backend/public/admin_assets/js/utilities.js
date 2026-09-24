/**
 * Admin Utilities & Feather Icon Initializer
 */
function initAdminFeather() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initAdminFeather();
});
