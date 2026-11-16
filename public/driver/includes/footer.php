        <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Close navigation" tabindex="-1"></button>
    </div><!-- /.dashboard-wrapper -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');

            function setSidebarOpen(isOpen) {
                sidebar.classList.toggle('open', isOpen);
                document.body.classList.toggle('lr-sidebar-open', isOpen);
                menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                menuToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            }

            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    setSidebarOpen(!sidebar.classList.contains('open'));
                });

                document.addEventListener('click', function(e) {
                    if (!sidebar.contains(e.target) && sidebar.classList.contains('open')) {
                        setSidebarOpen(false);
                    }
                });

                if (sidebarBackdrop) {
                    sidebarBackdrop.addEventListener('click', function() {
                        setSidebarOpen(false);
                    });
                }

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        setSidebarOpen(false);
                        menuToggle.focus();
                    }
                });

                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
                        setSidebarOpen(false);
                    }
                });
            }
        });
    </script>
    <script src="../assets/js/lr-sidebar-scroll.js"></script>
    <script src="../assets/js/lr-notifications.js"></script>
</body>
</html>
