/**
 * Custom JS file to replace Bootstrap functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Dropdown toggle
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentNode;
            const dropdownMenu = parent.querySelector('.dropdown-menu');
            
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        dropdownToggles.forEach(toggle => {
            const parent = toggle.parentNode;
            const dropdownMenu = parent.querySelector('.dropdown-menu');
            
            if (dropdownMenu && dropdownMenu.classList.contains('show') && 
                !parent.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    });
    
    // Modal functionality - Enhanced for better compatibility
    const modalOpeners = document.querySelectorAll('[data-toggle="modal"]');
    
    modalOpeners.forEach(opener => {
        opener.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const modal = document.querySelector(targetId);
            
            if (modal) {
                // Show modal with animation
                modal.style.display = 'block';
                setTimeout(() => {
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                    
                    // Add modal backdrop
                    if (!document.querySelector('.modal-backdrop')) {
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }, 10);
                
                // Set focus on first input in modal
                setTimeout(() => {
                    const firstInput = modal.querySelector('input, select, textarea');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }, 200);
            }
        });
    });
    
    // Close modals with close button
    const modalClosers = document.querySelectorAll('[data-dismiss="modal"]');
    
    modalClosers.forEach(closer => {
        closer.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = this.closest('.modal');
            
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Close modals when clicking on backdrop
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
            closeModal(e.target);
        }
    });
    
    // Close modal when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                closeModal(modal);
            }
        }
    });
    
    // Helper function to close modals
    function closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Remove backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }, 150);
    }
    
    // Collapsible functionality
    const collapsibleToggles = document.querySelectorAll('[data-toggle="collapse"]');
    
    collapsibleToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const target = document.querySelector(targetId);
            
            if (target) {
                target.classList.toggle('show');
            }
        });
    });
}); 