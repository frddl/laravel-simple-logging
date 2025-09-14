// Log Viewer JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile drawer
    initMobileDrawer();
    
    // Initialize log interactions
    initLogInteractions();
    
    // Initialize data modal
    initDataModal();
    
    // Initialize search
    initSearch();
    
    // Initialize filters
    initFilters();
});

function initMobileDrawer() {
    const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const filtersSidebar = document.getElementById('filters-sidebar');

    if (mobileFilterToggle) {
        mobileFilterToggle.addEventListener('click', function() {
            filtersSidebar.classList.remove('-translate-x-full');
            mobileOverlay.classList.remove('hidden');
        });
    }

    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function() {
            filtersSidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', function() {
            filtersSidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
    }
}

function initLogInteractions() {
    // Log header click to expand/collapse
    document.addEventListener('click', function(e) {
        if (e.target.closest('.log-header')) {
            const logHeader = e.target.closest('.log-header');
            const requestId = logHeader.getAttribute('data-request-id');
            const logContent = document.getElementById('content-' + requestId);
            
            if (logContent) {
                logContent.classList.toggle('expanded');
            }
        }
    });

    // Tab switching
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('tab-button')) {
            const tabButton = e.target;
            const tabId = tabButton.getAttribute('data-tab');
            const requestId = tabId.split('-').slice(1).join('-');
            
            // Remove active class from all tabs in this request
            const tabContainer = tabButton.closest('.tabs-container');
            tabContainer.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            tabContainer.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active class to clicked tab
            tabButton.classList.add('active');
            const tabContent = document.getElementById(tabId);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        }
    });
}

function initDataModal() {
    const dataModal = document.getElementById('data-modal');
    const closeModal = document.getElementById('close-modal');
    const modalKey = document.getElementById('modal-key');
    const modalValue = document.getElementById('modal-value');

    // Close modal
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            dataModal.classList.add('hidden');
        });
    }

    // Close modal on overlay click
    if (dataModal) {
        dataModal.addEventListener('click', function(e) {
            if (e.target === dataModal) {
                dataModal.classList.add('hidden');
            }
        });
    }

    // Make data chips clickable
    document.addEventListener('click', function(e) {
        if (e.target.closest('.data-chip')) {
            const dataChip = e.target.closest('.data-chip');
            const key = dataChip.getAttribute('data-key');
            const value = dataChip.getAttribute('data-value');
            
            if (modalKey && modalValue) {
                modalKey.value = key;
                modalValue.querySelector('pre').textContent = JSON.stringify(JSON.parse(value), null, 2);
                dataModal.classList.remove('hidden');
            }
        }
    });
}

function initSearch() {
    const searchInput = document.getElementById('search-input');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const logCards = document.querySelectorAll('.log-card');
            
            logCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
}

function initFilters() {
    const clearFiltersBtn = document.getElementById('clear-filters');
    
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Clear all filter checkboxes
            document.querySelectorAll('.level-filter, .type-filter, .status-filter').forEach(checkbox => {
                checkbox.checked = true;
            });
            
            // Clear search
            const searchInput = document.getElementById('search-input');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Show all log cards
            document.querySelectorAll('.log-card').forEach(card => {
                card.style.display = 'block';
            });
        });
    }
}

// Global function for showing data values (called from PHP)
function showDataValue(key, value) {
    const dataModal = document.getElementById('data-modal');
    const modalKey = document.getElementById('modal-key');
    const modalValue = document.getElementById('modal-value');
    
    if (modalKey && modalValue) {
        modalKey.value = key;
        try {
            const parsedValue = JSON.parse(value);
            modalValue.querySelector('pre').textContent = JSON.stringify(parsedValue, null, 2);
        } catch (e) {
            modalValue.querySelector('pre').textContent = value;
        }
        dataModal.classList.remove('hidden');
    }
}
