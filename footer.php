    <footer class="footer">
        <div class="container">
            <div class="text-center p-3">
                &copy; <?php echo date("Y"); ?> Mess Management System | All Rights Reserved
            </div>
        </div>
    </footer>

    <script>
        // Custom JavaScript to replace Bootstrap functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Close alert messages when clicking the close button
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const closeBtn = alert.querySelector('.close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        alert.style.display = 'none';
                    });
                }
                
                // Auto-hide alerts after 5 seconds
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html> 

// End output buffering
ob_end_flush();
?>