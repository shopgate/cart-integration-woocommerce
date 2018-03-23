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
if ( ! class_exists( 'SG_Retriever_Terms' ) ) :

	class SG_Retriever_Terms {

		const TERM_TAXONOMY_CATEGORIES = 'product_cat';

		/**
		 * @param int   $limit
		 * @param int   $offset
		 * @param array $uids
		 *
		 * @return WP_Term[]
		 */
		public function get_categories( $limit, $offset, $uids ) {
			global $wp_version;

			return version_compare( $wp_version, '4.5.0', '>=' )
				? get_terms( array( 'taxonomy' => self::TERM_TAXONOMY_CATEGORIES ) )
				: get_terms( self::TERM_TAXONOMY_CATEGORIES );
		}
	}
endif;
