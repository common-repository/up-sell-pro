<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use traits\TLavBoostSingleton;

class LavBoostModuleRelations extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
	}

	public function getFields(): array {
		return array(
			'name'   => 'relation',
			'title'  => esc_html__( 'Relation', 'up-sell-pro' ),
			'icon'   => 'fas fa-sitemap',
			'fields' => array(
				// A Notice
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => esc_html__('You can add up to 5 relations for every type if you need more relations so please buy paid version - ', 'up-sell-pro') . '<a href="'. esc_url('https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/')  .'" target="_blank">'. esc_html__('LavBoost', 'up-sell-pro').'</a>',
				),
				
				array(
					'type'         => 'repeater',
					'id'           => 'relation_by_category',
					'title'        => esc_html__( 'Relation by categories', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Set up the relations between products categories', 'up-sell-pro' ),
					'help'         => esc_html__( 'It helps to create mass relations between categories (for example: laptops->mouses, cases and etc.)', 'up-sell-pro' ),
					'max'          => 5,
					'button_title' => esc_html__( 'Add Relation', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'main-category',
							'type'        => 'select',
							'title'       => esc_html__( 'Main category', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select category', 'up-sell-pro' ),
							'options'     => 'categories',
							'settings'    => array( 'width' => '50%' ),
							'query_args'  => array(
								'taxonomy' => 'product_cat',
							),
						),
						array(
							'id'          => 'up-sell-categories',
							'type'        => 'select',
							'title'       => esc_html__( 'Related categories', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select categories', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'width'       => '250px',
							'options'     => 'categories',
							'query_args'  => array(
								'taxonomy' => 'product_cat',
							),
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
				),

				array(
					'type'         => 'repeater',
					'id'           => 'relation_by_tag',
					'title'        => esc_html__( 'Relation by tags', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Set up the relations between products tags', 'up-sell-pro' ),
					'help'         => esc_html__( 'It helps to create mass relations between tags (for example: electronics->cables, supplies and etc.)', 'up-sell-pro' ),
					'max'          => 5,
					'button_title' => esc_html__( 'Add Relation', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'main-tags',
							'type'        => 'select',
							'title'       => esc_html__( 'Main tag', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select tag', 'up-sell-pro' ),
							'options'     => 'tags',
							'settings'    => array( 'width' => '50%' ),
							'query_args'  => array(
								'taxonomy' => 'product_tag',
							),
						),
						array(
							'id'          => 'up-sell-tags',
							'type'        => 'select',
							'title'       => esc_html__( 'Related tags', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Select tags', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'sortable'    => true,
							'options'     => 'tags',
							'query_args'  => array(
								'taxonomy' => 'product_tag',
							),
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
				),

			),
		);
	}
}
