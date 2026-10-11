            </div> <!-- /.container-fluid -->
        </main> <!-- /.admin-content -->
        
    </div> <!-- /.admin-main -->
</div> <!-- /.admin-layout -->

<!-- Bootstrap 5 Bundle JS -->
<script src="<?= buildUrl('assets/js/bootstrap.bundle.min.js') ?>"></script>

<script>
    // Simple script to toggle sidebar on mobile
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>

</body>
</html>
