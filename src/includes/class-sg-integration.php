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

if ( ! class_exists( 'WC_Integration_Shopgate_Integration' ) ) :
	class WC_Integration_Shopgate_Integration extends WC_Integration {
		public $api_key;
		public $shop_number;
		public $customer_number;
		public $enable_module;
		public $server;
		public $api_url;
		public $export_description;
		public $cname;
		public $alias;

		const EXPORT_PRODUCT_DESCRIPTION_DESC                = 'desc';
		const EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC          = 'short_desc';
		const EXPORT_PRODUCT_DESCRIPTION_DESC_AND_SHORT_DESC = 'desc_and_short_desc';
		const EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC_AND_DESC = 'short_desc_and_desc';


		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->id                 = 'integration-shopgate';
			$this->method_title       = __( 'Shopgate Configuration', 'woocommerce-integration-shopgate' );
			$this->method_description = __(
				'Shopgate is a WooCommerce extension to easily create apps for your store. For more information visit <a href="http://www.shopgate.com" target="_blank">www.shopgate.com</a>',
				'woocommerce-integration-shopgate'
			);

			$this->init_form_fields();
			$this->init_settings();
			$this->api_key            = $this->get_option( 'api_key' );
			$this->shop_number        = $this->get_option( 'shop_number' );
			$this->customer_number    = $this->get_option( 'customer_number' );
			$this->enable_module      = $this->get_option( 'enable_module' );
			$this->api_url            = $this->get_option( 'api_url' );
			$this->server             = $this->get_option( 'server' );
			$this->export_description = $this->get_option( 'export_description' );
			$this->cname              = $this->get_option( 'cname' );
			$this->alias              = $this->get_option( 'alias' );

			add_action( 'woocommerce_update_options_integration_' . $this->id,
				array( $this, 'process_admin_options' ) );
		}

		/**
		 * @return bool
		 */
		public function is_valid() {
			if ( $this->enable_module != 'no'
			     && ! empty( $this->api_key )
			     && ! empty( $this->shop_number )
			     && ! empty( $this->customer_number )
			) {
				return true;
			}

			return false;
		}

		/**
		 * Initialize integration settings form fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enable_module'      => array(
					'title'   => __( 'Enable/Disable', 'woocommerce-integration-shopgate' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable Shopgate Module', 'woocommerce-integration-shopgate' ),
					'default' => 'no',
				),
				'api_key'            => array(
					'title'       => __( 'API Key', 'woocommerce-integration-shopgate' ),
					'type'        => 'text',
					'description' => __(
						'Enter with your Shopgate API Key'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'shop_number'        => array(
					'title'       => __( 'Shop Number', 'woocommerce-integration-shopgate' ),
					'type'        => 'text',
					'description' => __(
						'Enter with your Shopgate Shop Number'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'customer_number'    => array(
					'title'       => __( 'Customer Number', 'woocommerce-integration-shopgate' ),
					'type'        => 'text',
					'description' => __(
						'Enter with your Shopgate Customer Number'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'server'             => array(
					'title'   => __( 'Shopgate server', 'woocommerce-integration-shopgate' ),
					'default' => 'live',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => array(
						'live'   => __( 'Live', 'woocommerce-integration-shopgate' ),
						'pg'     => __( 'Playground', 'woocommerce-integration-shopgate' ),
						'custom' => __( 'Development', 'woocommerce-integration-shopgate' ),
					),
				),
				'api_url'            => array(
					'title'    => __( 'API URL', 'woocommerce-integration-shopgate' ),
					'type'     => 'text',
					'desc_tip' => true,
					'default'  => '',
				),
				'cname'              => array(
					'title'       => __( 'Custom URL to mobile webpage (CNAME) incl. http(s)://',
						'woocommerce-integration-shopgate' ),
					'type'        => 'text',
					'description' => __(
						'Enter a custom URL (defined by CNAME) for your mobile website. You can find the URL at the "Integration" section of your shop after you activated this option in the "Settings" => "Mobile website / webapp" section.'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'alias'              => array(
					'title'       => __( 'Shop alias', 'woocommerce-integration-shopgate' ),
					'type'        => 'text',
					'description' => __(
						'You can find the alias at the "Integration" section of your merchant area.'
					),
					'desc_tip'    => true,
					'default'     => '',
				),
				'export_description' => array(
					'title'   => __( 'Products description layout', 'woocommerce-integration-shopgate ' ),
					'default' => 'live',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'options' => array(
						self::EXPORT_PRODUCT_DESCRIPTION_DESC                => __(
							'Description',
							'woocommerce-integration-shopgate'
						),
						self::EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC          => __(
							'Short description',
							'woocommerce-integration-shopgate'
						),
						self::EXPORT_PRODUCT_DESCRIPTION_DESC_AND_SHORT_DESC => __(
							'Description + Short description',
							'woocommerce-integration-shopgate'
						),
						self::EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC_AND_DESC => __(
							'Short description + Description',
							'woocommerce-integration-shopgate'
						),
					),
				),
			);
		}
	}
endif;
