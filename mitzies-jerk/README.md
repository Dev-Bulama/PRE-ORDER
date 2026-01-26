# Mitzies Jerk - WordPress Food Pre-Order Plugin

A comprehensive WordPress plugin for food pre-ordering, designed specifically for restaurants, food trucks, and catering businesses. This plugin enables customers to browse menus, add items to cart, schedule pickups, and pay online.

## Features

### Core Features
- **Custom Post Types**: Food Items, Orders, and Categories
- **Pre-Order System**: Customers can schedule pickup date and time
- **Cart & Checkout**: Full shopping cart functionality with session management
- **Multiple Payment Gateways**: Paystack, Flutterwave, Stripe, and PayPal
- **Order Management**: Admin dashboard for managing orders and tracking
- **Email Notifications**: Automated emails for order confirmation, status updates, and reminders

### Admin Features
- Dashboard with analytics and statistics
- Settings page for configuration
- Order management with status updates
- Food item management with pricing, images, and options
- Business hours configuration
- Export orders to CSV

### Frontend Features
- Responsive food menu grid
- Category filtering
- Add to cart with AJAX
- Mini cart widget
- Full checkout with customer details
- Pre-order date/time picker
- Order history for logged-in users
- Order tracking

### Elementor Integration
- Food Menu widget
- Single Food Item widget
- Cart widget
- Checkout widget
- Food Categories widget
- Order History widget

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Elementor (optional, for widget support)

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"
5. Go to Mitzies Jerk > Settings to configure the plugin

## Configuration

### General Settings
- **Currency**: Set your currency (NGN, USD, GBP, EUR, etc.)
- **Currency Symbol**: Set the currency symbol
- **Business Name**: Your restaurant/business name
- **Business Email**: Email for receiving order notifications
- **Business Phone**: Contact phone number

### Pre-Order Settings
- **Minimum Pre-Order Hours**: Minimum hours in advance for pre-orders
- **Maximum Pre-Order Days**: How many days in advance customers can order
- **Business Hours**: Configure opening and closing times for each day

### Payment Settings
- **Paystack**: Enable and configure Paystack with public and secret keys
- **Flutterwave**: Enable and configure Flutterwave with public and secret keys
- **Stripe**: Enable and configure Stripe with publishable and secret keys
- **PayPal**: Enable and configure PayPal with client ID and secret

### Email Settings
- Configure email templates for:
  - Order confirmation
  - Order status updates
  - Pickup reminders
  - Admin notifications

## Shortcodes

### Food Menu
```
[mitzies_jerk_menu]
```
Parameters:
- `category`: Filter by category slug (comma-separated for multiple)
- `columns`: Number of columns (1-4, default: 3)
- `posts_per_page`: Items per page (default: 9)
- `show_filters`: Show category filters (true/false)
- `show_pagination`: Show pagination (true/false)
- `orderby`: Order by (date, title, menu_order, price)
- `order`: Order direction (ASC, DESC)

### Single Food Item
```
[mitzies_jerk_item id="123"]
```
Parameters:
- `id`: Food item post ID (required)
- `layout`: Layout style (card, horizontal, minimal)
- `show_image`: Show item image (true/false)
- `show_description`: Show description (true/false)
- `show_price`: Show price (true/false)
- `show_add_to_cart`: Show add to cart button (true/false)

### Cart
```
[mitzies_jerk_cart]
```
Parameters:
- `show_thumbnails`: Show item thumbnails (true/false)
- `show_quantity_controls`: Show +/- buttons (true/false)
- `empty_cart_text`: Custom empty cart message

### Checkout
```
[mitzies_jerk_checkout]
```
Parameters:
- `show_order_summary`: Show order summary (true/false)
- `show_coupon_field`: Show coupon field (true/false)

### Food Categories
```
[mitzies_jerk_categories]
```
Parameters:
- `layout`: Layout style (grid, list, horizontal)
- `columns`: Number of columns (2-6)
- `show_count`: Show item count (true/false)
- `show_image`: Show category image (true/false)
- `hide_empty`: Hide empty categories (true/false)

### Order History
```
[mitzies_jerk_order_history]
```
Parameters:
- `per_page`: Orders per page (default: 10)
- `show_status`: Show order status (true/false)
- `show_reorder_button`: Show reorder button (true/false)

### Order Tracking
```
[mitzies_jerk_order_tracking]
```

## REST API

The plugin provides REST API endpoints for external integrations:

### Endpoints

- `GET /wp-json/mitzies-jerk/v1/menu` - Get all food items
- `GET /wp-json/mitzies-jerk/v1/menu/{id}` - Get single food item
- `GET /wp-json/mitzies-jerk/v1/categories` - Get all categories
- `GET /wp-json/mitzies-jerk/v1/cart` - Get cart contents
- `POST /wp-json/mitzies-jerk/v1/cart/add` - Add item to cart
- `POST /wp-json/mitzies-jerk/v1/cart/update` - Update cart item
- `DELETE /wp-json/mitzies-jerk/v1/cart/remove` - Remove cart item
- `POST /wp-json/mitzies-jerk/v1/checkout` - Process checkout
- `GET /wp-json/mitzies-jerk/v1/orders/{id}` - Get order details
- `GET /wp-json/mitzies-jerk/v1/orders/track/{number}` - Track order

## Hooks & Filters

### Actions

```php
// Before order is created
do_action('mitzies_jerk_before_create_order', $order_data);

// After order is created
do_action('mitzies_jerk_after_create_order', $order_id, $order_data);

// When order status changes
do_action('mitzies_jerk_order_status_changed', $order_id, $old_status, $new_status);

// Before payment is processed
do_action('mitzies_jerk_before_process_payment', $order_id, $gateway);

// After successful payment
do_action('mitzies_jerk_payment_complete', $order_id, $transaction_id);
```

### Filters

```php
// Modify food item price
apply_filters('mitzies_jerk_food_item_price', $price, $item_id);

// Modify cart totals
apply_filters('mitzies_jerk_cart_totals', $totals);

// Modify available payment gateways
apply_filters('mitzies_jerk_payment_gateways', $gateways);

// Modify order email content
apply_filters('mitzies_jerk_order_email_content', $content, $order_id, $type);

// Modify available pickup times
apply_filters('mitzies_jerk_available_times', $times, $date);
```

## Support

For support, please contact:
- Email: support@skillscoreit.com
- Website: https://skillscoreit.com

## Credits

Developed by SkillScore IT Solutions and Training
Author: Tijani Bulama

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Food menu management
- Cart and checkout system
- Payment gateway integrations (Paystack, Flutterwave, Stripe, PayPal)
- Order management
- Email notifications
- Elementor widgets
- REST API
