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
if ( ! class_exists( 'SG_Export_Categories_Xml' ) ) :

	class SG_Export_Categories_Xml extends Shopgate_Model_Catalog_Category {

		/** @var WP_Term */
		protected $item;

		/**
		 * Set category id
		 */
		public function setUid() {
			parent::setUid( $this->item->term_id );
		}

		/**
		 * Set category sort order
		 */
		public function setSortOrder() {
			// TODO: implement real sortorder
			parent::setSortOrder( 0 );
		}

		/**
		 * Set category name
		 */
		public function setName() {
			parent::setName( $this->item->name );
		}

		/**
		 * Set parent category id
		 */
		public function setParentUid() {
			parent::setParentUid( $this->item->parent != 0 ? $this->item->parent : null );
		}

		/**
		 * Category link in shop
		 */
		public function setDeeplink() {
			parent::setDeeplink( get_term_link( $this->item->slug, SG_Retriever_Terms::TERM_TAXONOMY_CATEGORIES ) );
		}

		/**
		 * Check if category is anchor
		 */
		public function setIsAnchor() {
			parent::setIsAnchor( 1 );
		}

		/**
		 * Check if category is anchor
		 */
		public function setIsActive() {
			parent::setIsActive( $this->item->count > 0 ? 1 : 0 );
		}
	}
endif;
