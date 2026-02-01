<?php
/**
 * Cart Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cart Widget
 */
class Mitzies_Jerk_Widget_Cart extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_cart';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Cart', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-cart';
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
        return array( 'cart', 'basket', 'shopping', 'order', 'mitzies' );
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
            'show_thumbnails',
            array(
                'label'        => __( 'Show Thumbnails', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_quantity_controls',
            array(
                'label'        => __( 'Show Quantity Controls', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'checkout_page',
            array(
                'label'   => __( 'Checkout Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_pages(),
                'default' => '',
            )
        );

        $this->add_control(
            'continue_shopping_page',
            array(
                'label'   => __( 'Continue Shopping Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_pages(),
                'default' => '',
            )
        );

        $this->add_control(
            'empty_cart_text',
            array(
                'label'   => __( 'Empty Cart Text', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Your cart is empty', 'mitzies-jerk' ),
            )
        );

        $this->end_controls_section();

        // Style Section - Table
        $this->start_controls_section(
            'style_table_section',
            array(
                'label' => __( 'Cart Table', 'mitzies-jerk' ),
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
                    '{{WRAPPER}} .mj-cart-table' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-cart-table thead' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'table_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-cart-table',
            )
        );

        $this->add_control(
            'table_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-cart-table' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Totals
        $this->start_controls_section(
            'style_totals_section',
            array(
                'label' => __( 'Cart Totals', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'totals_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .mj-cart-totals' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'totals_typography',
                'label'    => __( 'Typography', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-cart-totals',
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
            'checkout_button_background',
            array(
                'label'     => __( 'Checkout Button Background', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e74c3c',
                'selectors' => array(
                    '{{WRAPPER}} .mj-checkout-btn' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'checkout_button_text_color',
            array(
                'label'     => __( 'Checkout Button Text', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mj-checkout-btn' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'show_thumbnails'        => $settings['show_thumbnails'] === 'yes',
            'show_quantity_controls' => $settings['show_quantity_controls'] === 'yes',
            'checkout_page'          => $settings['checkout_page'],
            'continue_shopping_page' => $settings['continue_shopping_page'],
            'empty_cart_text'        => $settings['empty_cart_text'],
        );

        echo Mitzies_Jerk_Shortcodes::cart( $atts );
    }
}
