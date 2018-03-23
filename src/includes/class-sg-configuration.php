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
if ( ! class_exists( 'SG_Configuration' ) ) :
	include_once( __DIR__ . '/../vendor/autoload.php' );

	class SG_Configuration extends ShopgateConfig {

		public function startup() {
			$pluginConfiguration = new WC_Integration_Shopgate_Integration();

			$this->setApikey( $pluginConfiguration->api_key );
			$this->setCustomerNumber( $pluginConfiguration->customer_number );
			$this->setShopNumber( $pluginConfiguration->shop_number );
			$this->setServer( $pluginConfiguration->server );
			$this->setApiUrl( $pluginConfiguration->api_url );
			$this->setCname( $pluginConfiguration->cname );
			$this->setAlias( $pluginConfiguration->alias );
			$this->setPluginName( 'Shopgate' );
			$this->setEnableGetCategories( 1 );
			$this->setEnableGetReviews( 0 );
			$this->setEnableGetItems( 1 );
			$this->setEnableGetCustomer( 1 );
			$this->setEnableRegisterCustomer( 1 );
			$this->setEnableAddOrder( 1 );
			$this->setEnableUpdateOrder( 0 );
			$this->setEnableGetOrders( 0 );
			$this->setEnableGetSettings( 1 );
			$this->setEnableCron( 0 );
			$this->setEnableCheckStock( 0 );
			$this->setEnableCheckCart( 1 );
			$this->setSupportedFieldsCheckCart( SG_Export_Cart::$supported_methods );
			$this->setSupportedFieldsGetSettings( SG_Settings::$supported_methods );

			if ( ! file_exists( $this->getExportFolderPath() ) ) {
				@mkdir( $this->getExportFolderPath(), 0777, true );
			}
			if ( ! file_exists( $this->getLogFolderPath() ) ) {
				@mkdir( $this->getLogFolderPath(), 0777, true );
			}
			if ( ! file_exists( $this->getCacheFolderPath() ) ) {
				@mkdir( $this->getCacheFolderPath(), 0777, true );
			}
		}
	}
endif;
