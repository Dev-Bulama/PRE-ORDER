<?php
/**
 * Documentation page.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap mj-admin-wrap mj-documentation">
    <!-- Header -->
    <div class="mj-admin-header">
        <div class="mj-header-left">
            <h1 class="mj-admin-title">
                <span class="dashicons dashicons-book"></span>
                <?php esc_html_e( 'Help & Documentation', 'mitzies-jerk' ); ?>
            </h1>
        </div>
    </div>

    <div class="mj-docs-grid">
        <!-- Shortcodes -->
        <div class="mj-card mj-docs-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-shortcode"></span> <?php esc_html_e( 'Shortcodes', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <p><?php esc_html_e( 'Use these shortcodes to display food menu, cart, and checkout on your pages.', 'mitzies-jerk' ); ?></p>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Food Menu', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_menu]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_menu]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays the food menu grid with all items.', 'mitzies-jerk' ); ?></p>
                    <details>
                        <summary><?php esc_html_e( 'Available Parameters', 'mitzies-jerk' ); ?></summary>
                        <ul class="mj-param-list">
                            <li><code>category=""</code> - <?php esc_html_e( 'Filter by category slug (comma-separated for multiple)', 'mitzies-jerk' ); ?></li>
                            <li><code>columns="3"</code> - <?php esc_html_e( 'Number of columns (1-4)', 'mitzies-jerk' ); ?></li>
                            <li><code>posts_per_page="9"</code> - <?php esc_html_e( 'Items per page', 'mitzies-jerk' ); ?></li>
                            <li><code>show_filters="true"</code> - <?php esc_html_e( 'Show category filters', 'mitzies-jerk' ); ?></li>
                            <li><code>show_pagination="true"</code> - <?php esc_html_e( 'Show pagination', 'mitzies-jerk' ); ?></li>
                            <li><code>orderby="date"</code> - <?php esc_html_e( 'Order by (date, title, menu_order, price)', 'mitzies-jerk' ); ?></li>
                            <li><code>order="ASC"</code> - <?php esc_html_e( 'Order direction (ASC, DESC)', 'mitzies-jerk' ); ?></li>
                        </ul>
                        <p class="mj-example"><strong><?php esc_html_e( 'Example:', 'mitzies-jerk' ); ?></strong> <code>[mitzies_jerk_menu category="main-dishes" columns="3" posts_per_page="12"]</code></p>
                    </details>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Single Food Item', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_item id="123"]</code>
                    <button class="button button-small mj-copy-btn" data-copy='[mitzies_jerk_item id="123"]'>
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays a single food item. Replace 123 with the food item ID.', 'mitzies-jerk' ); ?></p>
                    <details>
                        <summary><?php esc_html_e( 'Available Parameters', 'mitzies-jerk' ); ?></summary>
                        <ul class="mj-param-list">
                            <li><code>id=""</code> - <?php esc_html_e( 'Food item post ID (required)', 'mitzies-jerk' ); ?></li>
                            <li><code>layout="card"</code> - <?php esc_html_e( 'Layout style (card, horizontal, minimal)', 'mitzies-jerk' ); ?></li>
                            <li><code>show_image="true"</code> - <?php esc_html_e( 'Show item image', 'mitzies-jerk' ); ?></li>
                            <li><code>show_description="true"</code> - <?php esc_html_e( 'Show description', 'mitzies-jerk' ); ?></li>
                            <li><code>show_price="true"</code> - <?php esc_html_e( 'Show price', 'mitzies-jerk' ); ?></li>
                            <li><code>show_add_to_cart="true"</code> - <?php esc_html_e( 'Show add to cart button', 'mitzies-jerk' ); ?></li>
                        </ul>
                    </details>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Cart', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_cart]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_cart]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays the shopping cart page.', 'mitzies-jerk' ); ?></p>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Checkout', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_checkout]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_checkout]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays the checkout page with order form and payment options.', 'mitzies-jerk' ); ?></p>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Food Categories', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_categories]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_categories]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays food categories grid.', 'mitzies-jerk' ); ?></p>
                    <details>
                        <summary><?php esc_html_e( 'Available Parameters', 'mitzies-jerk' ); ?></summary>
                        <ul class="mj-param-list">
                            <li><code>layout="grid"</code> - <?php esc_html_e( 'Layout style (grid, list, horizontal)', 'mitzies-jerk' ); ?></li>
                            <li><code>columns="3"</code> - <?php esc_html_e( 'Number of columns (2-6)', 'mitzies-jerk' ); ?></li>
                            <li><code>show_count="true"</code> - <?php esc_html_e( 'Show item count', 'mitzies-jerk' ); ?></li>
                            <li><code>show_image="true"</code> - <?php esc_html_e( 'Show category image', 'mitzies-jerk' ); ?></li>
                            <li><code>hide_empty="true"</code> - <?php esc_html_e( 'Hide empty categories', 'mitzies-jerk' ); ?></li>
                        </ul>
                    </details>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Order History', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_order_history]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_order_history]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays order history for logged-in users.', 'mitzies-jerk' ); ?></p>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Order Tracking', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_order_tracking]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_order_tracking]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays order tracking form where customers can check their order status.', 'mitzies-jerk' ); ?></p>
                </div>

                <div class="mj-shortcode-item">
                    <h4><?php esc_html_e( 'Mini Cart', 'mitzies-jerk' ); ?></h4>
                    <code class="mj-shortcode-code">[mitzies_jerk_mini_cart]</code>
                    <button class="button button-small mj-copy-btn" data-copy="[mitzies_jerk_mini_cart]">
                        <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'mitzies-jerk' ); ?>
                    </button>
                    <p class="description"><?php esc_html_e( 'Displays a mini cart icon with dropdown. Great for headers.', 'mitzies-jerk' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Elementor Widgets -->
        <div class="mj-card mj-docs-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-welcome-widgets-menus"></span> <?php esc_html_e( 'Elementor Widgets', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <p><?php esc_html_e( 'If you have Elementor installed, you can use our widgets to build beautiful food ordering pages.', 'mitzies-jerk' ); ?></p>

                <?php if ( ! class_exists( '\Elementor\Plugin' ) ) : ?>
                <div class="mj-notice warning">
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e( 'Elementor is not installed. Install Elementor to use these widgets.', 'mitzies-jerk' ); ?>
                </div>
                <?php else : ?>
                <div class="mj-notice success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e( 'Elementor is active! You can find our widgets under the "Mitzies Jerk" category in the Elementor editor.', 'mitzies-jerk' ); ?>
                </div>
                <?php endif; ?>

                <h4><?php esc_html_e( 'Available Widgets:', 'mitzies-jerk' ); ?></h4>
                <ul class="mj-widget-list">
                    <li>
                        <span class="dashicons dashicons-food"></span>
                        <strong><?php esc_html_e( 'Food Menu', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Display food items in a customizable grid', 'mitzies-jerk' ); ?></span>
                    </li>
                    <li>
                        <span class="dashicons dashicons-carrot"></span>
                        <strong><?php esc_html_e( 'Single Food Item', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Feature a specific food item', 'mitzies-jerk' ); ?></span>
                    </li>
                    <li>
                        <span class="dashicons dashicons-cart"></span>
                        <strong><?php esc_html_e( 'Cart', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Shopping cart display', 'mitzies-jerk' ); ?></span>
                    </li>
                    <li>
                        <span class="dashicons dashicons-money-alt"></span>
                        <strong><?php esc_html_e( 'Checkout', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Complete checkout form', 'mitzies-jerk' ); ?></span>
                    </li>
                    <li>
                        <span class="dashicons dashicons-category"></span>
                        <strong><?php esc_html_e( 'Food Categories', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Display category listings', 'mitzies-jerk' ); ?></span>
                    </li>
                    <li>
                        <span class="dashicons dashicons-list-view"></span>
                        <strong><?php esc_html_e( 'Order History', 'mitzies-jerk' ); ?></strong>
                        <span class="mj-muted">- <?php esc_html_e( 'Customer order history', 'mitzies-jerk' ); ?></span>
                    </li>
                </ul>

                <h4><?php esc_html_e( 'How to Use:', 'mitzies-jerk' ); ?></h4>
                <ol>
                    <li><?php esc_html_e( 'Edit a page with Elementor', 'mitzies-jerk' ); ?></li>
                    <li><?php esc_html_e( 'Search for "Mitzies" in the widget panel', 'mitzies-jerk' ); ?></li>
                    <li><?php esc_html_e( 'Drag and drop the widget onto your page', 'mitzies-jerk' ); ?></li>
                    <li><?php esc_html_e( 'Customize settings in the widget panel', 'mitzies-jerk' ); ?></li>
                </ol>
            </div>
        </div>

        <!-- Quick Setup Guide -->
        <div class="mj-card mj-docs-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-welcome-learn-more"></span> <?php esc_html_e( 'Quick Setup Guide', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <div class="mj-setup-steps">
                    <div class="mj-setup-step">
                        <span class="mj-step-number">1</span>
                        <div class="mj-step-content">
                            <h4><?php esc_html_e( 'Add Food Items', 'mitzies-jerk' ); ?></h4>
                            <p><?php esc_html_e( 'Go to Mitzies Jerk > Add New Food to create your menu items. Add name, description, price, and images.', 'mitzies-jerk' ); ?></p>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mj_food_item' ) ); ?>" class="button button-small"><?php esc_html_e( 'Add Food Item', 'mitzies-jerk' ); ?></a>
                        </div>
                    </div>

                    <div class="mj-setup-step">
                        <span class="mj-step-number">2</span>
                        <div class="mj-step-content">
                            <h4><?php esc_html_e( 'Create Categories', 'mitzies-jerk' ); ?></h4>
                            <p><?php esc_html_e( 'Organize your menu with categories like "Main Dishes", "Sides", "Drinks", etc.', 'mitzies-jerk' ); ?></p>
                            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=mj_food_category&post_type=mj_food_item' ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage Categories', 'mitzies-jerk' ); ?></a>
                        </div>
                    </div>

                    <div class="mj-setup-step">
                        <span class="mj-step-number">3</span>
                        <div class="mj-step-content">
                            <h4><?php esc_html_e( 'Configure Settings', 'mitzies-jerk' ); ?></h4>
                            <p><?php esc_html_e( 'Set up your currency, business hours, pre-order settings, and payment gateways.', 'mitzies-jerk' ); ?></p>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-settings' ) ); ?>" class="button button-small"><?php esc_html_e( 'Settings', 'mitzies-jerk' ); ?></a>
                        </div>
                    </div>

                    <div class="mj-setup-step">
                        <span class="mj-step-number">4</span>
                        <div class="mj-step-content">
                            <h4><?php esc_html_e( 'Create Pages', 'mitzies-jerk' ); ?></h4>
                            <p><?php esc_html_e( 'Create pages for your menu, cart, and checkout using the shortcodes above.', 'mitzies-jerk' ); ?></p>
                            <div class="mj-page-suggestions">
                                <strong><?php esc_html_e( 'Suggested Pages:', 'mitzies-jerk' ); ?></strong>
                                <ul>
                                    <li><?php esc_html_e( 'Menu page', 'mitzies-jerk' ); ?> → <code>[mitzies_jerk_menu]</code></li>
                                    <li><?php esc_html_e( 'Cart page', 'mitzies-jerk' ); ?> → <code>[mitzies_jerk_cart]</code></li>
                                    <li><?php esc_html_e( 'Checkout page', 'mitzies-jerk' ); ?> → <code>[mitzies_jerk_checkout]</code></li>
                                    <li><?php esc_html_e( 'My Orders page', 'mitzies-jerk' ); ?> → <code>[mitzies_jerk_order_history]</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mj-setup-step">
                        <span class="mj-step-number">5</span>
                        <div class="mj-step-content">
                            <h4><?php esc_html_e( 'Set Up Payments', 'mitzies-jerk' ); ?></h4>
                            <p><?php esc_html_e( 'Configure payment gateways (Paystack, Flutterwave, Stripe, or PayPal) in Settings > Payments.', 'mitzies-jerk' ); ?></p>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-settings&tab=payment' ) ); ?>" class="button button-small"><?php esc_html_e( 'Payment Settings', 'mitzies-jerk' ); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="mj-card mj-docs-card">
            <div class="mj-card-header">
                <h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Support', 'mitzies-jerk' ); ?></h2>
            </div>
            <div class="mj-card-body">
                <p><?php esc_html_e( 'Need help? Here are some resources:', 'mitzies-jerk' ); ?></p>
                <ul class="mj-support-links">
                    <li>
                        <span class="dashicons dashicons-email"></span>
                        <strong><?php esc_html_e( 'Email Support:', 'mitzies-jerk' ); ?></strong>
                        <a href="mailto:support@skillscoreit.com">support@skillscoreit.com</a>
                    </li>
                    <li>
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <strong><?php esc_html_e( 'Website:', 'mitzies-jerk' ); ?></strong>
                        <a href="https://skillscoreit.com" target="_blank">skillscoreit.com</a>
                    </li>
                </ul>

                <h4><?php esc_html_e( 'Plugin Info', 'mitzies-jerk' ); ?></h4>
                <table class="mj-info-table">
                    <tr>
                        <td><?php esc_html_e( 'Version:', 'mitzies-jerk' ); ?></td>
                        <td><?php echo esc_html( MITZIES_JERK_VERSION ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'Author:', 'mitzies-jerk' ); ?></td>
                        <td>SkillScore IT Solutions - Tijani Bulama</td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'PHP Version:', 'mitzies-jerk' ); ?></td>
                        <td><?php echo esc_html( phpversion() ); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e( 'WordPress Version:', 'mitzies-jerk' ); ?></td>
                        <td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy to clipboard functionality
    document.querySelectorAll('.mj-copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var text = this.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(function() {
                var originalText = btn.innerHTML;
                btn.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Copied!', 'mitzies-jerk' ); ?>';
                setTimeout(function() {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        });
    });
});
</script>
