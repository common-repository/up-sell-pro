<?php

namespace abstracts;

use interfaces\ILavBoostDataProvider;

if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class LavBoostDataProvider implements ILavBoostDataProvider {

	abstract function getData( $type, $args );

	public function getSettings(): array {
		if ( ! get_option( LAV_BOOST_PREFIX ) ) {
			return array();
		} else {
			return get_option( LAV_BOOST_PREFIX );
		}
	}

	public function getValue( $key ) {
		return ! empty( $this->getSettings()[ $key ] ) && is_array($this->getSettings())
			? $this->getSettings()[ $key ]
			: null;
	}

}
