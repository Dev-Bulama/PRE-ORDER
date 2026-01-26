<?php
/**
 * Checkout Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Checkout Widget
 */
class Mitzies_Jerk_Widget_Checkout extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_checkout';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Checkout', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-checkout';
    }

    /**
     * Get widget categories
     *
     * @return array
     */
    public function get_categories() {
        return array( 'mitzies-jerk' );
    }

    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return array( 'checkout', 'payment', 'order', 'preorder', 'mitzies' );
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
            'show_order_summary',
            array(
                'label'        => __( 'Show Order Summary', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_coupon_field',
            array(
                'label'        => __( 'Show Coupon Field', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'thank_you_page',
            array(
                'label'   => __( 'Thank You Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_pages(),
                'default' => '',
            )
        );

        $this->add_control(
            'cart_page',
            array(
                'label'   => __( 'Cart Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_pages(),
                'default' => '',
            )
        );

        $this->end_controls_section();

        // Pre-order Settings
        $this->start_controls_section(
            'preorder_section',
            array(
                'label' => __( 'Pre-order Settings', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'min_preorder_hours',
            array(
                'label'   => __( 'Minimum Pre-order Hours', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 2,
                'min'     => 0,
                'max'     => 72,
            )
        );

        $this->add_control(
            'max_preorder_days',
            array(
                'label'   => __( 'Maximum Pre-order Days', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 7,
                'min'     => 1,
                'max'     => 30,
            )
        );

        $this->end_controls_section();

        // Style Section - Form
        $this->start_controls_section(
            'style_form_section',
            array(
                'label' => __( 'Form', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'form_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mj-checkout-form' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'form_padding',
            array(
                'label'      => __( 'Padding', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-checkout-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'form_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-checkout-form',
            )
        );

        $this->add_control(
            'form_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-checkout-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Input Fields
        $this->start_controls_section(
            'style_input_section',
            array(
                'label' => __( 'Input Fields', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'input_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .mj-checkout-form input, {{WRAPPER}} .mj-checkout-form select, {{WRAPPER}} .mj-checkout-form textarea' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'input_text_color',
            array(
                'label'     => __( 'Text Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-checkout-form input, {{WRAPPER}} .mj-checkout-form select, {{WRAPPER}} .mj-checkout-form textarea' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'input_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-checkout-form input, {{WRAPPER}} .mj-checkout-form select, {{WRAPPER}} .mj-checkout-form textarea',
            )
        );

        $this->add_control(
            'input_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-checkout-form input, {{WRAPPER}} .mj-checkout-form select, {{WRAPPER}} .mj-checkout-form textarea' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Place Order Button
        $this->start_controls_section(
            'style_button_section',
            array(
                'label' => __( 'Place Order Button', 'mitzies-jerk' ),
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
                    '{{WRAPPER}} .mj-place-order-btn' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-place-order-btn' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-place-order-btn:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'label'    => __( 'Typography', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-place-order-btn',
            )
        );

        $this->add_control(
            'button_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-place-order-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Get pages list
     *
     * @return array
     */
    private function get_pages() {
        $pages = get_pages();
        $options = array( '' => __( 'Default', 'mitzies-jerk' ) );

        foreach ( $pages as $page ) {
            $options[ $page->ID ] = $page->post_title;
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $atts = array(
            'show_order_summary' => $settings['show_order_summary'] === 'yes',
            'show_coupon_field'  => $settings['show_coupon_field'] === 'yes',
            'thank_you_page'     => $settings['thank_you_page'],
            'cart_page'          => $settings['cart_page'],
            'min_preorder_hours' => $settings['min_preorder_hours'],
            'max_preorder_days'  => $settings['max_preorder_days'],
        );

        echo Mitzies_Jerk_Shortcodes::checkout( $atts );
    }
}
