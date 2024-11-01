<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use abstracts\LavBoostModule;
use data\LavBoostDataLoader;
use traits\TLavBoostSingleton;

class LavBoostModuleBackup extends LavBoostModule {
	use TLavBoostSingleton;


	public function run( $args = '' ) {
		$this->createSettingsTab();
	}

	public function render( $arguments = '' ) {

	}

	public function getFields(): array {
		return array(
			'name'   => 'backup',
			'title'  => esc_html__( 'Backup', 'up-sell-pro' ),
			'icon'   => 'far fa-file-archive',
			'fields' => array(
				array(
					'type'    => 'backup',
					'title'   => esc_html__( 'Backup', 'up-sell-pro' ),
				),
			)
		);
	}
}
