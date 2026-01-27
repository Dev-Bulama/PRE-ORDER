<?php
/**
 * Admin dashboard page.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle demo data creation.
if ( isset( $_POST['mj_create_demo_data'] ) && wp_verify_nonce( $_POST['mj_demo_nonce'], 'mj_create_demo_data' ) ) {
    mitzies_jerk_create_demo_data();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Demo data created successfully!', 'mitzies-jerk' ) . '</p></div>';
}

// Get statistics.
global $wpdb;

// Orders stats.
$total_orders = wp_count_posts( 'mj_order' );
$pending_orders = isset( $total_orders->{'mj-pending'} ) ? $total_orders->{'mj-pending'} : 0;
$completed_orders = isset( $total_orders->{'mj-completed'} ) ? $total_orders->{'mj-completed'} : 0;

// Today's orders.
$today_start = date( 'Y-m-d 00:00:00' );
$today_end = date( 'Y-m-d 23:59:59' );

$today_orders = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mj_order' AND post_date >= %s AND post_date <= %s",
        $today_start,
        $today_end
    )
);

// Total revenue.
$total_revenue = $wpdb->get_var(
    "SELECT SUM(meta_value) FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'mj_order'
    AND p.post_status = 'mj-completed'
    AND pm.meta_key = '_mj_total'"
);

// Today's revenue.
$today_revenue = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(pm.meta_value) FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.post_type = 'mj_order'
        AND p.post_date >= %s AND p.post_date <= %s
        AND pm.meta_key = '_mj_total'",
        $today_start,
        $today_end
    )
);

// Pending deliveries.
$pending_deliveries = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mj_order' AND post_status IN ('mj-paid', 'mj-processing', 'mj-preparing', 'mj-ready')"
);

// Food items count.
$food_items_count = wp_count_posts( 'mj_food_item' );
$published_items = isset( $food_items_count->publish ) ? $food_items_count->publish : 0;

// Low stock items.
$low_stock_items = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'mj_food_item'
    AND p.post_status = 'publish'
    AND pm.meta_key = '_mj_stock_quantity'
    AND CAST(pm.meta_value AS SIGNED) <= 5
    AND CAST(pm.meta_value AS SIGNED) > 0"
);

// Recent orders.
$recent_orders = Mitzies_Jerk_Order::get_orders( array( 'posts_per_page' => 10 ) );

// Get chart data (last 7 days).
$chart_data = array();
for ( $i = 6; $i >= 0; $i-- ) {
    $date = date( 'Y-m-d', strtotime( "-$i days" ) );
    $day_total = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COALESCE(SUM(pm.meta_value), 0) FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'mj_order'
            AND DATE(p.post_date) = %s
            AND pm.meta_key = '_mj_total'",
            $date
        )
    );
    $chart_data[] = array(
        'date'  => date( 'M j', strtotime( $date ) ),
        'total' => floatval( $day_total ),
    );
}

$settings = get_option( 'mitzies_jerk_settings', array() );
$currency_symbol = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
?>

<div class="wrap mj-admin-wrap mj-dashboard">
    <!-- Header -->
    <div class="mj-admin-header">
        <div class="mj-header-left">
            <h1 class="mj-admin-title">
                <span class="dashicons dashicons-carrot"></span>
                <?php esc_html_e( 'Mitzies Jerk', 'mitzies-jerk' ); ?>
            </h1>
            <span class="mj-version">v<?php echo esc_html( MITZIES_JERK_VERSION ); ?></span>
        </div>
        <div class="mj-header-actions">
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mj_food_item' ) ); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e( 'Add Food Item', 'mitzies-jerk' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-settings' ) ); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Settings', 'mitzies-jerk' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-documentation' ) ); ?>" class="button">
                <span class="dashicons dashicons-book"></span>
                <?php esc_html_e( 'Help & Docs', 'mitzies-jerk' ); ?>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="mj-stats-grid">
        <div class="mj-stat-card">
            <div class="mj-stat-icon blue">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $today_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Today\'s Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-card">
            <div class="mj-stat-icon green">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $currency_symbol . number_format( $today_revenue ?: 0, 2 ) ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Today\'s Revenue', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-card">
            <div class="mj-stat-icon orange">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $pending_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Pending Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-card">
            <div class="mj-stat-icon purple">
                <span class="dashicons dashicons-car"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $pending_deliveries ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Pending Deliveries', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-card">
            <div class="mj-stat-icon teal">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $currency_symbol . number_format( $total_revenue ?: 0, 2 ) ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Total Revenue', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-card">
            <div class="mj-stat-icon red">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $completed_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Completed Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="mj-dashboard-grid">
        <!-- Main Column -->
        <div class="mj-dashboard-main">
            <!-- Sales Chart -->
            <div class="mj-card">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Sales Overview (Last 7 Days)', 'mitzies-jerk' ); ?></h2>
                </div>
                <div class="mj-card-body">
                    <canvas id="mj-sales-chart" height="80"></canvas>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="mj-card">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Recent Orders', 'mitzies-jerk' ); ?></h2>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mj_order' ) ); ?>" class="button button-small">
                        <?php esc_html_e( 'View All', 'mitzies-jerk' ); ?>
                    </a>
                </div>
                <div class="mj-card-body">
                    <?php if ( ! empty( $recent_orders['orders'] ) ) : ?>
                        <table class="mj-table widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Order', 'mitzies-jerk' ); ?></th>
                                    <th><?php esc_html_e( 'Customer', 'mitzies-jerk' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                                    <th><?php esc_html_e( 'Pickup Time', 'mitzies-jerk' ); ?></th>
                                    <th><?php esc_html_e( 'Total', 'mitzies-jerk' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $recent_orders['orders'] as $order ) :
                                    $billing = $order->get( 'billing' );
                                    $status = $order->get( 'status' );
                                    $statuses = mitzies_jerk_get_order_statuses();
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo esc_url( get_edit_post_link( $order->get_id() ) ); ?>" class="mj-order-link">
                                                <strong>#<?php echo esc_html( $order->get( 'order_number' ) ); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ( $billing ) : ?>
                                                <?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?>
                                                <br><small class="mj-muted"><?php echo esc_html( $billing['email'] ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="mj-status-badge status-<?php echo esc_attr( $status ); ?>">
                                                <?php echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $delivery = $order->get( 'delivery_datetime' );
                                            echo $delivery ? esc_html( date_i18n( 'M j, g:i a', strtotime( $delivery ) ) ) : '-';
                                            ?>
                                        </td>
                                        <td><strong><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="mj-empty-state">
                            <span class="dashicons dashicons-clipboard"></span>
                            <p><?php esc_html_e( 'No orders yet. Orders will appear here once customers start placing them.', 'mitzies-jerk' ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="mj-dashboard-sidebar">
            <!-- Getting Started -->
            <?php if ( $published_items === 0 ) : ?>
            <div class="mj-card mj-getting-started">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Getting Started', 'mitzies-jerk' ); ?></h2>
                </div>
                <div class="mj-card-body">
                    <p><?php esc_html_e( 'Welcome! Get started by adding food items or loading demo data.', 'mitzies-jerk' ); ?></p>

                    <form method="post" class="mj-demo-form">
                        <?php wp_nonce_field( 'mj_create_demo_data', 'mj_demo_nonce' ); ?>
                        <button type="submit" name="mj_create_demo_data" class="button button-primary button-hero">
                            <span class="dashicons dashicons-database-add"></span>
                            <?php esc_html_e( 'Load Demo Data (5 Items)', 'mitzies-jerk' ); ?>
                        </button>
                    </form>

                    <p class="mj-or-separator"><?php esc_html_e( '- OR -', 'mitzies-jerk' ); ?></p>

                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mj_food_item' ) ); ?>" class="button button-secondary button-hero">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e( 'Add Your First Food Item', 'mitzies-jerk' ); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Inventory -->
            <div class="mj-card">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Inventory', 'mitzies-jerk' ); ?></h2>
                </div>
                <div class="mj-card-body">
                    <ul class="mj-inventory-list">
                        <li>
                            <span class="mj-inv-label"><?php esc_html_e( 'Total Food Items', 'mitzies-jerk' ); ?></span>
                            <span class="mj-inv-value"><?php echo esc_html( $published_items ); ?></span>
                        </li>
                        <li>
                            <span class="mj-inv-label"><?php esc_html_e( 'Low Stock Items', 'mitzies-jerk' ); ?></span>
                            <span class="mj-inv-value <?php echo $low_stock_items > 0 ? 'warning' : ''; ?>">
                                <?php echo esc_html( $low_stock_items ); ?>
                            </span>
                        </li>
                    </ul>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mj_food_item' ) ); ?>" class="button button-block">
                        <?php esc_html_e( 'Manage Food Items', 'mitzies-jerk' ); ?>
                    </a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mj-card">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Quick Actions', 'mitzies-jerk' ); ?></h2>
                </div>
                <div class="mj-card-body">
                    <ul class="mj-quick-actions">
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mj_food_item' ) ); ?>">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e( 'Add New Food Item', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=mj_food_category&post_type=mj_food_item' ) ); ?>">
                                <span class="dashicons dashicons-category"></span>
                                <?php esc_html_e( 'Manage Categories', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-coupons' ) ); ?>">
                                <span class="dashicons dashicons-tickets-alt"></span>
                                <?php esc_html_e( 'Manage Coupons', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-reports' ) ); ?>">
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php esc_html_e( 'View Reports', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-settings' ) ); ?>">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e( 'Settings', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=mj-documentation' ) ); ?>">
                                <span class="dashicons dashicons-book"></span>
                                <?php esc_html_e( 'Help & Documentation', 'mitzies-jerk' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Demo Data -->
            <?php if ( $published_items > 0 ) : ?>
            <div class="mj-card mj-demo-card">
                <div class="mj-card-header">
                    <h2><?php esc_html_e( 'Demo Data', 'mitzies-jerk' ); ?></h2>
                </div>
                <div class="mj-card-body">
                    <p class="description"><?php esc_html_e( 'Need more sample data? Click below to add 5 more demo food items.', 'mitzies-jerk' ); ?></p>
                    <form method="post">
                        <?php wp_nonce_field( 'mj_create_demo_data', 'mj_demo_nonce' ); ?>
                        <button type="submit" name="mj_create_demo_data" class="button">
                            <span class="dashicons dashicons-database-add"></span>
                            <?php esc_html_e( 'Add Demo Data', 'mitzies-jerk' ); ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('mj-sales-chart');
        if (ctx) {
            var chartData = <?php echo wp_json_encode( $chart_data ); ?>;

            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.map(function(item) { return item.date; }),
                    datasets: [{
                        label: '<?php esc_html_e( 'Sales', 'mitzies-jerk' ); ?>',
                        data: chartData.map(function(item) { return item.total; }),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>
