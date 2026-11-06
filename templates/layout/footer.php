<?php if (Auth::check()): ?>
        </main>
    </div>
</div>

<script>
function toggleSidebar() {
    const app = document.querySelector('.rh-app');
    const sidebar = document.getElementById('rhSidebar');
    const backdrop = document.getElementById('rhSidebarBackdrop');
    
    if (window.innerWidth < 768) {
        if (sidebar && backdrop) {
            sidebar.classList.toggle('is-open');
            backdrop.classList.toggle('is-open');
        }
    } else {
        if (app) {
            app.classList.toggle('is-collapsed');
            const isCollapsed = app.classList.contains('is-collapsed');
            localStorage.setItem('rh_sidebar_collapsed', isCollapsed ? '1' : '0');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const backdrop = document.getElementById('rhSidebarBackdrop');
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            const sidebar = document.getElementById('rhSidebar');
            if (sidebar) sidebar.classList.remove('is-open');
            backdrop.classList.remove('is-open');
        });
    }

    const themeBtn = document.getElementById('rhThemeToggleBtn');
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            console.log('Switch theme clicked');
        });
    }
});
</script>
<?php else: ?>
    </main>
<?php endif; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

