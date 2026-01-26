<?php
/**
 * Database helper class.
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes
 * @author     SkillScore IT Solutions and Training, Tijani Bulama
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database helper class.
 */
class Mitzies_Jerk_Database {

    /**
     * Get table name with prefix.
     *
     * @since    1.0.0
     * @param    string $table Table name without prefix.
     * @return   string Full table name.
     */
    public static function get_table( $table ) {
        global $wpdb;
        return $wpdb->prefix . MITZIES_JERK_TABLE_PREFIX . $table;
    }

    /**
     * Insert a row into a table.
     *
     * @since    1.0.0
     * @param    string $table  Table name without prefix.
     * @param    array  $data   Data to insert.
     * @param    array  $format Format for each value.
     * @return   int|false The number of rows inserted, or false on error.
     */
    public static function insert( $table, $data, $format = null ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $result = $wpdb->insert( $table_name, $data, $format );

        if ( false === $result ) {
            mitzies_jerk_log(
                sprintf( 'Database insert error: %s', $wpdb->last_error ),
                'error',
                array( 'table' => $table, 'data' => $data )
            );
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update rows in a table.
     *
     * @since    1.0.0
     * @param    string $table        Table name without prefix.
     * @param    array  $data         Data to update.
     * @param    array  $where        WHERE conditions.
     * @param    array  $format       Format for data values.
     * @param    array  $where_format Format for WHERE values.
     * @return   int|false The number of rows updated, or false on error.
     */
    public static function update( $table, $data, $where, $format = null, $where_format = null ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $result = $wpdb->update( $table_name, $data, $where, $format, $where_format );

        if ( false === $result ) {
            mitzies_jerk_log(
                sprintf( 'Database update error: %s', $wpdb->last_error ),
                'error',
                array( 'table' => $table, 'data' => $data, 'where' => $where )
            );
        }

        return $result;
    }

    /**
     * Delete rows from a table.
     *
     * @since    1.0.0
     * @param    string $table        Table name without prefix.
     * @param    array  $where        WHERE conditions.
     * @param    array  $where_format Format for WHERE values.
     * @return   int|false The number of rows deleted, or false on error.
     */
    public static function delete( $table, $where, $where_format = null ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $result = $wpdb->delete( $table_name, $where, $where_format );

        if ( false === $result ) {
            mitzies_jerk_log(
                sprintf( 'Database delete error: %s', $wpdb->last_error ),
                'error',
                array( 'table' => $table, 'where' => $where )
            );
        }

        return $result;
    }

    /**
     * Get a single row from a table.
     *
     * @since    1.0.0
     * @param    string $table  Table name without prefix.
     * @param    array  $where  WHERE conditions.
     * @param    string $output Output type (OBJECT, ARRAY_A, ARRAY_N).
     * @return   object|array|null Database row or null.
     */
    public static function get_row( $table, $where, $output = OBJECT ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $conditions = array();
        $values = array();

        foreach ( $where as $key => $value ) {
            $conditions[] = "`$key` = %s";
            $values[] = $value;
        }

        $where_clause = implode( ' AND ', $conditions );
        $query = $wpdb->prepare(
            "SELECT * FROM `$table_name` WHERE $where_clause LIMIT 1",
            $values
        );

        return $wpdb->get_row( $query, $output );
    }

    /**
     * Get multiple rows from a table.
     *
     * @since    1.0.0
     * @param    string $table   Table name without prefix.
     * @param    array  $args    Query arguments.
     * @param    string $output  Output type (OBJECT, ARRAY_A, ARRAY_N).
     * @return   array Database rows.
     */
    public static function get_results( $table, $args = array(), $output = OBJECT ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $defaults = array(
            'where'    => array(),
            'orderby'  => 'id',
            'order'    => 'DESC',
            'limit'    => -1,
            'offset'   => 0,
            'select'   => '*',
        );

        $args = wp_parse_args( $args, $defaults );

        $query = "SELECT {$args['select']} FROM `$table_name`";

        // Build WHERE clause.
        if ( ! empty( $args['where'] ) ) {
            $conditions = array();
            $values = array();

            foreach ( $args['where'] as $key => $value ) {
                if ( is_array( $value ) ) {
                    $placeholders = implode( ',', array_fill( 0, count( $value ), '%s' ) );
                    $conditions[] = "`$key` IN ($placeholders)";
                    $values = array_merge( $values, $value );
                } else {
                    $conditions[] = "`$key` = %s";
                    $values[] = $value;
                }
            }

            $where_clause = implode( ' AND ', $conditions );
            $query .= $wpdb->prepare( " WHERE $where_clause", $values );
        }

        // Order.
        $query .= " ORDER BY `{$args['orderby']}` {$args['order']}";

        // Limit.
        if ( $args['limit'] > 0 ) {
            $query .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
        }

        return $wpdb->get_results( $query, $output );
    }

    /**
     * Count rows in a table.
     *
     * @since    1.0.0
     * @param    string $table Table name without prefix.
     * @param    array  $where WHERE conditions.
     * @return   int Row count.
     */
    public static function count( $table, $where = array() ) {
        global $wpdb;
        $table_name = self::get_table( $table );

        $query = "SELECT COUNT(*) FROM `$table_name`";

        if ( ! empty( $where ) ) {
            $conditions = array();
            $values = array();

            foreach ( $where as $key => $value ) {
                $conditions[] = "`$key` = %s";
                $values[] = $value;
            }

            $where_clause = implode( ' AND ', $conditions );
            $query .= $wpdb->prepare( " WHERE $where_clause", $values );
        }

        return (int) $wpdb->get_var( $query );
    }

    /**
     * Get order items by order ID.
     *
     * @since    1.0.0
     * @param    int $order_id Order ID.
     * @return   array Order items.
     */
    public static function get_order_items( $order_id ) {
        return self::get_results( 'order_items', array(
            'where'   => array( 'order_id' => $order_id ),
            'orderby' => 'id',
            'order'   => 'ASC',
        ) );
    }

    /**
     * Save order item.
     *
     * @since    1.0.0
     * @param    array $item_data Item data.
     * @return   int|false Item ID or false.
     */
    public static function save_order_item( $item_data ) {
        return self::insert( 'order_items', $item_data, array( '%d', '%d', '%d', '%f', '%f', '%s', '%s' ) );
    }

    /**
     * Delete order items.
     *
     * @since    1.0.0
     * @param    int $order_id Order ID.
     * @return   int|false Number of deleted rows or false.
     */
    public static function delete_order_items( $order_id ) {
        return self::delete( 'order_items', array( 'order_id' => $order_id ), array( '%d' ) );
    }

    /**
     * Get coupon by code.
     *
     * @since    1.0.0
     * @param    string $code Coupon code.
     * @return   object|null Coupon data or null.
     */
    public static function get_coupon_by_code( $code ) {
        return self::get_row( 'coupons', array( 'code' => strtoupper( $code ) ) );
    }

    /**
     * Increment coupon usage.
     *
     * @since    1.0.0
     * @param    int $coupon_id Coupon ID.
     * @return   int|false Number of updated rows or false.
     */
    public static function increment_coupon_usage( $coupon_id ) {
        global $wpdb;
        $table_name = self::get_table( 'coupons' );

        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE `$table_name` SET usage_count = usage_count + 1 WHERE id = %d",
                $coupon_id
            )
        );
    }

    /**
     * Log payment transaction.
     *
     * @since    1.0.0
     * @param    array $log_data Log data.
     * @return   int|false Log ID or false.
     */
    public static function log_payment( $log_data ) {
        return self::insert( 'payment_logs', $log_data );
    }

    /**
     * Get payment logs for an order.
     *
     * @since    1.0.0
     * @param    int $order_id Order ID.
     * @return   array Payment logs.
     */
    public static function get_payment_logs( $order_id ) {
        return self::get_results( 'payment_logs', array(
            'where'   => array( 'order_id' => $order_id ),
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ) );
    }

    /**
     * Save review.
     *
     * @since    1.0.0
     * @param    array $review_data Review data.
     * @return   int|false Review ID or false.
     */
    public static function save_review( $review_data ) {
        return self::insert( 'reviews', $review_data );
    }

    /**
     * Get reviews for a food item.
     *
     * @since    1.0.0
     * @param    int    $food_item_id Food item ID.
     * @param    string $status       Review status.
     * @return   array Reviews.
     */
    public static function get_reviews( $food_item_id, $status = 'approved' ) {
        return self::get_results( 'reviews', array(
            'where'   => array(
                'food_item_id' => $food_item_id,
                'status'       => $status,
            ),
            'orderby' => 'created_at',
            'order'   => 'DESC',
        ) );
    }

    /**
     * Get average rating for a food item.
     *
     * @since    1.0.0
     * @param    int $food_item_id Food item ID.
     * @return   float Average rating.
     */
    public static function get_average_rating( $food_item_id ) {
        global $wpdb;
        $table_name = self::get_table( 'reviews' );

        $avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(rating) FROM `$table_name` WHERE food_item_id = %d AND status = 'approved'",
                $food_item_id
            )
        );

        return $avg ? round( (float) $avg, 1 ) : 0;
    }

    /**
     * Get addons for a food item.
     *
     * @since    1.0.0
     * @param    int $food_item_id Food item ID.
     * @return   array Addons.
     */
    public static function get_food_addons( $food_item_id ) {
        return self::get_results( 'addons', array(
            'where'   => array(
                'food_item_id' => $food_item_id,
                'status'       => 'active',
            ),
            'orderby' => 'sort_order',
            'order'   => 'ASC',
        ) );
    }

    /**
     * Get delivery zone by postcode or city.
     *
     * @since    1.0.0
     * @param    string $value Postcode or city name.
     * @param    string $type  Zone type (postcode, city, region).
     * @return   object|null Delivery zone or null.
     */
    public static function get_delivery_zone( $value, $type = 'postcode' ) {
        global $wpdb;
        $table_name = self::get_table( 'delivery_zones' );

        $zones = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `$table_name` WHERE zone_type = %s AND status = 'active'",
                $type
            )
        );

        foreach ( $zones as $zone ) {
            $zone_values = maybe_unserialize( $zone->zone_values );
            if ( is_array( $zone_values ) && in_array( $value, $zone_values, true ) ) {
                return $zone;
            }
        }

        return null;
    }
}
