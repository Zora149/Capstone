# EYC TRADING - E-Commerce Platform

EYC TRADING is a professional, high-fidelity e-commerce web application designed for efficient product management and online sales. Built with PHP and MySQL, it offers a seamless shopping experience with real-time stock tracking and an intuitive user interface.

## 🚀 Features

- **Product Management**: Dynamic product catalog with real-time stock status (In Stock, Low Stock, Out of Stock).
- **Shopping Cart**: Fully functional cart system with AJAX-powered updates.
- **Direct Purchase**: "Buy Now" functionality for quick transactions.
- **Order Tracking**: Comprehensive order fetching and history management.
- **Responsive Design**: Modern, glassmorphism-inspired UI that works perfectly on all devices.
- **Admin Dashboard**: Secure backend for managing products, images, and orders.
- **Store Location**: Integrated Google Maps for easy store navigation.

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL (using PDO for secure transactions)
- **Frontend**: HTML5, CSS3 (Vanilla CSS), JavaScript (ES6+)
- **Icons**: FontAwesome 6.7.2
- **Maps**: Google Maps API Integration

## 📋 Prerequisites

- PHP 8.x
- MySQL Server
- XAMPP / WAMP / MAMP (for local development)

## 🔧 Installation & Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/Zora149/Capstone.git
   ```

2. **Database Setup**:
   - Create a new MySQL database (e.g., `u490599583_chickensale`).
   - Import the database schema (look for `.sql` files in the project or create tables based on `db_connect.php`).
   - Configure the database connection in `connection/db_connect.php`:
     ```php
     $db_config = [
         'host' => 'localhost',
         'dbname' => 'your_db_name',
         'username' => 'your_username',
         'password' => 'your_password',
         'charset' => 'utf8mb4'
     ];
     ```

3. **Apache Configuration**:
   - Place the project folder in your web root (`htdocs` for XAMPP).
   - Ensure you have write permissions for the `upload_images` directory.

4. **Launch**:
   - Start Apache and MySQL in your control panel.
   - Access the site via `http://localhost/Captsone`.

## 📁 Project Structure

```text
Captsone/
├── admin/            # Backend administration tools
├── assets/           # Images and static assets
├── components/        # Reusable PHP components (header, footer, etc.)
├── connection/       # Database connection logic
├── css/              # Stylesheets
├── upload_images/    # User-uploaded product images
├── cart.php          # Shopping cart logic
├── index.php         # Main landing page
└── transaction.php    # Checkout and transaction processing
```

## 🤝 Contributing

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---
*Developed by [Zora149](https://github.com/Zora149)*
