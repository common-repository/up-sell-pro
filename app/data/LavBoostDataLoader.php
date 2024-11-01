<?php

namespace data;
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use abstracts\LavBoostDataProvider;
use DateTime;
use WP_Query;

class LavBoostDataLoader extends LavBoostDataProvider {
	public function getDataByProvider( $type, $args ) {
		switch ( $type ) {
			case 'tags':
				return $this->getProductsByTags( $args );
				break;
			case 'categories':
				return $this->getProductsByCategories( $args );
				break;
			case 'viewed':
				return $this->getViewedProducts( $args );
				break;
			case 'random':
				return $this->getRandomProducts( $args );
				break;
			case 'search':
				return $this->getSearchQueries();
				break;
			case 'order-proofs':
				return $this->getOrderProofs();
				break;
			case 'fake-proofs':
				return $this->getFakeProofs( $args );
				break;
			case 'bundle':
				return $this->getBundleProducts( $args );
				break;
			case 'services':
				return $this->getServicesProducts( $args );
				break;
			default:
				return $this->getAllProducts( $args );
		}
	}

	// Helpers
	public function arrayFlatten( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		$result = array();
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$result = array_merge( $result, $this->arrayFlatten( $value ) );
			} else {
				$result = array_merge( $result, array( $key => $value ) );
			}
		}

		return $result;
	}

	public function getItemsId( $items ) {
		$arr = [];
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				$arr[] = $item->term_id;
			}
		}

		return $arr;
	}

	public function getFormatDate( $orderDate ) {
		$order_date_format = null;
		try {
			$order_date_format = new DateTime( $orderDate );
		} catch ( \Exception $e ) {
			$e->getMessage();
		}
		$current_date = new DateTime();
		$interval     = $current_date->diff( $order_date_format );

		if ( $interval->d > 0 ) {
			return $interval->format( '%dd %hh %im ago' );
		} elseif ( $interval->h > 0 ) {
			return $interval->format( '%hh %im ago' );
		} elseif ( $interval->i > 0 ) {
			return $interval->format( '%im ago' );
		} else {
			return esc_html__( 'just now', 'up-sell-pro' );
		}
	}

	public function getRelatedTags( $tags, $relations ) {
		$arr = [];
		if ( is_array( $tags ) && is_array( $relations ) ) {
			foreach ( $relations as $relation ) {
				if ( in_array( $relation['main-tags'], $tags ) && !empty($relation['up-sell-tags'])) {
					$arr[] = $relation['up-sell-tags'];
				}
			}

			return $this->arrayFlatten( $arr );
		}

		return [];
	}

	public function getRelatedCategories( $categories, $relations ) {
		$arr = [];
		if ( is_array( $categories ) && is_array( $relations ) ) {
			foreach ( $relations as $relation ) {
				if ( in_array( $relation['main-category'], $categories ) && !empty($relation['up-sell-categories']) ) {
					$arr[] = $relation['up-sell-categories'];
				}
			}

			return $this->arrayFlatten( $arr );
		}

		return [];
	}

	public function getRandomName() {
		$names = array(
			1  => esc_html__( 'Liam', 'up-sell-pro' ),
			2  => esc_html__( 'Emma', 'up-sell-pro' ),
			3  => esc_html__( 'Noah', 'up-sell-pro' ),
			4  => esc_html__( 'Olivia', 'up-sell-pro' ),
			5  => esc_html__( 'William', 'up-sell-pro' ),
			6  => esc_html__( 'Ava', 'up-sell-pro' ),
			7  => esc_html__( 'James', 'up-sell-pro' ),
			8  => esc_html__( 'Isabella', 'up-sell-pro' ),
			9  => esc_html__( 'Oliver', 'up-sell-pro' ),
			10 => esc_html__( 'Sophia', 'up-sell-pro' ),
			11 => esc_html__( 'Elijah', 'up-sell-pro' ),
			12 => esc_html__( 'Charlotte', 'up-sell-pro' ),
			13 => esc_html__( 'Lucas', 'up-sell-pro' ),
			14 => esc_html__( 'Amelia', 'up-sell-pro' ),
			15 => esc_html__( 'Mason', 'up-sell-pro' ),
			16 => esc_html__( 'Mia', 'up-sell-pro' ),
			17 => esc_html__( 'Logan', 'up-sell-pro' ),
			18 => esc_html__( 'Harper', 'up-sell-pro' ),
			19 => esc_html__( 'Ethan', 'up-sell-pro' ),
			20 => esc_html__( 'Evelyn', 'up-sell-pro' ),
			21 => esc_html__( 'Aiden', 'up-sell-pro' ),
			22 => esc_html__( 'Abigail', 'up-sell-pro' ),
			23 => esc_html__( 'Jackson', 'up-sell-pro' ),
			24 => esc_html__( 'Emily', 'up-sell-pro' ),
			25 => esc_html__( 'Harry', 'up-sell-pro' ),
			26 => esc_html__( 'Madison', 'up-sell-pro' ),
			27 => esc_html__( 'Alexander', 'up-sell-pro' ),
			28 => esc_html__( 'Elizabeth', 'up-sell-pro' ),
			29 => esc_html__( 'Henry', 'up-sell-pro' ),
			30 => esc_html__( 'Ella', 'up-sell-pro' ),
			31 => esc_html__( 'Jacob', 'up-sell-pro' ),
			32 => esc_html__( 'Avery', 'up-sell-pro' ),
			33 => esc_html__( 'Joshua', 'up-sell-pro' ),
			34 => esc_html__( 'Sofia', 'up-sell-pro' ),
			35 => esc_html__( 'Michael', 'up-sell-pro' ),
			36 => esc_html__( 'Chloe', 'up-sell-pro' ),
			37 => esc_html__( 'Daniel', 'up-sell-pro' ),
			38 => esc_html__( 'Scarlett', 'up-sell-pro' ),
			39 => esc_html__( 'Matthew', 'up-sell-pro' ),
			40 => esc_html__( 'Victoria', 'up-sell-pro' ),
			41 => esc_html__( 'Joseph', 'up-sell-pro' ),
			42 => esc_html__( 'Grace', 'up-sell-pro' ),
			43 => esc_html__( 'Levi', 'up-sell-pro' ),
			44 => esc_html__( 'Riley', 'up-sell-pro' ),
			45 => esc_html__( 'Samuel', 'up-sell-pro' ),
			46 => esc_html__( 'Hannah', 'up-sell-pro' ),
			47 => esc_html__( 'David', 'up-sell-pro' ),
			48 => esc_html__( 'Aria', 'up-sell-pro' ),
			49 => esc_html__( 'Carter', 'up-sell-pro' ),
			50 => esc_html__( 'Lily', 'up-sell-pro' ),
			51 => esc_html__( 'Benjamin', 'up-sell-pro' ),
			52 => esc_html__( 'Eleanor', 'up-sell-pro' ),
			53 => esc_html__( 'Andrew', 'up-sell-pro' ),
			54 => esc_html__( 'Lila', 'up-sell-pro' ),
			55 => esc_html__( 'Joshua', 'up-sell-pro' ),
			56 => esc_html__( 'Nora', 'up-sell-pro' ),
			57 => esc_html__( 'Nicholas', 'up-sell-pro' ),
			58 => esc_html__( 'Eva', 'up-sell-pro' ),
			59 => esc_html__( 'Brandon', 'up-sell-pro' ),
			60 => esc_html__( 'Ruby', 'up-sell-pro' ),
			61 => esc_html__( 'Justin', 'up-sell-pro' ),
			62 => esc_html__( 'Savannah', 'up-sell-pro' ),
			63 => esc_html__( 'Christian', 'up-sell-pro' ),
			64 => esc_html__( 'Audrey', 'up-sell-pro' ),
		);

		return $names[ array_rand( $names ) ];
	}

	public function getRandomMinutes() {
		$random_minutes_ago = rand( 0, 59 );

		return $random_minutes_ago == 0
			? esc_html__( 'just now', 'up-sell-pro' )
			: esc_html__( "{$random_minutes_ago} min. ago", 'up-sell-pro' );
	}

	public function createFakeProofs( $products ) {
		$proofs = array();
		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$product_id = $product->ID;

				$product_name      = $product->post_title;
				$product_image_url = wp_get_attachment_url( get_post_thumbnail_id( $product_id ) );

				$proof = array(
					'product'     => $product_name,
					'name'        => $this->getRandomName(),
					'img'         => $product_image_url,
					'placeholder' => wc_placeholder_img_src(),
					'date'        => $this->getRandomMinutes(),
				);
				array_push( $proofs, $proof );
			}
		}
		wp_reset_query();

		return $proofs;
	}

	// Data
	public function getOrderProofs() {
		$proofs = array();

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return $proofs;
		}

		// Get 15 orders
		$orders = wc_get_orders( array(
			'limit' => 15,
		) );

		// Loop through each order
		foreach ( $orders as $order ) {
			// Get order details
			foreach ( $order->get_items() as $item_id => $item ) {

				// Get product details
				$product_name = $item->get_name();
				$first_name   = $order->get_billing_first_name();
				$formatDate   = $this->getFormatDate( $order->get_date_created()->date( 'Y-m-d H:i:s' ) );
				$product      = $item->get_product();

				if ( $product->get_catalog_visibility() == 'hidden' ) {
					continue;
				}

				$product_thumbnail_id  = $product->get_image_id();
				$product_thumbnail_url = wp_get_attachment_image_url( $product_thumbnail_id, 'thumbnail' );
				$placeholder_image_url = wc_placeholder_img_src();

				$proof = array(
					'product'     => $product_name,
					'name'        => $first_name,
					'img'         => $product_thumbnail_url,
					'placeholder' => $placeholder_image_url,
					'date'        => $formatDate,
				);
				array_push( $proofs, $proof );
			}
		}
		shuffle( $proofs );

		return $proofs;
	}

	public function getFakeProofs( $values ) {
		$type     = ! empty( $values['type'] ) ? $values['type'] : 'product';
		$termsIds = $type == 'product_cat' ? $values['categories'] : $values['tags'];

		if ( $type == 'product' ) {
			$args     = array(
				'post_type'      => 'product',
				'post__in'       => $values['products'],
				'posts_per_page' => - 1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_visibility',
						'terms'    => array( 'exclude-from-catalog' ),
						'field'    => 'name',
						'operator' => 'NOT IN',
					),
				),
			);
			$products = new WP_Query( $args );

			return $this->createFakeProofs( $products->get_posts() );
		} else {
			$args = array(
				'post_type'      => 'product',
				'posts_per_page' => 15,
				'orderby'        => 'rand',
				'tax_query'      => array(
					array(
						'taxonomy' => $type,
						'field'    => 'term_id',
						'terms'    => $termsIds,
						'operator' => 'IN',
					),
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => 'outofstock',
						'operator' => 'NOT IN',
					),
					array(
						'taxonomy' => 'product_visibility',
						'terms'    => array( 'exclude-from-catalog' ),
						'field'    => 'name',
						'operator' => 'NOT IN',
					),
				),
			);

			$products = new WP_Query( $args );

			return $this->createFakeProofs( $products->get_posts() );
		}
	}

	public function getViewedProducts( $values ) {
		$viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', sanitize_text_field( wp_unslash( $_COOKIE['woocommerce_recently_viewed'] ) ) ) : array();

		$keys = array_flip( $viewed_products );

		if ( is_array( $values['id'] ) ) {
			foreach ( $values['id'] as $id ) {
				if ( array_key_exists($id, $keys) ) {
					unset( $viewed_products[ $keys[ $id ] ] );
				}
			}
		} else {
			if ( ! empty( $values['id'] ) && array_key_exists($values['id'], $keys) ) {
				unset( $viewed_products[ $keys[ $values['id'] ] ] );
			}
		}

		if ( empty( $viewed_products ) && empty($values['add_random']) ) {
			return [];
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $values['posts_per_page'],
			'orderby'        => $values['orderby'],
			'post_status'    => 'publish',
			'post__in'       => $viewed_products,
			'post__not_in'   => array( $values['id'] ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getSearchQueries() {
		if ( isset( $_COOKIE['lav-boost-search'] ) ) {
			return array_slice( json_decode( sanitize_text_field( stripslashes( $_COOKIE['lav-boost-search'] ) ) ), '-' . $this->getValue( 'general_keep_queries' ) );
		}
	}

	public function getAllProducts( $values ) {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => - 1,
			'post__not_in'   => array( $values['id'] ),
			'orderby'        => $values['orderby'],
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getBundleProducts( $values ) {
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => - 1,
			'post__not_in'   => array( $values['id'] ),
			'post__in'       => $values['post__in'],
			'orderby'        => $values['orderby'],
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getRandomProducts( $values ) {
		$type  = $values['type'] == 'tags' ? 'product_tag' : 'product_cat';
		$terms = $this->getItemsId( get_the_terms( $values['id'], $type ) );

		if ( is_array( $values['id'] ) ) {
			$allTermsIds = array();
			foreach ( $values['id'] as $id ) {
				$allTerms = get_the_terms( $id, $type );
				if ( $allTerms && ! is_wp_error( $allTerms ) ) {
					foreach ( $allTerms as $term ) {
						array_push( $allTermsIds, $term->term_id );
					}
				}
			}
			$terms = array_unique( $allTermsIds );
		}
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $values['posts_per_page'],
			'post__not_in'   => is_array( $values['id'] ) ? $values['id'] : array( $values['id'] ),
			'orderby'        => $values['orderby'],
			'tax_query'      => array(
				array(
					'taxonomy' => $type,
					'field'    => 'term_id',
					'terms'    => $terms,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getProductsByCategories( $values ) {
		$productCategories = $this->getItemsId( get_the_terms( $values['id'], 'product_cat' ) );

		$relatedCategories = [];
		if ( ! empty( $this->getValue( 'relation_by_category' ) ) ) {
			$relatedCategories = $this->getRelatedCategories( $productCategories, $this->getValue( 'relation_by_category' ) );
		}

		if ( is_array( $values['id'] ) ) {
			$allCategoriesIds = array();
			foreach ( $values['id'] as $id ) {
				$allCategories = get_the_terms( $id, 'product_cat' );
				if ( $allCategories && ! is_wp_error( $allCategories ) ) {
					foreach ( $allCategories as $term ) {
						array_push( $allCategoriesIds, $term->term_id );
					}
				}
			}
			$relatedCategories = array_unique( $this->getRelatedCategories( $allCategoriesIds, $this->getValue( 'relation_by_category' ) ) );
		}
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $values['posts_per_page'],
			'orderby'        => $values['orderby'],
			'post__not_in'   => is_array( $values['id'] ) ? $values['id'] : array( $values['id'] ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $relatedCategories,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getProductsByTags( $values ) {
		$productTags = $this->getItemsId( get_the_terms( $values['id'], 'product_tag' ) );

		$relatedTags = [];
		if ( ! empty( $this->getValue( 'relation_by_tag' ) ) ) {
			$relatedTags = $this->getRelatedTags( $productTags, $this->getValue( 'relation_by_tag' ) );
		}

		if ( is_array( $values['id'] ) ) {
			$allTagsIds = array();
			foreach ( $values['id'] as $id ) {
				$allTags = get_the_terms( $id, 'product_tag' );
				if ( $allTags && ! is_wp_error( $allTags ) ) {
					foreach ( $allTags as $term ) {
						array_push( $allTagsIds, $term->term_id );
					}
				}
			}
			$relatedTags = array_unique( $this->getRelatedTags( $allTagsIds, $this->getValue( 'relation_by_tag' ) ) );
		}

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $values['posts_per_page'],
			'orderby'        => $values['orderby'],
			'post__not_in'   => is_array( $values['id'] ) ? $values['id'] : array( $values['id'] ),
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_id',
					'terms'    => $relatedTags,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
				array(
					'taxonomy' => 'product_visibility',
					'terms'    => array( 'exclude-from-catalog' ),
					'field'    => 'name',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getServicesProducts( $values ) {
		$type  = $values['type'] == 'tags' ? 'product_tag' : 'product_cat';
		$terms = !empty($values['terms']) ? $values['terms'] : array();

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => $values['posts_per_page'],
			'orderby'        => $values['orderby'],
			'post__not_in'   => is_array( $values['id'] ) ? $values['id'] : array( $values['id'] ),
			'tax_query'      => array(
				array(
					'taxonomy' => $type,
					'field'    => 'term_id',
					'terms'    => $terms,
					'operator' => 'IN',
				),
				array(
					'taxonomy' => 'product_type',
					'field'    => 'name',
					'terms'    => array( 'simple' ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => 'outofstock',
					'operator' => 'NOT IN',
				),
			),
		);

		return new WP_Query( $args );
	}

	public function getData( $type, $args ) {
		return $this->getDataByProvider( $type, $args );
	}
}
