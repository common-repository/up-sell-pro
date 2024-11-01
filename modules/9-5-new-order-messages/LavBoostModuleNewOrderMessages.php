<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleNewOrderMessages extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();

		if ( $this->getValue( 'email_info_enable' ) ) {
			$this->render();
		}
	}

	public function getArgs() {

		return array(
			'posts_per_page' => $this->getValue( 'thank_additional_products' ) !== null
				? $this->getValue( 'thank_additional_products' )
				: 2,
			'orderby'        => $this->getValue( 'thank_relation_order' ) !== null
				? $this->getValue( 'thank_relation_order' )
				: 'rand',
			'add_random'     => $this->getValue( 'thank_add_if_empty' ) == 'yes',
			'type'           => $this->getValue( 'thank_relation_priority' ),
		);
	}

	public function isNote($args, $orderProductsIds) {
		if(empty($args['email_info_item_condition'])){
			return true;
		}
		if(!empty($args['email_info_item_condition']) && $args['email_info_item_condition'] === 'some'){
			return $this->hasSome($orderProductsIds, $args['email_info_order_items']);
		}
		if(!empty($args['email_info_item_condition']) && $args['email_info_item_condition'] === 'every'){
			return $this->hasEvery($orderProductsIds, $args['email_info_order_items']);
		}
		if(!empty($args['email_info_item_condition']) && $args['email_info_item_condition'] === 'any'){
			return !$this->hasSome($orderProductsIds, $args['email_info_order_items']);
		}

		return false;
	}

	public function hasSome($orderProductsIds, $infoItemsIds) {
		$intersection = array_intersect($orderProductsIds, $infoItemsIds);

		if (!empty($intersection)) {
			return true;
		} else {
			return false;
		}
	}

	public function hasEvery($orderProductsIds, $infoItemsIds) {
		$diff = array_diff($infoItemsIds, $orderProductsIds);

		if (empty($diff)) {
			return true;
		} else {
			return false;
		}
	}

	public function getNoteMessage($values) {
		$output = '';
		$output .= !empty($values['email_info_item_title'])
			? '<strong>' . $values['email_info_item_title'] . '</strong><br>'
			: '<br>';
		$output .= !empty($values['email_info_item_note'])
			? '<p>' . $values['email_info_item_note'] . '</p><br>'
			: '<br>';

		return $output;
	}

	public function renderRows( $order, $sent_to_admin, $plain_text, $email ) {

		if ( !$sent_to_admin && !$plain_text ) {
			$output = '';

			$order_items      = $order->get_items();
			$orderProductsIds = [];

			if ( ! is_wp_error( $order_items ) ) {
				foreach ( $order_items as $item_id => $order_item ) {
					array_push( $orderProductsIds, $order_item->get_product_id() );
				}
			}

			if (!empty($this->getValue('email_info_items')) && is_array($this->getValue('email_info_items'))){
				foreach ( $this->getValue('email_info_items') as $value ) {
					// render notices
					if ( is_array($value) && !empty($value['email_info_order_items'])) {
						$output .= $this->isNote($value, $orderProductsIds)
							? $this->getNoteMessage($value)
							: '';
					}
				}
				echo wp_kses( $output, $this->getAllowedTags());
            }

		}
	}

	public function render($arguments = '') {
		add_action( 'woocommerce_email_customer_details', array( $this, 'renderRows' ), 90, 4 );
	}

	public function getFields(): array {
		return array(
			'name'   => 'new_order_email_info',
			'title'  => esc_html__( 'New Order Email', 'up-sell-pro' ),
			'icon'   => 'fas fa-store',
			'fields' => array(
				array(
					'id'       => 'email_info_enable',
					'type'     => 'switcher',
					'title'    => esc_html__( 'Enable notice', 'up-sell-pro' ),
					'subtitle' => esc_html__( 'Enable\Disable New Order Email notice for client', 'up-sell-pro' ),
					'default'  => '0',
					'help'     => esc_html__( 'It shows notice in new order email', 'up-sell-pro' ),
				),

				// A Notice
				array(
					'type'    => 'notice',
					'style'   => 'info',
					'content' => esc_html__('You can add only one notice if you need more notices so please buy paid version - ', 'up-sell-pro') . '<a href="'. esc_url('https://first-design-company.com/product/lavboost-all-in-one-sales-increasing-tool/')  .'" target="_blank">'. esc_html__('LavBoost', 'up-sell-pro').'</a>',
				),

				array(
					'id'           => 'email_info_items',
					'type'         => 'repeater',
					'max' => 1,
					'title'        => esc_html__( 'Conditions', 'up-sell-pro' ),
					'subtitle'     => esc_html__( 'Conditions to show notices', 'up-sell-pro' ),
					'button_title' => esc_html__( 'Add Notice', 'up-sell-pro' ),
					'fields'       => array(
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
						array(
							'id'          => 'email_info_order_items',
							'type'        => 'select',
							'title'       => esc_html__( 'Products in order', 'up-sell-pro' ),
							'subtitle'    => esc_html__( 'Choose products', 'up-sell-pro' ),
							'chosen'      => true,
							'ajax'        => true,
							'multiple'    => true,
							'placeholder' => esc_html__( 'Select product', 'up-sell-pro' ),
							'options'     => 'posts',
							'query_args'  => array(
								'post_type'      => 'product',
								'status'         => 'publish',
								'posts_per_page' => - 1,
							),
						),
						array(
							'id'          => 'email_info_item_condition',
							'type'        => 'select',
							'title'       => esc_html__( 'Condition', 'up-sell-pro' ),
							'chosen'      => true,
							'placeholder' => esc_html__( 'Select condition', 'up-sell-pro' ),
							'options'     => array(
								'some'  => esc_html__( 'Some', 'up-sell-pro' ),
								'every' => esc_html__( 'Every', 'up-sell-pro' ),
								'any'   => esc_html__( 'Any', 'up-sell-pro' ),
							),
							'default'     => array( 'some' )
						),

						array(
							'id'          => 'email_info_item_title',
							'type'        => 'text',
							'title'       => esc_html__( 'Title', 'up-sell-pro' ),
							'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),

						array(
							'id'          => 'email_info_item_note',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Message', 'up-sell-pro' ),
							'default'     => esc_html__( 'Buying together often', 'up-sell-pro' ),
							'placeholder' => esc_html__( 'Put title text here', 'up-sell-pro' ),
						),
						array(
							'type'    => 'submessage',
							'style'   => 'info',
							'content' => '',
						),
					),
					'dependency'   => array( 'email_info_enable', '==', '1', '', 'visible' ),
				),

			)
		);
	}
}
