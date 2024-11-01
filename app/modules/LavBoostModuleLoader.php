<?php
namespace modules;
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
use interfaces\ILavBoostModule;

class LavBoostModuleLoader {

	public static function register() {

		foreach (glob(LAV_BOOST_MODULES . '/*/*.php') as $filename) {

			if (file_exists($filename)) {
				require $filename;
				$moduleClass = basename($filename, ".php");
				if(class_exists($moduleClass) && method_exists($moduleClass,'getInstance')){
					$obj = $moduleClass::getInstance();
					if ( $obj instanceof ILavBoostModule) {
						$obj->run();
					}else{
						trigger_error($moduleClass . ' module must implement ILavBoostModule interface', E_USER_WARNING);
					}
				}
			}

		}
	}
}
