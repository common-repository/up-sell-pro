<?php

namespace interfaces;
if ( ! defined( 'WPINC' ) ) {
	die;
}

interface ILavBoostDataProvider {

	public function getData( $type, $args );
}
