<?php
namespace traits;
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
trait TLavBoostSingleton {
	protected static $instance;
	public static function getInstance() {
		return isset(static::$instance)
			? static::$instance
			: static::$instance = new static;
	}
	private function __construct() {
		$this->init();
	}
	protected function init() {}
}
