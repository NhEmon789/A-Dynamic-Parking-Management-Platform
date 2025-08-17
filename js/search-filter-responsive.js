// Hide filter button when hamburger menu is visible (responsive)
(function() {
    function updateSidebarToggleVisibility() {
        var sidebarToggle = document.getElementById('sidebarToggle');
        var hamburger = document.getElementById('hamburger');
        if (!sidebarToggle || !hamburger) return;
        // If hamburger is visible, show filter button; else hide
        var hamburgerVisible = window.getComputedStyle(hamburger).display !== 'none';
        sidebarToggle.style.display = hamburgerVisible ? 'flex' : 'none';
    }
    window.addEventListener('resize', updateSidebarToggleVisibility);
    document.addEventListener('DOMContentLoaded', updateSidebarToggleVisibility);
})();
