<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use ElementorAliceAddons\AliceAddonsHelper;



class LalBoostFlashSale extends Widget_Base {

	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
	}

	public function get_name() {
		return 'lav-boost-flash-sale';
	}

	public function get_title() {
		return __( 'Flash Sale','up-sell-pro' );
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	public function get_categories() {
		return array( 'lav-boost-addons-category' );
	}

	public function get_style_depends() {
		return array( 'swiper', 'elementor-addons' );
	}

	public function get_script_depends() {
		return array( 'swiper', 'flipper-responsive', 'elementor-init' );
	}

	protected function register_controls() {

		// Start content section
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content','up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'data_source',
			[
				'label'   => __( 'Data source', 'up-sell-pro' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'manual'        => esc_html__( 'Manual selected', 'up-sell-pro' ),
					'sale-products' => esc_html__( 'Sale products', 'up-sell-pro' ),
				],
				'default' => 'sale-products'
			]
		);

		$this->add_control(
			'products',
			[
				'label'       => esc_html__( 'Products', 'up-sell-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $this->getProductOptions(),
				'condition'   => [
					'data_source' => 'manual'
				],
			]
		);

		$this->add_control(
			'alice_category_grid_per_page',
			[
				'label'   => __( 'Posts quantity', 'up-sell-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '3',
				'options' => [
					'2' => esc_html__( '2', 'up-sell-pro' ),
					'3' => esc_html__( '3', 'up-sell-pro' ),
					'4' => esc_html__( '4', 'up-sell-pro' ),
					'5' => esc_html__( '5', 'up-sell-pro' ),
					'6' => esc_html__( '6', 'up-sell-pro' ),
				]
			]
		);

		$this->add_control( 'title', [
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label'       => esc_html__( 'Title', 'up-sell-pro' ),
			'default'     => esc_html__( 'Deal of the day', 'up-sell-pro' ),
			'label_block' => true,
		] );

		$this->add_control( 'markettext', [
			'type'        => \Elementor\Controls_Manager::TEXT,
			'label'       => esc_html__( 'Add marketing text', 'up-sell-pro' ),
			'default'     => esc_html__( 'Hurry Up! Offer ends soon.', 'up-sell-pro' ),
			'label_block' => true,
		] );

		$this->add_control( 'fakebar', [
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label'        => esc_html__( 'Set sold bar:', 'up-sell-pro' ),
			'description'  => esc_html__( 'By default, widget shows real progress bar based on stock status, you can enable fake bar if set up "Sold" quantity', 'up-sell-pro' ),
			'label_on'     => esc_html__( 'Yes', 'up-sell-pro' ),
			'label_off'    => esc_html__( 'No', 'up-sell-pro' ),
			'return_value' => 'yes',
		] );

		$this->add_control( 'fakebar_data', [
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label'        => esc_html__( 'Use fake data for sold bar:', 'up-sell-pro' ),
			'description'  => esc_html__( 'By default, widget shows real progress bar based on stock status, you can enable fake bar if set up "Sold" quantity', 'up-sell-pro' ),
			'label_on'     => esc_html__( 'Yes', 'up-sell-pro' ),
			'label_off'    => esc_html__( 'No', 'up-sell-pro' ),
			'return_value' => 'yes',
			'condition'    => [
				'fakebar' => 'yes'
			]
		] );


		$this->add_control( 'faketimer', [
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label'        => esc_html__( 'Set timer', 'up-sell-pro' ),
			'description'  => esc_html__( 'By default, widget shows countdown base on Sale price dates of product. You can enable fake timer (always shows 12 hours)', 'up-sell-pro' ),
			'label_on'     => esc_html__( 'Yes', 'up-sell-pro' ),
			'label_off'    => esc_html__( 'No', 'up-sell-pro' ),
			'return_value' => 'yes',
			'default'      => 'no'
		] );

		$this->add_control(
			'disable_title',
			[
				'label'        => esc_html__( 'Disable product title', 'up-sell-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'up-sell-pro' ),
				'label_off'    => __( 'Off', 'up-sell-pro' ),
				'return_value' => 'yes',
				'default'      => 'no'
			]
		);

		$this->add_control(
			'disable_price',
			[
				'label'        => esc_html__( 'Disable product price', 'up-sell-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'up-sell-pro' ),
				'label_off'    => __( 'Off', 'up-sell-pro' ),
				'default'      => 'no',
				'return_value' => 'yes'
			]
		);

		$this->add_control(
			'disable_button',
			[
				'label'        => __( 'Disable Button', 'up-sell-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'up-sell-pro' ),
				'label_off'    => __( 'Off', 'up-sell-pro' ),
				'default'      => 'no',
				'return_value' => 'yes'
			]
		);

		$this->add_control( 'slider_autorotate', [
			'type'         => Controls_Manager::SWITCHER,
			'label'        => esc_html__( 'Enable autorotate?', 'up-sell-pro' ),
			'label_on'     => esc_html__( 'Yes', 'up-sell-pro' ),
			'label_off'    => esc_html__( 'No', 'up-sell-pro' ),
			'return_value' => 'yes',
			'default'      => 'yes'
		] );
		$this->add_control(
			'slider_autorotate_delay',
			[
				'label'     => __( 'Delay (ms)', 'up-sell-pro' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => '6000',
				'condition' => [
					'slider_autorotate' => 'yes'
				]
			]
		);

		$this->end_controls_section();
		// End content section


		// Start Tab Style
		// Product Title
		$this->start_controls_section(
			'title_style',
			[
				'label' => __( 'Title', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'title_style_alignment',
			[
				'label'     => __( 'Title Alignment', 'up-sell-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider__code' => 'text-align: {{VALUE}};'
				]
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .blog-slider__code',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],

			]
		);
		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider__code' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
		// End Title


		// Product Title
		$this->start_controls_section(
			'product_title_style',
			[
				'label' => __( 'Product title', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'product_title_alignment',
			[
				'label'     => __( 'Title Alignment', 'up-sell-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider__title' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'product_title_typography',
				'selector' => '{{WRAPPER}} .blog-slider__title',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],

			]
		);

		$this->start_controls_tabs( 'product_title_tabs' );

		$this->start_controls_tab( 'normal_product_title', [ 'label' => esc_html__( 'Normal', 'up-sell-pro' ) ] );

		$this->add_control(
			'product_title_color',
			[
				'label'     => __( 'Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider__title' => 'color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover_product_title', [ 'label' => esc_html__( 'Hover', 'up-sell-pro' ) ] );

		$this->add_control(
			'product_title_hover_color',
			[
				'label'     => __( 'Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider__title:hover' => 'color: {{VALUE}};'
				]

			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();
		// End Product  Title

		// Product Title
		$this->start_controls_section(
			'price_style',
			[
				'label' => __( 'Price', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'price_style_alignment',
			[
				'label'     => __( 'Price Alignment', 'up-sell-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .card-price' => 'text-align: {{VALUE}};'
				]
			]
		);
		$this->add_control(
			'price_normal_color',
			[
				'label'     => __( 'Standard price color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .card-price del bdi' => 'color: {{VALUE}}; text-decoration-color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_normal_typography',
				'selector' => '{{WRAPPER}} .card-price del bdi',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],

			]
		);

		$this->add_control(
			'price_sale_color',
			[
				'label'     => __( 'Sale price color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .card-price ins bdi' => 'color: {{VALUE}};'
				]
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'price_sale_typography',
				'selector' => '{{WRAPPER}} .card-price ins bdi',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],

			]
		);

		$this->end_controls_section();
		// End Product  Title

		// Sold bar
		$this->start_controls_section(
			'sold_bar_style',
			[
				'label' => __( 'Sold bar', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'sold_bar__text_color',
			[
				'label'     => __( 'Text color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .sold-bar' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sod_bar_text_typography',
				'selector' => '{{WRAPPER}}  .sold-bar',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
			]
		);

		$this->add_control(
			'sold_bar_progress_color',
			[
				'label'     => __( 'Progress color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .blog-slider .progress' => 'background-color: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
		// End Sold Bar

		// Start Market text
		$this->start_controls_section(
			'market_text_style',
			[
				'label' => __( 'Marketing text', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'market_text_color',
			[
				'label'     => __( 'Progress color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .market-text' => 'color: {{VALUE}};'
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'market_text_typography',
				'selector' => '{{WRAPPER}} .market-text',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
			]
		);
		$this->add_responsive_control(
			'market_text_alignment',
			[
				'label'     => __( 'Price Alignment', 'up-sell-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .market-text' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->end_controls_section();
		// End market text


		// Countdown
		$this->start_controls_section(
			'alice_countdown_style',
			[
				'label' => __( 'Countdown', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'alice_countdown_label_color',
			[
				'label'     => __( 'Label Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#091C55',
				'selectors' => [
					'{{WRAPPER}} .alice-countdown .flipper-group label' => 'color: {{VALUE}};'
				]
			]
		);
		$this->add_control(
			'alice_countdown_digits_color',
			[
				'label'     => __( 'Digits Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#FFFFFF',
				'selectors' => [
					'{{WRAPPER}} .alice-countdown .flipper' => 'color: {{VALUE}};'
				]
			]
		);
		$this->add_control(
			'alice_countdown_background_color',
			[
				'label'     => __( 'Background Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#052047',
				'selectors' => [
					'{{WRAPPER}} .alice-countdown .digit-next'   => 'background: {{VALUE}};',
					'{{WRAPPER}} .alice-countdown .digit-top'    => 'background: {{VALUE}};',
					'{{WRAPPER}} .alice-countdown .digit-top2.r' => 'background: {{VALUE}};',
					'{{WRAPPER}} .alice-countdown .digit-bottom' => 'background: {{VALUE}};',
				]
			]
		);
		$this->add_control(
			'alice_countdown_delimiter_color',
			[
				'label'     => __( 'Delimiter Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#091C55',
				'selectors' => [
					'{{WRAPPER}} .alice-countdown .flipper-delimiter' => 'color: {{VALUE}};',
				]
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'alice_countdown_digits_typography',
				'global'   => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
				'selector' => '{{WRAPPER}} .alice-countdown .flipper-group label',
			]
		);

		$this->end_controls_section();
		// End title

		// Button
		$this->start_controls_section(
			'button_style',
			[
				'label' => __( 'Button', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'button_style_alignment',
			[
				'label'     => __( 'Button Alignment', 'up-sell-pro' ),
				'type'      => Controls_Manager::CHOOSE,
				'toggle'    => false,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-left'
					],
					'center' => [
						'title' => __( 'Center', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-center'
					],
					'right'  => [
						'title' => __( 'Right', 'up-sell-pro' ),
						'icon'  => 'eicon-text-align-right'
					]
				],
				'selectors' => [
					'{{WRAPPER}} .button-row' => 'text-align: {{VALUE}};'
				]
			]
		);

		$this->start_controls_tabs( 'button_tabs' );

		$this->start_controls_tab( 'normal_button', [ 'label' => esc_html__( 'Normal', 'up-sell-pro' ) ] );

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .button-row .add_to_cart_button' => 'color: {{VALUE}};'
				]
			]
		);
		$this->add_control(
			'button_color_bg',
			[
				'label'     => __( 'Background', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .button-row .add_to_cart_button'                           => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .blog-slider__pagination .swiper-pagination-bullet-active' => 'background: {{VALUE}};'
				]
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover_button', [ 'label' => esc_html__( 'Hover', 'up-sell-pro' ) ] );

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .button-row .add_to_cart_button:hover' => 'color: {{VALUE}};'
				]

			]
		);
		$this->add_control(
			'button_hover_color_bg',
			[
				'label'     => __( 'Background', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .flash-sale.lav-boost .button-row .add_to_cart_button:hover' => 'background-color: {{VALUE}}; ',
					'{{WRAPPER}} .flash-sale.lav-boost .button-row .button:hover' => 'background: {{VALUE}};  box-shadow: inset -5em 0 0 0 {{VALUE}}, inset 5em 0 0 0 {{VALUE}};',
				]

			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'up-sell-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .blog-slider .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->end_controls_section();

		// General
		$this->start_controls_section(
			'style_card',
			[
				'label' => __( 'General', 'up-sell-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'general_bg',
			[
				'label'     => __( 'Background color', 'up-sell-pro' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
				'selectors' => [
					'{{WRAPPER}} .flash-sale' => 'background: {{VALUE}};'
				]
			]
		);

		$this->add_control(
			'card_border_radius',
			[
				'label'      => esc_html__( 'Section Border Radius', 'up-sell-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .blog-slider' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_shadow',
				'label'    => esc_html__( 'Section Shadow', 'up-sell-pro' ),
				'selector' => '{{WRAPPER}} .blog-slider',
			]
		);

		$this->add_control(
			'image_border_radius',
			[
				'label'      => esc_html__( 'Image Border Radius', 'up-sell-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .blog-slider__img img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .blog-slider__img'     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator'  => 'before',
			]
		);


		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_shadow',
				'label'    => esc_html__( 'Image Shadow', 'up-sell-pro' ),
				'selector' => '{{WRAPPER}} .swiper-slide-active .blog-slider__img',
			]
		);

		$this->end_controls_section();

		// End Tab Style
	}


	protected function render() {
		$settings = $this->get_settings_for_display();
		$this->add_render_attribute( 'lav_slider_settings', [
			'data-autorotate'       => $settings['slider_autorotate'],
			'data-autorotate-delay' => $settings['slider_autorotate_delay'],
		] );

		$products = null;
		if ( ! empty( $settings['products'] ) && $settings['data_source'] == 'manual' ) {
			$products = $this->getProductsOnSale( $settings['products'], $settings['alice_category_grid_per_page'] );
		} else {
			$products = $this->getProductsOnSale( array(), $settings['alice_category_grid_per_page'] );
		}

		$labels = sprintf( '%1$s|%2$s|%3$s|%4$s',
			esc_html__( 'Days', 'up-sell-pro' ),
			esc_html__( 'Hours', 'up-sell-pro' ),
			esc_html__( 'Minutes', 'up-sell-pro' ),
			esc_html__( 'Seconds', 'up-sell-pro' ) );

		if ( $products->have_posts() ) {
			?>
            <!-- BLOG CARDS -->
            <div class="flash-sale lav-boost blog-slider blog-slider-<?php echo esc_attr( $this->get_id() ); ?>" <?php echo $this->get_render_attribute_string( 'lav_slider_settings' ); ?>>
                <div class="blog-slider__wrp swiper-wrapper">
					<?php while ( $products->have_posts() ): ?>
						<?php
						$products->the_post();
						$id        = get_the_ID();
						$_product  = wc_get_product( $id );
						$due_date  = strtotime( $this->getDueToDate( $id, $settings['faketimer'] ) );
						$sold      = $this->getSold( $id );
						$available = $this->getAvailable( $id );

						if ( ! empty( $settings['fakebar_data'] ) && $settings['fakebar_data'] ) {
							$this->setFakeSales( $id );
							$sold = $this->getFakeSales( $id );
						}

						?>
                        <div class="blog-slider__item swiper-slide">
                            <div class="blog-slider__img">
                                <a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
									<?php echo $_product->get_image( 'medium' ); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>
                            </div>
                            <div class="blog-slider__content">
								<?php if ( ! empty( $settings['title'] ) ): ?>
                                    <span class="blog-slider__code"><?php echo esc_html( $settings['title'] ) ?></span>
								<?php endif; ?>
								<?php if ( $settings['disable_title'] != 'yes' ): ?>
                                    <a class="blog-slider__title"
                                       href="<?php echo esc_url( get_permalink( $id ) ); ?>">
										<?php echo wp_kses_post( $_product->get_name() ); ?>
                                    </a>
								<?php endif; ?>
								<?php if ( $settings['disable_price'] != 'yes' ): ?>
                                    <p class="<?php echo esc_attr( apply_filters( 'woocommerce_product_price_class', 'card-price' ) ); ?>">
										<?php echo $_product->get_price_html(); ?>
                                    </p>
								<?php endif; ?>
								<?php if ( $settings['fakebar'] == 'yes' ): ?>
                                    <div class="blog-slider__text">
                                        <div class="sold-bar">
                                            <div class="sold">
                                                <strong><?php echo esc_html__( 'Sold: ', 'up-sell-pro' ) ?></strong>
                                                <span><?php echo esc_html( $sold ) ?></span>
                                            </div>
                                            <div class="available">
                                                <strong><?php echo esc_html__( 'Available: ', 'up-sell-pro' ) ?></strong>
                                                <span><?php echo esc_html( $available ) ?></span>
                                            </div>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo esc_attr( $this->getPercent( $available, $sold ) ) ?>%;"></div>
                                        </div>
                                    </div>
								<?php endif; ?>
								<?php if ( ! empty( $settings['markettext'] ) ): ?>
                                    <div class="market-text"><?php echo esc_html( $settings['markettext'] ) ?></div>
								<?php endif; ?>
								<?php if ( ! empty( $due_date ) && $settings['faketimer'] == 'yes' ): ?>
                                    <div data-id="<?php echo esc_attr( $id ); ?>"
                                         class="alice-countdown alice-countdown-<?php echo esc_attr( $id ); ?>">
										<?php if ( date( "Y-m-d H:i:s" ) < date( "Y-m-d H:i:s", $due_date ) ): ?>
                                            <div class="flipper"
                                                 data-datetime="<?php echo esc_html( date( "Y-m-d H:i:s", $due_date ) ); ?>"
                                                 data-template="ddd|HH|ii|ss"
                                                 data-labels="<?php echo esc_html( $labels ); ?>"
                                                 data-reverse="true"
                                                 id="alice-countdown-<?php echo esc_attr( $id ); ?>">
                                            </div>
										<?php else: ?>
                                            <div class="notice">
												<?php echo esc_html__( 'Time is end', 'up-sell-pro' ) ?>
                                            </div>
										<?php endif; ?>
                                    </div>
								<?php endif; ?>
								<?php if ( $settings['disable_button'] != 'yes' ): ?>
                                    <div class="button-row">
										<?php woocommerce_template_loop_add_to_cart(); ?>
                                    </div>
								<?php endif; ?>
                            </div>
                        </div>
						<?php wp_reset_postdata(); ?>
					<?php endwhile; ?>
                </div>
                <div class="blog-slider__pagination"></div>
            </div>
			<?php
		}
	}

	public function getProductOptions() {
		$options  = [];
		$args     = array(
			'post_type'      => 'product',
			'posts_per_page' => - 1,
		);
		$products = new WP_Query( $args );
		if ( $products->have_posts() ) {
			while ( $products->have_posts() ) {
				$products->the_post();
				$product_id   = get_the_ID();
				$product_name = get_the_title();

				$options[ $product_id ] = $product_name;
			}
		}
		wp_reset_postdata();

		return $options;
	}

	public function getProductsOnSale( $product_ids = array(), $limit = - 1 ) {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => '_min_variation_sale_price',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'        => array(
				'meta_value_num' => 'DESC',
				'meta_value'     => 'DESC',
			),
		);

		if ( ! empty( $product_ids ) ) {
			$args['post__in']   = $product_ids;
			$args['meta_query'] = array();
			$args['orderby']    = array();
		}

		return new WP_Query( $args );
	}

	public function getRandomFutureDate() {
		return date( 'Y-m-d H:i', time() + ( 18 * 60 * 60 ) );
	}

	public function getDueToDate( $product_id, $args ) {
		$sale_end_date = get_post_meta( $product_id, '_sale_price_dates_to', true );
		if ( ! empty( $sale_end_date ) ) {
			return date( 'Y-m-d H:i', $sale_end_date );
		} else {
			return $args == 'yes' ? $this->getRandomFutureDate() : 0;
		}
	}

	public function getSold( $id ) {
		return get_post_meta( $id, 'total_sales', true );
	}

	public function getAvailable( $id ) {
		$available = get_post_meta( $id, '_stock', true );

		return ! empty( $available ) ? $available : '100+';
	}

	public function getPercent( $stock_available, $stock_sold ) {
		return ( $stock_available > 0 ) ? round( ( intval( $stock_available ) * 100 ) / ( intval( $stock_sold ) + intval( $stock_available ) ) ) : '';
	}

	public function getArgs( $max = 9 ) {
		return rand( 1, $max );
	}

	public function getCount( $id ) {
		$data = get_post_meta( $id, 'lav_boost_elementor_fake_sales_count', true );

		return ! empty( $data ) ? $data : 2;
	}

	public function setFakeSales( $id ) {
		if ( empty( $id ) ) {
			return;
		}

		$current_datetime      = current_time( 'timestamp' );
		$last_update_timestamp = get_post_meta( $id, 'lav_boost_elementor_fake_sales_last_update', true );

		if ( empty( $last_update_timestamp ) || ( $current_datetime - $last_update_timestamp ) > 24 * 60 * 60 ) {
			$fake_sales_count = $this->getArgs( 4 );
			$current_count    = $this->getCount( $id );

			update_post_meta( $id, 'lav_boost_elementor_fake_sales_count', $fake_sales_count + $current_count );
			update_post_meta( $id, 'lav_boost_elementor_fake_sales_last_update', $current_datetime );
		}
	}

	public function getFakeSales( $id ) {
		if ( ! empty( $id ) ) {
			$data = get_post_meta( $id, 'lav_boost_elementor_fake_sales_count', true );

			return ! empty( $data ) ? $data : 12;
		} else {
			return 4;
		}
	}
}
