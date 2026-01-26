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
?>

<div class="wrap mj-dashboard">
    <h1><?php esc_html_e( 'Mitzies Jerk Dashboard', 'mitzies-jerk' ); ?></h1>

    <div class="mj-dashboard-stats">
        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-cart"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $today_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Today\'s Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( mitzies_jerk_format_price( $today_revenue ?: 0 ) ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Today\'s Revenue', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $pending_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Pending Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-car"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $pending_deliveries ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Pending Deliveries', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( mitzies_jerk_format_price( $total_revenue ?: 0 ) ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Total Revenue', 'mitzies-jerk' ); ?></span>
            </div>
        </div>

        <div class="mj-stat-box">
            <div class="mj-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="mj-stat-content">
                <span class="mj-stat-value"><?php echo esc_html( $completed_orders ); ?></span>
                <span class="mj-stat-label"><?php esc_html_e( 'Completed Orders', 'mitzies-jerk' ); ?></span>
            </div>
        </div>
    </div>

    <div class="mj-dashboard-grid">
        <div class="mj-dashboard-main">
            <!-- Sales Chart -->
            <div class="mj-card">
                <h2><?php esc_html_e( 'Sales Overview (Last 7 Days)', 'mitzies-jerk' ); ?></h2>
                <canvas id="mj-sales-chart" height="100"></canvas>
            </div>

            <!-- Recent Orders -->
            <div class="mj-card">
                <h2><?php esc_html_e( 'Recent Orders', 'mitzies-jerk' ); ?></h2>
                <?php if ( ! empty( $recent_orders['orders'] ) ) : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Order', 'mitzies-jerk' ); ?></th>
                                <th><?php esc_html_e( 'Customer', 'mitzies-jerk' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'mitzies-jerk' ); ?></th>
                                <th><?php esc_html_e( 'Delivery', 'mitzies-jerk' ); ?></th>
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
                                        <a href="<?php echo esc_url( get_edit_post_link( $order->get_id() ) ); ?>">
                                            <strong>#<?php echo esc_html( $order->get( 'order_number' ) ); ?></strong>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( $billing['first_name'] . ' ' . $billing['last_name'] ); ?></td>
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
                                    <td><?php echo esc_html( mitzies_jerk_format_price( $order->get( 'total' ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="mj-view-all">
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mj_order' ) ); ?>" class="button">
                            <?php esc_html_e( 'View All Orders', 'mitzies-jerk' ); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p><?php esc_html_e( 'No orders yet.', 'mitzies-jerk' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mj-dashboard-sidebar">
            <!-- Quick Stats -->
            <div class="mj-card">
                <h2><?php esc_html_e( 'Inventory', 'mitzies-jerk' ); ?></h2>
                <ul class="mj-quick-stats">
                    <li>
                        <span class="mj-qs-label"><?php esc_html_e( 'Total Food Items', 'mitzies-jerk' ); ?></span>
                        <span class="mj-qs-value"><?php echo esc_html( $published_items ); ?></span>
                    </li>
                    <li>
                        <span class="mj-qs-label"><?php esc_html_e( 'Low Stock Items', 'mitzies-jerk' ); ?></span>
                        <span class="mj-qs-value <?php echo $low_stock_items > 0 ? 'warning' : ''; ?>">
                            <?php echo esc_html( $low_stock_items ); ?>
                        </span>
                    </li>
                </ul>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=mj_food_item' ) ); ?>" class="button">
                        <?php esc_html_e( 'Manage Food Items', 'mitzies-jerk' ); ?>
                    </a>
                </p>
            </div>

            <!-- Quick Links -->
            <div class="mj-card">
                <h2><?php esc_html_e( 'Quick Actions', 'mitzies-jerk' ); ?></h2>
                <ul class="mj-quick-links">
                    <li>
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=mj_food_item' ) ); ?>">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e( 'Add New Food Item', 'mitzies-jerk' ); ?>
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
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('mj-sales-chart').getContext('2d');
        var chartData = <?php echo wp_json_encode( $chart_data ); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.map(function(item) { return item.date; }),
                datasets: [{
                    label: '<?php esc_html_e( 'Sales', 'mitzies-jerk' ); ?>',
                    data: chartData.map(function(item) { return item.total; }),
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
