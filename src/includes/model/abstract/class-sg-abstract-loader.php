<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'SG_Abstract_Loader' ) ) :

	class SG_Abstract_Loader {

		/**
		 * Traverses provided method array and calls the
		 * functions on the implemented class
		 *
		 * @param array $methods
		 *
		 * @return array
		 */
		protected function load_methods( $methods ) {
			foreach ( $methods as $index => $method ) {
				$method_name = 'set_' . $method;
				if ( method_exists( $this, $method_name ) ) {
					ShopgateLogger::getInstance()->log( 'Starting method ' . $method_name,
						ShopgateLogger::LOGTYPE_DEBUG );
					$methods[ $method ] = $this->{$method_name}();
					ShopgateLogger::getInstance()->log( 'Finished method ' . $method_name,
						ShopgateLogger::LOGTYPE_DEBUG );
					unset( $methods[ $index ] );
				}
			}

			return $methods;
		}
	}
endif;
