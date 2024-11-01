<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleHelp extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
	}

	public function render( $arguments = '' ) {

	}

	public function getFields(): array {
		return array(
			'name'   => 'help',
			'title'  => esc_html__( 'Help & Info', 'up-sell-pro' ),
			'icon'   => 'far fa-life-ring',
			'fields' => array(
				// Info
				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Help', 'up-sell-pro' ),
				),
				array(
					'type'    => 'content',
					'content' => '<p>'. esc_html__('For customers who need to set up the most popular and effective tools to increase sales simple products in the WooCommerce store in a few clicks. LavBoost - the All in One Sales Increasing Plugin.', 'up-sell-pro').'</p>
                              <p><strong>'. esc_html__('Documentation', 'up-sell-pro').'</strong></p>
                              <p>'. esc_html__('Useful documentation you can find via link', 'up-sell-pro').' <a href="'. esc_url('https://alicethemes.com/documentation/')  .'" target="_blank" >'. esc_html__('online documentation', 'up-sell-pro').'</a> </p>',
				),
				array(
					'type'    => 'heading',
					'content' => esc_html__( 'Other products', 'up-sell-pro' ),
				),

				array(
					'type'    => 'content',

					'content' => '<div class="up-cards-container">
							
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/baner-new.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('Curie', 'up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Useful and Fast WordPress Theme For Authors And Writers', 'up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://www.templatemonster.com/wordpress-themes/curie-wordpress-theme-for-authors-and-writers-278443.html')  .'" target="_blank">'. esc_html__('Learn more', 'up-sell-pro').'</a>
								  </div>
								</div>		
								
								
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/web-design.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('Website Design','up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Professional Web Design & Development Services','up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://first-design-company.com/services/web-design/')  .'" target="_blank">'. esc_html__('Learn more','up-sell-pro').'</a>
								  </div>
								</div>
								
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/brand.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('Branding','up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Branding And Identity Design Services','up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://first-design-company.com/services/branding-service/')  .'" target="_blank">'. esc_html__('Learn more','up-sell-pro').'</a>
								  </div>
								</div>
								
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/redesign.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('Website Redesign','up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Revamp And Redesign Of An Existing Website','up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://first-design-company.com/services/website-redesign/')  .'" target="_blank">'. esc_html__('Learn more','up-sell-pro').'</a>
								  </div>
								</div>
								
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/support.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('Website Maintenance','up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Professional Website Maintenance & Support','up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://first-design-company.com/services/website-maintenance/')  .'" target="_blank">'. esc_html__('Learn more','up-sell-pro').'</a>
								  </div>
								</div>
								
								<div class="card">
								  <div class="image">
								    <!-- You can add an image here using the <img> element -->
								    <img src="'. esc_url(LAV_BOOST_URL . '/admin/img/tweaks.jpg')  .'" alt="Curie">
								  </div>
								  <div class="content">
								    <h2 class="title">'. esc_html__('WordPress Tweaks & Fix','up-sell-pro').'</h2>
								    <p class="description">'. esc_html__('Develop A New Plugin, Finalize The Theme','up-sell-pro').'</p>
								    <a class="button" href="'. esc_url('https://first-design-company.com/services/wp-fixes-tweaks/')  .'" target="_blank">'. esc_html__('Learn more','up-sell-pro').'</a>
								  </div>
								</div>
							
							</div>',
				),
			)
		);
	}
}
