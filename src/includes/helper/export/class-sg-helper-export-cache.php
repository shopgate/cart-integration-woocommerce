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
if ( ! class_exists( 'SG_Helper_Export_Cache' ) ) :
	class SG_Helper_Export_Cache {

		const CACHE_KEY_CATEGORY_PRODUCT_SORTING = 'categories_product_sort_';

		/**
		 * Cache that can be used during export processes
		 *
		 * @var array
		 */
		protected $export_cache = array();

		/**
		 * @param int $product_id
		 * @param int $category_id
		 *
		 * @return int
		 */
		public function get_product_order_index( $product_id, $category_id ) {
			$cache_key = self::CACHE_KEY_CATEGORY_PRODUCT_SORTING . $category_id;

			if ( empty( $this->export_cache[ $cache_key ] ) ) {
				ShopgateLogger::getInstance()
				              ->log( "Start creating Cache {$cache_key}", ShopgateLogger::LOGTYPE_DEBUG );

				$products = $this->get_all_products_by_category_id( $category_id );

				$i       = 0;
				$maxSort = count( $products ) + 1;
				foreach ( $products as $product_id ) {
					$this->export_cache[ $cache_key ][ $product_id ] = $maxSort - $i ++;
				}

				ShopgateLogger::getInstance()->log(
					"Created Cache {$cache_key} with " .
					count( $this->export_cache[ $cache_key ] ) . " entries",
					ShopgateLogger::LOGTYPE_DEBUG
				);
			}

			return ! empty( $this->export_cache[ $cache_key ][ $product_id ] )
				? $this->export_cache[ $cache_key ][ $product_id ]
				: 0;
		}

		/**
		 * @param int $category_id
		 *
		 * @return array
		 */
		protected function get_all_products_by_category_id( $category_id ) {

			$category_products = array();
			$query             = new WC_Query();
			$filter_args       = $query->get_catalog_ordering_args();

			$args = array(
				'post_type'   => 'product',
				'numberposts' => - 1,
				'orderby'     => $filter_args['orderby'],
				'order'       => $filter_args['order'],
				'tax_query'   => array(
					array(
						'taxonomy' => 'product_cat',
						'terms'    => $category_id,
						'operator' => 'IN',
					),
				),
			);

			$products = get_posts( $args );

			/** @var WP_Post $product */
			foreach ( $products as $product ) {
				$category_products[] = $product->ID;
			}

			return $category_products;
		}
	}
endif;
