<?php

namespace abstracts;
use CSF;
use interfaces\ILavBoostModule;

if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class LavBoostModule implements ILavBoostModule {

	abstract function run( $args = '' );
	abstract function getFields(): array;

	public function createSettingsTab(){
		if(class_exists( 'CSF' )){
			CSF::createSection(LAV_BOOST_PREFIX, $this->getFields());
		}
	}

	public function getSettings(): array {
		if ( !get_option( LAV_BOOST_PREFIX ) ) {
			return array();
		} else {
			return get_option( LAV_BOOST_PREFIX );
		}
	}
	public function getValue($key) {
		return ! empty( $this->getSettings()[ $key ] ) && is_array($this->getSettings())
			? $this->getSettings()[ $key ]
			: null;
	}

	public function dd($mixed = null) {
		echo '<pre>';
		var_dump($mixed);
		echo '</pre>';
		return null;
	}

	public function getAllowedTags() {
		return [
			'b'      => [],
			's'      => [],
			'strong' => [],
			'i'      => [],
			'u'      => [],
			'br'     => [],
			'em'     => [],
			'del'    => [],
			'ins'    => [],
			'sup'    => [],
			'sub'    => [],
			'code'   => [],
			'small'  => [],
			'strike' => [],
			'abbr'   => [
				'title' => [],
			],
			'div'    => [
				'class'    => [],
				'data-tab' => [],
			],
			'li'     => [
				'class'    => [],
				'data-tab' => [],
			],
			'span'   => [
				'class' => [],
			],
			'a'      => [
				'href'  => [],
				'title' => [],
				'class' => [],
				'id'    => [],
			],
			'img'    => [
				'src'    => [],
				'alt'    => [],
				'height' => [],
				'width'  => [],
			],
			'hr'     => [],
			'h1'     => [],
			'id'     => [],
		];
	}
}
