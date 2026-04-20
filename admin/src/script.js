const mainContent = document.getElementById('main-content');

// Function to load content
function loadPage(page) {
    const pagePath = `./${page}`;
    
    fetch(pagePath)
        .then(response => {
            if (!response.ok) throw new Error("Page not found");
            return response.text();
        })
        .then(html => {
            mainContent.innerHTML = html;
            
            // Initialize based on which page is loaded
            if (page === 'dashboard.php') {
                initializeChart();
            }
            if (page === 'products.php') {
                initProductModal();
            }
            if (page === 'records.php') {
                initReceipts();
            }
            if (page === 'orders.php') {
                initOrderStatusHandlers();
            }
        })
        .catch(error => {
            mainContent.innerHTML = `<h1>Error</h1><p>${error.message}</p>`;
        });
}

// Function to initialize receipt modal functionality
function initReceipts() {
    const modal = document.getElementById('receiptModal');
    const closeBtn = modal?.querySelector('.close');
    
    if (!modal) return;
    
    // Function to populate and show the receipt modal
    function showReceipt(order) {
        // Populate basic order information
        document.getElementById('receipt-order-id').textContent = '#' + order.order_id;
        document.getElementById('receipt-customer').textContent = order.username;
        document.getElementById('receipt-date').textContent = new Date(order.order_date).toLocaleDateString();
        document.getElementById('receipt-status').textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        document.getElementById('receipt-payment').textContent = order.mop;
        document.getElementById('receipt-reference').textContent = order.reference_number || 'N/A';
        document.getElementById('receipt-total').textContent = '₱' + parseFloat(order.total).toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Populate products list
        const productsList = document.getElementById('receipt-products');
        productsList.innerHTML = ''; // Clear existing items
        
        order.items.forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `
                <div class="product-name">${item.product_name}</div>
                <div class="product-price">
                    ${item.quantity} x ₱${parseFloat(item.price).toFixed(2)} = ₱${parseFloat(item.total).toFixed(2)}
                </div>
            `;
            productsList.appendChild(li);
        });
        
        // Show the modal
        modal.style.display = 'block';
    }
    
    // Add event listeners to all "View Receipt" buttons
    document.querySelectorAll('.view-receipt-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            try {
                const orderData = JSON.parse(this.getAttribute('data-order'));
                showReceipt(orderData);
            } catch (error) {
                console.error('Error parsing order data:', error);
                alert('Error loading receipt data');
            }
        });
    });
    
    // Close modal when clicking the X
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    };
}

// Function to initialize the chart
function initializeChart() {
    try {
        // Get product distribution data from window object
        const productData = window.productDistribution || [];
        
        const labels = productData.map(item => item.product_name);
        const values = productData.map(item => item.total_quantity);

        const ctx = document.getElementById('productChart').getContext('2d');
        const productChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(199, 199, 199, 0.2)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Product Distribution'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.raw + ' units';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing chart:', error);
        const chartContainer = document.getElementById('productChart').parentElement;
        chartContainer.innerHTML = `<p class="text-danger">Error loading product distribution: ${error.message}</p>`;
    }
}

// Initialize the dashboard on page load
document.addEventListener('DOMContentLoaded', () => {
    // Set the dashboard link as active
    document.querySelector('.sidebar a[data-page="dashboard.php"]').classList.add('active');
    
    // Initialize the chart for the default dashboard view
    initializeChart();
});

// Add click listeners to sidebar links
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', event => {
        event.preventDefault();
        document.querySelectorAll('.sidebar a').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        const page = link.dataset.page;
        if (page) {
            loadPage(page);
        } else {
            console.error("No page specified in data-page attribute.");
        }
    });
});

// Function to open a modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error(`Modal with ID ${modalId} not found.`);
    }
}

// Function to close a modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Function to open the edit modal and populate form data
function openEditModal(product) {
    const editProductId = document.getElementById('editProductId');
    const editProductName = document.getElementById('editProductName');
    const editProductPrice = document.getElementById('editProductPrice');
    const editProductQuantity = document.getElementById('editProductQuantity');
    const editProductModal = document.getElementById('editProductModal');

    if (editProductId && editProductName && editProductPrice && editProductQuantity && editProductModal) {
        editProductId.value = product.id;
        editProductName.value = product.name;
        editProductPrice.value = product.price;
        editProductQuantity.value = product.quantity;
        editProductModal.style.display = 'block';
    } else {
        console.error("Edit modal elements not found in the DOM.");
    }
}

// Function to open the delete confirmation modal
function openDeleteModal(productId) {
    const deleteProductId = document.getElementById('deleteProductId');
    const deleteProductModal = document.getElementById('deleteProductModal');

    if (deleteProductId && deleteProductModal) {
        deleteProductId.value = productId;
        deleteProductModal.style.display = 'block';
    } else {
        console.error("Delete modal elements not found in the DOM.");
    }
}

// Initialize modals and event listeners
function initProductModal() {
    // Add event listeners to open modals
    const addProductBtn = document.querySelector('.header button');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', () => openModal('addProductModal'));
    }

    // Add event listeners to close modals
    const closeBtns = document.querySelectorAll('.modal .close');
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // Add event listeners to edit and delete buttons
    document.querySelectorAll('.edit').forEach(button => {
        button.addEventListener('click', () => {
            const product = {
                id: button.dataset.id,
                name: button.dataset.name,
                price: button.dataset.price,
                quantity: button.dataset.quantity
            };
            openEditModal(product);
        });
    });

    document.querySelectorAll('.delete').forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.dataset.id;
            openDeleteModal(productId);
        });
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
}

// Initialize modal when DOM is loaded
window.addEventListener('DOMContentLoaded', () => {
    initProductModal();
});

// Function to handle status changes
function handleStatusChange(orderId, newStatus) {
    const confirmationMessage = `Are you sure you want to change the status to ${newStatus}?`;

    if (confirm(confirmationMessage)) {
        updateOrderStatus(orderId, newStatus);
    } else {
        // Reset to previous status
        const dropdown = document.querySelector(`.status-dropdown[data-order-id="${orderId}"]`);
        if (dropdown) {
            dropdown.value = dropdown.dataset.previousStatus;
        }
    }
}

// Function to update order status
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus
            })
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Order status updated successfully!');
            // Update the previous status
            const dropdown = document.querySelector(`.status-dropdown[data-order-id="${orderId}"]`);
            if (dropdown) {
                dropdown.dataset.previousStatus = newStatus;
            }
        } else {
            alert('Failed to update order status');
            // Reset to previous status
            const dropdown = document.querySelector(`.status-dropdown[data-order-id="${orderId}"]`);
            if (dropdown) {
                dropdown.value = dropdown.dataset.previousStatus;
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating the order status');
    }
}

// Add event listeners for status dropdowns
function initOrderStatusHandlers() {
    document.querySelectorAll('.status-dropdown').forEach(dropdown => {
        // Store the initial status
        dropdown.dataset.previousStatus = dropdown.value;

        dropdown.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            handleStatusChange(orderId, newStatus);
        });
    });
    
    // Handle update status buttons
    document.querySelectorAll('.update-status-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const statusDropdown = document.querySelector(`.status-dropdown[data-order-id="${orderId}"]`);
            const newStatus = statusDropdown.value;

            if (confirm('Are you sure you want to update the status?')) {
                updateOrderStatus(orderId, newStatus);
            }
        });
    });
}