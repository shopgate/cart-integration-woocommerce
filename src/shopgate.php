<?php
/**
 * Plugin Name: Shopgate Connector
 */

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

if ( ! class_exists( 'WC_Integration_Shopgate' ) ) :
	final class WC_Integration_Shopgate {
		/**
		 * Shopgate plugin version.
		 *
		 * @var string
		 */
		public $version = '2.9.0';
		/** @var ShopgateBuilder | null */
		private $builder = null;
		/** @var WC_Integration_Shopgate_Integration | null */
		private $config = null;

		public function __construct() {
			$this->define_constants();
			$this->init_hooks();
		}

		/**
		 * Define SG Constants.
		 */
		private function define_constants() {
			$this->define( 'SG_VERSION', $this->version );
		}

		private function init_hooks() {
			add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
			add_action( 'wp_head', array( $this, 'mobile_redirect' ) ); //woocommerce_before_main_content
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_update' ) );
			// run installer
			include_once( __DIR__ . '/includes/class-sg-install.php' );
			register_activation_hook( __FILE__, array( 'SG_Install', 'install' ) );
		}

		public function init_plugin() {
			if ( class_exists( 'WC_Integration' ) ) {
				include_once( __DIR__ . '/includes/class-sg-integration.php' );
				include_once( __DIR__ . '/vendor/autoload.php' );
				add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
			}
		}

		/**
		 * @param $orderId
		 */
		public function order_status_update( $orderId ) {
			$order = WC()->order_factory->get_order( $orderId );
			$post  = $order->post;

			if ( strstr( $post->post_excerpt, "Shopgate" ) == false ) {
				return;
			}
			$parts           = explode( " ", $post->post_excerpt );
			$shopgateOrderId = array_pop( $parts );
			$merchantApi     = $this->get_merchant_api();
			switch ( $order->post_status ) {
				case "wc-cancelled" :
					try {
						//$merchantApi->cancelOrder($shopgateOrderId);
						apply_filters( 'woocommerce_add_message', "Order cancelled at Shopgate" );
					} catch ( Exception $e ) {
						apply_filters( 'woocommerce_add_error', "Order was not cancelled at Shopgate" );

						return;
					}
					break;
				case "wc-completed" :
					try {
						//$merchantApi->setOrderShippingCompleted($shopgateOrderId);
						apply_filters( 'woocommerce_add_message', "Order was marked as shipped at Shopgate" );
					} catch ( Exception $e ) {
						apply_filters( 'woocommerce_add_error', "Order was not marked as shipped at Shopgate" );

						return;
					}
					break;
				default :
					break;
			}
		}

		/**
		 * Prints the redirect script on frontend pages
		 */
		public function mobile_redirect() {
			$wooConfig = $this->get_woo_config();
			if ( ! $wooConfig->is_valid() || is_customize_preview() ) {
				return;
			}
			$mobileRedirect = $this->get_mobile_redirect();
			if ( is_product_category() ) {
				$category = get_queried_object();
				$script   = $mobileRedirect->buildScriptCategory( $category->term_id );
			} elseif ( is_product() ) {
				$productId = wc_get_product()->id;
				$script    = $mobileRedirect->buildScriptItem( $productId );
			} elseif ( is_front_page() ) {
				$script = $mobileRedirect->buildScriptShop();
			} else {
				$script = $mobileRedirect->buildScriptDefault();
			}

			echo $script;
		}

		/**
		 * @return WC_Integration_Shopgate_Integration
		 */
		protected function get_woo_config() {
			if ( null === $this->config ) {
				$this->config = new WC_Integration_Shopgate_Integration();
			}

			return $this->config;
		}

		/**
		 * @return ShopgateConfig
		 */
		protected function get_config() {
			$wooConfiguration      = $this->get_woo_config();
			$shopgateConfiguration = new ShopgateConfig();
			$shopgateConfiguration->setApikey( $wooConfiguration->api_key );
			$shopgateConfiguration->setCustomerNumber( $wooConfiguration->customer_number );
			$shopgateConfiguration->setShopNumber( $wooConfiguration->shop_number );
			$shopgateConfiguration->setShopIsActive( $wooConfiguration->enable_module );
			$shopgateConfiguration->setAlias( $wooConfiguration->alias );
			$shopgateConfiguration->setCname( $wooConfiguration->cname );
			$shopgateConfiguration->setServer( $wooConfiguration->server );

			return $shopgateConfiguration;
		}

		/**
		 * @return ShopgateBuilder
		 */
		protected function get_builder() {
			if ( null === $this->builder ) {
				$this->builder = new ShopgateBuilder( $this->get_config() );
			}

			return $this->builder;
		}

		/**
		 * @return ShopgateMerchantApi
		 */
		public function get_merchant_api() {
			$builder = $this->get_builder();

			return $builder->buildMerchantApi();
		}

		/**
		 * @return Shopgate_Helper_Redirect_MobileRedirect
		 */
		public function get_mobile_redirect() {
			$builder = $this->get_builder();

			return $builder->buildMobileRedirect( $_SERVER['HTTP_USER_AGENT'], $_GET, $_COOKIE );
		}

		/**
		 * @param $integrations
		 *
		 * @return array
		 */
		public function add_integration( $integrations ) {
			$integrations[] = 'WC_Integration_Shopgate_Integration';

			return $integrations;
		}

		/**
		 * @param $name
		 * @param $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
	}

	// Global for backwards compatibility.
	$GLOBALS['shopgate'] = new WC_Integration_Shopgate( __FILE__ );
endif;
