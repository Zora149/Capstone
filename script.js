// Select the hamburger menu and menu content
const hamburgerMenu = document.querySelector('.hamburger-menu');
const menuContent = document.querySelector('.menu-content');

// Add a click event listener to toggle the 'active' class
if (hamburgerMenu && menuContent) {
hamburgerMenu.addEventListener('click', () => {
    menuContent.classList.toggle('active');
});
}

// Initialize cart count
let cartCount = 0;

// Add to cart function
async function addToCart(productId) {
    try {
        const response = await fetch('connection/add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ product_id: productId })
        });

        const data = await response.json();
        
        if (data.success) {
            updateCartCount(data.cart_count);
            updatePaymentButtons();
            
            // Process the returned orders if needed
            console.log('Updated orders:', data.orders);
        } else {
            if (data.redirect) {
                // User not logged in, redirect to login page
                alert('Please login first to add items to your cart');
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Failed to add to cart');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while adding to cart');
    }
}

// Update cart count in header
function updateCartCount(count) {
    const cartCountElement = document.getElementById("cart-count");
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Add this function to fetch cart data
async function fetchCartData() {
    try {
        const response = await fetch('connection/get_orders.php');
        const data = await response.json();
        
        if (data.success) {
            // Update cart count in header
            updateCartCount(data.total_count);
        } else {
            console.log('No cart data found or user not logged in');
        }
    } catch (error) {
        console.error('Error fetching cart data:', error);
    }
}

// Call fetchCartData when any page loads
document.addEventListener('DOMContentLoaded', () => {
    fetchCartData();
    updatePaymentButtons();
    
    // Existing event listeners for add to cart buttons
    const addToCartButtons = document.querySelectorAll(".add-to-cart-btn");
addToCartButtons.forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault();
            const productId = button.dataset.id;
            addToCart(productId);
        });
    });

    // Add event listener for Pay Now button
    const payNowButton = document.querySelector('.pay-now');
    if (payNowButton) {
        payNowButton.addEventListener('click', (e) => {
            e.preventDefault();
            showGCashModal();
        });
    }

    // Add event listener for Pay Later button
    const payLaterButton = document.querySelector('.pay-later');
    if (payLaterButton) {
        payLaterButton.addEventListener('click', (e) => {
            e.preventDefault();
            showPayLaterModal();
            handlePayLater();
        });
    }

    // Close modal when clicking outside of it
    window.addEventListener('click', (event) => {
        const modal = document.getElementById('gcashModal');
        if (event.target === modal) {
            closeModal();
        }
    });

    // Add event listener for Order Now button
    const orderNowButton = document.getElementById('orderNowButton');
    if (orderNowButton) {
        orderNowButton.addEventListener('click', async (e) => {
            e.preventDefault();
            await transferCartToOrders();
        });
    }

    // Add event listener for GCash Payment Done button
    const gcashDoneButton = document.querySelector('#gcashModal .done-btn');
    if (gcashDoneButton) {
        gcashDoneButton.addEventListener('click', async (e) => {
            e.preventDefault();
            await processGCashPayment();
        });
    }
});

// Cart Production
function updateTotalPrice(row) {
    if (!row) return;

    const priceElement = row.querySelector(".price");
    const quantityElement = row.querySelector(".quantity p");
    const totalPriceElement = row.querySelector(".total-price");

    if (!priceElement || !quantityElement || !totalPriceElement) return;

    const price = parseFloat(priceElement.textContent.replace("₱", "")) || 0;
    const quantity = parseInt(quantityElement.textContent) || 0;
    const totalPrice = price * quantity;

    if (totalPriceElement) {
    totalPriceElement.textContent = `₱${totalPrice.toFixed(2)}`;
    }
    updateCartTotal();
    updateSummary(row, quantity, totalPrice);
}

function updateSummary(row, quantity, totalPrice) {
    if (!row) return;

    const productNameElement = row.querySelector("td:nth-child(2) p");
    const summaryContainer = document.querySelector(".total-summary");

    if (!productNameElement || !summaryContainer) return;

    const productName = productNameElement.textContent;

    // Find the existing summary for this product
    const existingSummary = Array.from(summaryContainer.children).find((child) => 
        child.querySelector("span")?.textContent.includes(productName)
    );

    if (existingSummary) {
        // Update existing summary
        existingSummary.innerHTML = `
            <span>${productName} (${quantity}x)</span>
            <span class="cart-Price">₱${totalPrice.toFixed(2)}</span>
        `;
    } else {
        // Add new summary
        const summaryDiv = document.createElement("div");
        summaryDiv.innerHTML = `
            <span>${productName} (${quantity}x)</span>
            <span class="cart-Price">₱${totalPrice.toFixed(2)}</span>
        `;
        const totalElement = summaryContainer.querySelector(".total");
        if (totalElement) {
            summaryContainer.insertBefore(summaryDiv, totalElement);
        }
    }
}

function updateCartPrice() {
    const priceElements = document.querySelectorAll(".cart-Price");
    let totalPrice = 0;

    priceElements.forEach((priceElement) => {
        const price = parseFloat(priceElement.textContent.replace("₱", "")) || 0;
        totalPrice += price;
    });

    console.log(`Total Price: ₱${totalPrice.toFixed(2)}`);
    return totalPrice;
}

function updateCartTotal() {
    const totalSummaryElement = document.querySelector(".total-summary .total span:last-child");
    const totalPrices = document.querySelectorAll(".total-price");
    let total = 0;

    if (!totalSummaryElement) return;

    totalPrices.forEach((totalPriceElement) => {
        const price = parseFloat(totalPriceElement.textContent.replace("₱", "")) || 0;
        total += price;
    });

    totalSummaryElement.textContent = `₱${total.toFixed(2)}`;
}

// Function to handle quantity changes
async function updateQuantity(orderId, change) {
    try {
        const response = await fetch('connection/update_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ order_id: orderId, change: change })
        });

        const data = await response.json();
        
        if (data.success) {
            updatePaymentButtons();
            window.location.reload();
        } else {
            alert(data.message || 'Failed to update quantity');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating quantity');
    }
}

// Function to handle item removal
async function removeFromCart(orderId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        try {
            const response = await fetch('connection/remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: orderId })
            });

            const data = await response.json();
            
            if (data.success) {
                updatePaymentButtons();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to remove item');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while removing item');
        }
    }
}

// Update the quantity functions to use the new updateQuantity function
function increaseQuantity(button, orderId) {
    updateQuantity(orderId, 1);
}

function decreaseQuantity(button, orderId) {
    updateQuantity(orderId, -1);
}

// Add this code to handle the GCash modal
function showGCashModal() {
    const modal = document.getElementById('gcashModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal() {
    const modal = document.getElementById('gcashModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Add this function to handle Pay Later
function showPayLaterModal() {
    const modal = document.getElementById('payLaterModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closePayLaterModal() {
    const modal = document.getElementById('payLaterModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Update the updatePaymentButtons function
function updatePaymentButtons() {
    const payNowButton = document.querySelector('.pay-now');
    const payLaterButton = document.querySelector('.pay-later');
    const cartItems = document.querySelectorAll('tbody tr');
    
    // Check if cart is empty
    const isEmpty = cartItems.length === 0 || 
                   (cartItems.length === 1 && 
                    cartItems[0].querySelector('td').colSpan === 5);
    
    if (payNowButton) payNowButton.disabled = isEmpty;
    if (payLaterButton) payLaterButton.disabled = isEmpty;
}

// Function to generate a 6-digit random reference number
function generateReferenceNumber() {
    const prefix = '#';
    const randomNumber = Math.floor(100000 + Math.random() * 900000); // Generates a 6-digit number
    return `${prefix}${randomNumber}`;
}

// Function to handle Pay Later button click
function handlePayLater() {
    // Generate reference number
    const referenceNumber = generateReferenceNumber();
    
    // Display the generated reference number in the modal
    document.getElementById('referenceNumber').textContent = referenceNumber;

    // Show the Pay Later modal
    showPayLaterModal();
}

// Function to transfer cart items to orders table (Pay Later)
async function transferCartToOrders() {
    try {
        const referenceNumber = document.getElementById('referenceNumber').textContent;
        const mop = 'Pay Later'; 
        const deliveryDateElement = document.getElementById('deliveryDate');
        const contactNumberElement = document.getElementById('contactNumber');
        
        const deliveryDate = deliveryDateElement ? deliveryDateElement.value : null;
        const contactNumber = contactNumberElement ? contactNumberElement.value : null;

        // Validate delivery date
        if (!deliveryDate) {
            alert('Please select a delivery date and time');
            return;
        }

        // Validate contact number
        if (!contactNumber || contactNumber.length !== 11) {
            alert('Please enter a valid 11-digit contact number');
            return;
        }

        const response = await fetch('connection/transfer_to_orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reference_number: referenceNumber,
                mop: mop,
                order_date: new Date().toISOString(), 
                delivery_date: deliveryDate ? new Date(deliveryDate).toISOString() : null,
                contact_number: contactNumber // Include contact number
            })
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Order placed successfully!');
            closePayLaterModal();
            window.location.reload();
        } else {
            alert(`Failed to place order: ${data.message || 'Unknown error'}`);
            console.error('Error details:', data);
        }
    } catch (error) {
        console.error('Error:', error);
        alert(`An error occurred while placing order: ${error.message}`);
    }
}

// Function to process GCash payment
async function processGCashPayment() {
    try {
        const gcashRefElement = document.getElementById('gcashRef');
        const deliveryDateElement = document.getElementById('deliveryDate');
        const contactNumberElement = document.getElementById('contactNumber');
        
        const gcashRef = gcashRefElement ? gcashRefElement.value : null;
        const mop = 'Gcash'; 
        const deliveryDate = deliveryDateElement ? deliveryDateElement.value : null;
        const contactNumber = contactNumberElement ? contactNumberElement.value : null;
        
        if (!gcashRef) {
            alert('Please enter your GCash reference number');
            return;
        }

        // Validate delivery date
        if (!deliveryDate) {
            alert('Please select a delivery date and time');
            return;
        }

        // Validate contact number
        if (!contactNumber || contactNumber.length !== 11) {
            alert('Please enter a valid 11-digit contact number');
            return;
        }

        const response = await fetch('connection/transfer_to_orders.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                reference_number: gcashRef,
                mop: mop,
                order_date: new Date().toISOString(),
                delivery_date: deliveryDate ? new Date(deliveryDate).toISOString() : null,
                contact_number: contactNumber // Include contact number
            })
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Payment successful! Thank you for your purchase.');
            closeModal();
            window.location.reload();
        } else {
            alert(`Failed to process payment: ${data.message || 'Unknown error'}`);
            console.error('Error details:', data);
        }
    } catch (error) {
        console.error('Error:', error);
        alert(`An error occurred while processing payment: ${error.message}`);
    }
}

function processOrder() {
    console.log("processOrder() called");
    
    // Get reference number if applicable
    const referenceNumber = document.getElementById('gcashRef') ? 
        document.getElementById('gcashRef').value : '';
    
    // Determine payment method
    const mop = document.getElementById('gcashModal').style.display === 'block' ? 'Gcash' : 'Pay Later';
    
    // Get delivery schedule if delivery option is available
    let deliveryDate = null;
    let deliveryTime = null;
    const dateInput = document.getElementById('deliveryDate');
    const timeInput = document.getElementById('deliveryTime');
    
    if (dateInput && timeInput) {
        deliveryDate = dateInput.value;
        deliveryTime = timeInput.value;
        
        if (!deliveryDate || !deliveryTime) {
            alert("⚠ Please select a delivery date and time before proceeding.");
            return;
        }
    }

    console.log("Payment method:", mop, "Reference:", referenceNumber);
    console.log("Delivery schedule:", deliveryDate, deliveryTime);
    
    // Send request to process order
    fetch('connection/process_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            reference_number: referenceNumber,
            mop: mop,
            delivery_date: deliveryDate,
            delivery_time: deliveryTime
        })
    })
    .then(response => {
        console.log("Response status:", response.status);
        console.log("Response headers:", response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log("Raw response:", text);
        
        try {
            const data = JSON.parse(text);
            console.log("Parsed JSON:", data);
            
            if (data.success) {
                console.log("Order successful!");
                
                closeModal();
                closePayLaterModal();
                
                const modal = new bootstrap.Modal(document.getElementById('successModal'));
                modal.show();
            } else {
                console.error("Order failed:", data.message);
                alert('Error: ' + data.message);
            }
        } catch (parseError) {
            console.error("Failed to parse JSON:", parseError);
            console.error("Raw text was:", text);
            alert("Server returned invalid response. Check console for details.");
        }
    })
    .catch(error => {
        console.error("Fetch error:", error);
        alert("Something went wrong. Please try again.");
    });
}


// Helper function to close GCash modal
function closeModal() {
    const modal = document.getElementById('gcashModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Helper function to close Pay Later modal
function closePayLaterModal() {
    const modal = document.getElementById('payLaterModal');
    if (modal) {
        modal.style.display = 'none';
    }
}