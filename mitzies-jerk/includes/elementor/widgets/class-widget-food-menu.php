<?php
/**
 * Food Menu Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Food Menu Widget
 */
class Mitzies_Jerk_Widget_Food_Menu extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_food_menu';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Food Menu', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-menu-bar';
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
        return array( 'food', 'menu', 'restaurant', 'preorder', 'mitzies' );
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
            'category',
            array(
                'label'   => __( 'Category', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_food_categories(),
                'default' => '',
                'multiple' => true,
            )
        );

        $this->add_control(
            'columns',
            array(
                'label'   => __( 'Columns', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ),
            )
        );

        $this->add_control(
            'posts_per_page',
            array(
                'label'   => __( 'Items Per Page', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 9,
                'min'     => 1,
                'max'     => 50,
            )
        );

        $this->add_control(
            'show_filters',
            array(
                'label'        => __( 'Show Category Filters', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_pagination',
            array(
                'label'        => __( 'Show Pagination', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'orderby',
            array(
                'label'   => __( 'Order By', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'date',
                'options' => array(
                    'date'       => __( 'Date', 'mitzies-jerk' ),
                    'title'      => __( 'Title', 'mitzies-jerk' ),
                    'menu_order' => __( 'Menu Order', 'mitzies-jerk' ),
                    'price'      => __( 'Price', 'mitzies-jerk' ),
                ),
            )
        );

        $this->add_control(
            'order',
            array(
                'label'   => __( 'Order', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => array(
                    'ASC'  => __( 'Ascending', 'mitzies-jerk' ),
                    'DESC' => __( 'Descending', 'mitzies-jerk' ),
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Grid
        $this->start_controls_section(
            'style_grid_section',
            array(
                'label' => __( 'Grid', 'mitzies-jerk' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_responsive_control(
            'grid_gap',
            array(
                'label'      => __( 'Grid Gap', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 20,
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-food-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Item Card
        $this->start_controls_section(
            'style_card_section',
            array(
                'label' => __( 'Item Card', 'mitzies-jerk' ),
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
                    '{{WRAPPER}} .mj-food-item' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-food-item',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-food-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_box_shadow',
                'label'    => __( 'Box Shadow', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-food-item',
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
                'name'     => 'title_typography',
                'label'    => __( 'Title Typography', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-food-item-title',
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __( 'Title Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-food-item-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'price_typography',
                'label'    => __( 'Price Typography', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-food-item-price',
            )
        );

        $this->add_control(
            'price_color',
            array(
                'label'     => __( 'Price Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-food-item-price' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'style_button_section',
            array(
                'label' => __( 'Add to Cart Button', 'mitzies-jerk' ),
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
                    '{{WRAPPER}} .mj-add-to-cart-btn' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-add-to-cart-btn' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-add-to-cart-btn:hover' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-add-to-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Get food categories
     *
     * @return array
     */
    private function get_food_categories() {
        $categories = get_terms( array(
            'taxonomy'   => 'mj_food_category',
            'hide_empty' => false,
        ) );

        $options = array( '' => __( 'All Categories', 'mitzies-jerk' ) );

        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
            foreach ( $categories as $category ) {
                $options[ $category->slug ] = $category->name;
            }
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $atts = array(
            'category'        => is_array( $settings['category'] ) ? implode( ',', $settings['category'] ) : $settings['category'],
            'columns'         => $settings['columns'],
            'posts_per_page'  => $settings['posts_per_page'],
            'show_filters'    => $settings['show_filters'] === 'yes',
            'show_pagination' => $settings['show_pagination'] === 'yes',
            'orderby'         => $settings['orderby'],
            'order'           => $settings['order'],
        );

        echo Mitzies_Jerk_Shortcodes::food_menu( $atts );
    }
}
