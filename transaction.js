// Function to load orders for a specific status
async function loadOrders(status) {
    try {
        const response = await fetch(`fetch_orders.php?status=${status}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            console.error('Server error:', data.error);
            return;
        }

        const tableBody = document.querySelector(`#${status} tbody`);
        if (!tableBody) {
            console.error(`Table body not found for status: ${status}`);
            return;
        }
        
        tableBody.innerHTML = data.map(order => `
            <tr>
                <td class="product-cell">
                    <div>
                        <p class="product-name">${order.product_name}</p>
                        ${order.status !== 'completed' && order.status !== 'cancelled' ? 
                            `<a href="#" class="cancel-link" data-order-id="${order.order_id}">Cancel</a>` : ''}
                    </div>
                </td>
                <td>${order.mop}</td>
                <td>${order.status.charAt(0).toUpperCase() + order.status.slice(1)}</td>
                <td>₱${parseFloat(order.total).toFixed(2)}</td>
            </tr>
        `).join('');
        
        // Re-add event listeners for cancel links
        tableBody.querySelectorAll('.cancel-link').forEach(link => {
            link.addEventListener('click', handleCancelOrder);
        });
    } catch (error) {
        console.error('Error loading orders:', error);
    }
}

// Tab switching logic
const tabButtons = document.querySelectorAll('.tab-button');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        // Remove "active" class from all tab buttons
        tabButtons.forEach((b) => b.classList.remove('active'));
        // Add "active" class to the clicked tab button
        btn.classList.add('active');

        // Hide all tab contents
        tabContents.forEach((content) => content.classList.remove('active'));

        // Show the selected tab content
        const tabId = btn.getAttribute('data-tab');
        const tabContent = document.getElementById(tabId);
        tabContent.classList.add('active');

        // Load orders for the selected tab
        if (tabId !== 'all') {
            loadOrders(tabId);
        }
    });
});

// Function to handle cancel order
async function handleCancelOrder(e) {
    e.preventDefault();
    const orderId = e.target.dataset.orderId;
    if (confirm('Are you sure you want to cancel this order?')) {
        try {
            const response = await fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ orderId })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Failed to cancel order: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            alert('An error occurred while cancelling the order: ' + error.message);
        }
    }
}

// Initial load for the "All" tab
document.addEventListener('DOMContentLoaded', () => {
    // The "All" tab is already populated with PHP
    // Add event listeners for cancel links
    document.querySelectorAll('.cancel-link').forEach(link => {
        link.addEventListener('click', handleCancelOrder);
    });
});
