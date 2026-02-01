<?php
/**
 * Order History Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Order History Widget
 */
class Mitzies_Jerk_Widget_Order_History extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_order_history';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Order History', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-history';
    }

    /**
     * Get widget categories
     *
     * @return array
     */
    public function get_categories() {
        return array( 'mitzies-jerk', 'general' );
    }

    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return array( 'order', 'history', 'account', 'preorder', 'mitzies' );
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'per_page',
            array(
                'label'   => __( 'Orders Per Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min'     => 1,
                'max'     => 50,
            )
        );

        $this->add_control(
            'show_status',
            array(
                'label'        => __( 'Show Order Status', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_date',
            array(
                'label'        => __( 'Show Order Date', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_pickup_time',
            array(
                'label'        => __( 'Show Pickup Time', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_total',
            array(
                'label'        => __( 'Show Order Total', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_items',
            array(
                'label'        => __( 'Show Order Items', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_reorder_button',
            array(
                'label'        => __( 'Show Reorder Button', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'login_message',
            array(
                'label'   => __( 'Login Required Message', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Please log in to view your order history.', 'mitzies-jerk' ),
            )
        );

        $this->add_control(
            'no_orders_message',
            array(
                'label'   => __( 'No Orders Message', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'You have not placed any orders yet.', 'mitzies-jerk' ),
            )
        );

        $this->end_controls_section();

        // Style Section - Table
        $this->start_controls_section(
            'style_table_section',
            array(
                'label' => __( 'Orders Table', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'table_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mj-order-history-table' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'table_header_background',
            array(
                'label'     => __( 'Header Background', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .mj-order-history-table thead' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'table_row_hover',
            array(
                'label'     => __( 'Row Hover Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f1f3f4',
                'selectors' => array(
                    '{{WRAPPER}} .mj-order-history-table tbody tr:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'table_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-order-history-table',
            )
        );

        $this->add_control(
            'table_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-order-history-table' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Typography
        $this->start_controls_section(
            'style_typography_section',
            array(
                'label' => __( 'Typography', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'table_typography',
                'label'    => __( 'Table Typography', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-order-history-table',
            )
        );

        $this->add_control(
            'text_color',
            array(
                'label'     => __( 'Text Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-order-history-table' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Status Badges
        $this->start_controls_section(
            'style_status_section',
            array(
                'label'     => __( 'Status Badges', 'mitzies-jerk' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_status' => 'yes',
                ),
            )
        );

        $this->add_control(
            'status_pending_color',
            array(
                'label'     => __( 'Pending Status Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f39c12',
                'selectors' => array(
                    '{{WRAPPER}} .mj-status-pending' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'status_confirmed_color',
            array(
                'label'     => __( 'Confirmed Status Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#3498db',
                'selectors' => array(
                    '{{WRAPPER}} .mj-status-confirmed' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'status_ready_color',
            array(
                'label'     => __( 'Ready Status Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#9b59b6',
                'selectors' => array(
                    '{{WRAPPER}} .mj-status-ready' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'status_completed_color',
            array(
                'label'     => __( 'Completed Status Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#27ae60',
                'selectors' => array(
                    '{{WRAPPER}} .mj-status-completed' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'status_cancelled_color',
            array(
                'label'     => __( 'Cancelled Status Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e74c3c',
                'selectors' => array(
                    '{{WRAPPER}} .mj-status-cancelled' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'status_border_radius',
            array(
                'label'      => __( 'Badge Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 4,
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-order-status' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Buttons
        $this->start_controls_section(
            'style_button_section',
            array(
                'label' => __( 'Buttons', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'button_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e74c3c',
                'selectors' => array(
                    '{{WRAPPER}} .mj-reorder-btn' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_text_color',
            array(
                'label'     => __( 'Text Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mj-reorder-btn' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_hover_background',
            array(
                'label'     => __( 'Hover Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#c0392b',
                'selectors' => array(
                    '{{WRAPPER}} .mj-reorder-btn:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-reorder-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $atts = array(
            'per_page'            => $settings['per_page'],
            'show_status'         => $settings['show_status'] === 'yes',
            'show_date'           => $settings['show_date'] === 'yes',
            'show_pickup_time'    => $settings['show_pickup_time'] === 'yes',
            'show_total'          => $settings['show_total'] === 'yes',
            'show_items'          => $settings['show_items'] === 'yes',
            'show_reorder_button' => $settings['show_reorder_button'] === 'yes',
            'login_message'       => $settings['login_message'],
            'no_orders_message'   => $settings['no_orders_message'],
        );

        echo Mitzies_Jerk_Shortcodes::order_history( $atts );
    }
}
