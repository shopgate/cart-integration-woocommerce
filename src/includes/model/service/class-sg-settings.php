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
if ( ! class_exists( 'SG_Settings' ) ) :

	class SG_Settings extends SG_Abstract_Loader {

		CONST OPTION_PRODUCT_TAX_CLASSES = 'woocommerce_tax_classes';
		CONST DEFAULT_TAX_CLASS_NAME     = 'Standard Rate';
		CONST DEFAULT_TAX_CLASS_KEY      = 'standard';

		public static $supported_methods = array( 'tax', 'customer_groups' );

		/**
		 * @inheritdoc
		 */
		public function get_settings() {
			return $this->load_methods( self::$supported_methods );
		}

		public function set_tax() {
			$tax_rates            = $this->get_tax_rates();
			$product_tax_classes  = $this->get_product_tax_classes();
			$customer_tax_classes = $this->get_customer_tax_classes();
			$tax_rules            =
				$this->calculate_tax_rules( $product_tax_classes, $tax_rates, $customer_tax_classes );

			return array(
				'tax_rates'            => $tax_rates,
				'tax_rules'            => $tax_rules,
				'product_tax_classes'  => $product_tax_classes,
				'customer_tax_classes' => $customer_tax_classes,
			);
		}

		/**
		 * @param array $product_tax_classes
		 * @param array $tax_rates
		 * @param array $customer_tax_classes
		 *
		 * @return array
		 */
		private function calculate_tax_rules( $product_tax_classes, $tax_rates, $customer_tax_classes ) {
			$result = array();
			foreach ( $product_tax_classes as $product_tax_class ) {
				$tax_rule                          = array();
				$tax_rule['id']                    = $product_tax_class['id'];
				$tax_rule['name']                  = is_null( $product_tax_class['key'] )
					? self::DEFAULT_TAX_CLASS_NAME
					: $product_tax_class['key'];
				$tax_rule['product_tax_classes'][] = $product_tax_class;
				$tax_rule['customer_tax_classes']  = $customer_tax_classes;
				$tax_rule['tax_rates']             = array();
				$tax_rule['priority']              = 0;
				foreach ( $tax_rates as $tax_rate ) {
					if ( substr( $tax_rate['key'], 0, strlen( $product_tax_class['id'] ) )
					     !== $product_tax_class['id']
					) {
						continue;
					}
					$tax_rule['tax_rates'][] = array(
						'id'  => $tax_rate['id'],
						'key' => $tax_rate['key']
					);
				}
				$result[] = $tax_rule;
			}

			return $result;
		}

		/**
		 * @return array
		 */
		private function get_customer_tax_classes() {
			return array(
				array(
					'key'        => 'default',
					'id'         => 'default',
					'is_default' => '1'
				)
			);
		}

		/**
		 * @return array
		 */
		public function set_customer_groups() {
			return array(
				array(
					'key'        => 'default',
					'id'         => 'default',
					'is_default' => '1'
				)
			);
		}

		/**
		 * @return array
		 */
		private function get_product_tax_classes() {
			$result = array(
				array(
					'id'  => self::DEFAULT_TAX_CLASS_KEY,
					'key' => __( self::DEFAULT_TAX_CLASS_NAME, 'woocommerce' ),
				)
			);

			$found_classes =
				array_filter( array_map( 'trim', explode( "\n", get_option( self::OPTION_PRODUCT_TAX_CLASSES ) ) ) );

			foreach ( $found_classes as $class ) {
				$taxClass        = array();
				$taxClass['id']  = sanitize_title( $class );
				$taxClass['key'] = $class;

				$result[] = $taxClass;
			}

			return $result;
		}

		/**
		 * @return array
		 */
		private function get_tax_rates() {
			global $wpdb;
			$result = array();

			$found_rates = $wpdb->get_results( "
        SELECT tr.*, l.location_code, l.location_type
        FROM {$wpdb->prefix}woocommerce_tax_rates as tr
          LEFT JOIN {$wpdb->prefix}woocommerce_tax_rate_locations as l ON l.tax_rate_id = tr.tax_rate_id
    " );

			foreach ( $found_rates as $rate ) {
				switch ( $rate->location_type ) {
					case 'postcode':
						$zipcode_pattern = $rate->location_code;
						break;
					case 'city':
					default:
						$zipcode_pattern = '*';
						break;
				}

				$tax_rate_class = empty( $rate->tax_rate_class ) ? 'standard' : $rate->tax_rate_class;

				$tax_rate                 = array();
				$tax_rate['id']           = $rate->tax_rate_id;
				$tax_rate['key']          =
					$tax_rate_class . '_' . $rate->tax_rate_country . '_' . $rate->tax_rate_state . '_'
					. $rate->tax_rate_id;
				$tax_rate['display_name'] = $rate->tax_rate_name;
				$tax_rate['tax_percent']  = $rate->tax_rate;
				$tax_rate['country']      = $rate->tax_rate_country;
				$tax_rate['state']        = $rate->tax_rate_state;
				// all, range, pattern
				$tax_rate['zipcode_type']    = 'pattern';
				$tax_rate['zipcode_pattern'] = $zipcode_pattern;
				$result[]                    = $tax_rate;
			}

			return $result;
		}
	}
endif;
