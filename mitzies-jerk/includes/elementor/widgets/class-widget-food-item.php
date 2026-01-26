<?php
/**
 * Single Food Item Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Food Item Widget
 */
class Mitzies_Jerk_Widget_Food_Item extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_food_item';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Single Food Item', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-image-box';
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
        return array( 'food', 'item', 'product', 'single', 'mitzies' );
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
            'food_item_id',
            array(
                'label'   => __( 'Select Food Item', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_food_items(),
                'default' => '',
            )
        );

        $this->add_control(
            'layout',
            array(
                'label'   => __( 'Layout', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'card',
                'options' => array(
                    'card'       => __( 'Card', 'mitzies-jerk' ),
                    'horizontal' => __( 'Horizontal', 'mitzies-jerk' ),
                    'minimal'    => __( 'Minimal', 'mitzies-jerk' ),
                ),
            )
        );

        $this->add_control(
            'show_image',
            array(
                'label'        => __( 'Show Image', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_description',
            array(
                'label'        => __( 'Show Description', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_price',
            array(
                'label'        => __( 'Show Price', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_add_to_cart',
            array(
                'label'        => __( 'Show Add to Cart', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        // Style Section - Card
        $this->start_controls_section(
            'style_card_section',
            array(
                'label' => __( 'Card', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'card_background',
            array(
                'label'     => __( 'Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .mj-single-food-item' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __( 'Padding', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-single-food-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-single-food-item',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-single-food-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_box_shadow',
                'label'    => __( 'Box Shadow', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-single-food-item',
            )
        );

        $this->end_controls_section();

        // Style Section - Image
        $this->start_controls_section(
            'style_image_section',
            array(
                'label'     => __( 'Image', 'mitzies-jerk' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_image' => 'yes',
                ),
            )
        );

        $this->add_responsive_control(
            'image_height',
            array(
                'label'      => __( 'Height', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'vh' ),
                'range'      => array(
                    'px' => array(
                        'min' => 100,
                        'max' => 500,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-food-item-image' => 'height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'image_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-food-item-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Get food items
     *
     * @return array
     */
    private function get_food_items() {
        $items = get_posts( array(
            'post_type'      => 'mj_food_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $options = array( '' => __( 'Select Item', 'mitzies-jerk' ) );

        foreach ( $items as $item ) {
            $options[ $item->ID ] = $item->post_title;
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if ( empty( $settings['food_item_id'] ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p class="mj-elementor-notice">' . esc_html__( 'Please select a food item from the widget settings.', 'mitzies-jerk' ) . '</p>';
            }
            return;
        }

        $atts = array(
            'id'               => $settings['food_item_id'],
            'layout'           => $settings['layout'],
            'show_image'       => $settings['show_image'] === 'yes',
            'show_description' => $settings['show_description'] === 'yes',
            'show_price'       => $settings['show_price'] === 'yes',
            'show_add_to_cart' => $settings['show_add_to_cart'] === 'yes',
        );

        echo Mitzies_Jerk_Shortcodes::single_food_item( $atts );
    }
}
