// Closes the search sidebar when clicking outside of it on small screens
(function() {
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('searchSidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        if (!sidebar) return;
        // Only run if sidebar is open and screen is small
        if (sidebar.classList.contains('active') && window.innerWidth <= 900) {
            // If click is outside sidebar and toggle button
            if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
                sidebar.classList.remove('active');
                setTimeout(() => { toggleBtn.style.display = 'flex'; }, 300);
            }
        }
    });
})();
