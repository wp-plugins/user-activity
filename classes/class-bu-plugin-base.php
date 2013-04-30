<?php
if ( ! class_exists( 'Bu_Plugin_Base' ) ) {
	/**
	 * Boilerplate class for setting up plugin classes
	 * When creating new plugin classes, extend this class and implement _setup as a constructor.
	 * No need for unused instance variables polluting the global namespace anymore.
	 * Change old syntax $dummy_variable = new My_Plugin_Class();
	 * to
	 * My_Plugin_Class::Init();
	 *
	 * @package default
	 */
	abstract class Bu_Plugin_Base {
		private static $reg = array();

		public static function init() {
			add_action( 'plugins_loaded', array( static::instance(), '_setup' ) );
		}

		public static function instance() {
			$cls = get_called_class();
			!isset( self::$reg[$cls] ) && self::$reg[$cls] = new $cls;
			return self::$reg[$cls];
		}

		abstract public function _setup();
	}
}