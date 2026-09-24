/**
 * MLM Book Admin - Theme Mode Toggle JS
 */
document.addEventListener('DOMContentLoaded', function () {
    const modeBtn = document.querySelector('.mode');
    if (modeBtn) {
        modeBtn.addEventListener('click', function () {
            document.body.classList.toggle('dark-theme');
            const isDark = document.body.classList.contains('dark-theme');
            localStorage.setItem('mlm-admin-theme', isDark ? 'dark' : 'light');
            
            // Update icon
            const icon = modeBtn.querySelector('i');
            if (icon) {
                icon.setAttribute('data-feather', isDark ? 'sun' : 'moon');
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        });

        // Restore saved theme
        if (localStorage.getItem('mlm-admin-theme') === 'dark') {
            document.body.classList.add('dark-theme');
            const icon = modeBtn.querySelector('i');
            if (icon) {
                icon.setAttribute('data-feather', 'sun');
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }
        }
    }
});
