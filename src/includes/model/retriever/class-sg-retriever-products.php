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
if ( ! class_exists( 'SG_Retriever_Products' ) ) :

	class SG_Retriever_Products {

		const TERM_TAXONOMY_PRODUCTS     = 'product_type';
		const SLUG_PRODUCT_TYPE_SIMPLE   = 'simple';
		const SLUG_PRODUCT_TYPE_GROUPED  = 'grouped';
		const SLUG_PRODUCT_TYPE_VARIABLE = 'variable';
		const SLUG_PRODUCT_TYPE_EXTERNAL = 'external';

		protected $supported_product_types = array( 'product' );

		/**
		 * @param int   $limit
		 * @param int   $offset
		 * @param array $uids
		 *
		 * @return WC_Product[]
		 */
		public function get_items( $limit, $offset, $uids ) {

			$product_list = array();
			$filter       = array(
				'post_type'      => $this->supported_product_types,
				'posts_per_page' => - 1
			);

			$filter = $limit !== null && $offset !== null
				? array_merge( $filter, array( 'posts_per_page' => $limit, 'offset' => $offset, 'nopaging' => false ) )
				: $filter;

			$result = new WP_Query( $filter );

			while ( $result->have_posts() ) {
				$result->the_post();
				$product_id     = get_the_ID();
				$product_list[] = new WC_Product( $product_id );
			}

			return $product_list;
		}

		/**
		 * @param int $product_id
		 *
		 * @return array
		 */
		public function get_children( $product_id ) {
			$args       = array(
				'post_type'   => 'product_variation',
				'numberposts' => - 1,
				'orderby'     => 'menu_order',
				'order'       => 'asc',
				'post_parent' => $product_id
			);
			$variations = get_posts( $args );
			$children   = array();
			foreach ( $variations as $variation ) {
				$variation_ID = $variation->ID;
				$children[]   = new WC_Product_Variation( $variation_ID );
			}

			return $children;
		}
	}
endif;
