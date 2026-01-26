<?php
/**
 * Categories Elementor Widget
 *
 * @package    Mitzies_Jerk
 * @subpackage Mitzies_Jerk/includes/elementor/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Categories Widget
 */
class Mitzies_Jerk_Widget_Categories extends \Elementor\Widget_Base {

    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mitzies_jerk_categories';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __( 'Food Categories', 'mitzies-jerk' );
    }

    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-folder';
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
        return array( 'categories', 'food', 'menu', 'filter', 'mitzies' );
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
            'layout',
            array(
                'label'   => __( 'Layout', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => array(
                    'grid'       => __( 'Grid', 'mitzies-jerk' ),
                    'list'       => __( 'List', 'mitzies-jerk' ),
                    'horizontal' => __( 'Horizontal', 'mitzies-jerk' ),
                ),
            )
        );

        $this->add_control(
            'columns',
            array(
                'label'     => __( 'Columns', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'default'   => '3',
                'options'   => array(
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'condition' => array(
                    'layout' => 'grid',
                ),
            )
        );

        $this->add_control(
            'show_count',
            array(
                'label'        => __( 'Show Item Count', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Show', 'mitzies-jerk' ),
                'label_off'    => __( 'Hide', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_image',
            array(
                'label'        => __( 'Show Category Image', 'mitzies-jerk' ),
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
                'default'      => 'no',
            )
        );

        $this->add_control(
            'hide_empty',
            array(
                'label'        => __( 'Hide Empty Categories', 'mitzies-jerk' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Hide', 'mitzies-jerk' ),
                'label_off'    => __( 'Show', 'mitzies-jerk' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'orderby',
            array(
                'label'   => __( 'Order By', 'mitzies-jerk' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'name',
                'options' => array(
                    'name'  => __( 'Name', 'mitzies-jerk' ),
                    'count' => __( 'Item Count', 'mitzies-jerk' ),
                    'id'    => __( 'ID', 'mitzies-jerk' ),
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

        $this->add_control(
            'menu_page',
            array(
                'label'       => __( 'Menu Page', 'mitzies-jerk' ),
                'type'        => \Elementor\Controls_Manager::SELECT2,
                'options'     => $this->get_pages(),
                'default'     => '',
                'description' => __( 'Page where the food menu shortcode is placed', 'mitzies-jerk' ),
            )
        );

        $this->end_controls_section();

        // Style Section - Category Card
        $this->start_controls_section(
            'style_card_section',
            array(
                'label' => __( 'Category Card', 'mitzies-jerk' ),
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
                    '{{WRAPPER}} .mj-category-item' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'card_hover_background',
            array(
                'label'     => __( 'Hover Background Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#f8f9fa',
                'selectors' => array(
                    '{{WRAPPER}} .mj-category-item:hover' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mj-category-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'label'    => __( 'Border', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-category-item',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'mitzies-jerk' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-category-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_box_shadow',
                'label'    => __( 'Box Shadow', 'mitzies-jerk' ),
                'selector' => '{{WRAPPER}} .mj-category-item',
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
                'selector' => '{{WRAPPER}} .mj-category-title',
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __( 'Title Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-category-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'count_color',
            array(
                'label'     => __( 'Count Color', 'mitzies-jerk' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mj-category-count' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Image
        $this->start_controls_section(
            'style_image_section',
            array(
                'label'     => __( 'Category Image', 'mitzies-jerk' ),
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
                        'min' => 50,
                        'max' => 400,
                    ),
                ),
                'default'    => array(
                    'unit' => 'px',
                    'size' => 150,
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .mj-category-image' => 'height: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .mj-category-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'layout'           => $settings['layout'],
            'columns'          => $settings['columns'],
            'show_count'       => $settings['show_count'] === 'yes',
            'show_image'       => $settings['show_image'] === 'yes',
            'show_description' => $settings['show_description'] === 'yes',
            'hide_empty'       => $settings['hide_empty'] === 'yes',
            'orderby'          => $settings['orderby'],
            'order'            => $settings['order'],
            'menu_page'        => $settings['menu_page'],
        );

        echo Mitzies_Jerk_Shortcodes::food_categories( $atts );
    }
}
