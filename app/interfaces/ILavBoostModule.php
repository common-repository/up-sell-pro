<?php

namespace interfaces;
if ( ! defined( 'WPINC' ) ) {
	die;
}

interface ILavBoostModule {

	public function run( $args = '' );
	public function getSettings(): array;

}
