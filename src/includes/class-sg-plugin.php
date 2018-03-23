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
if ( ! class_exists( 'SG_Plugin' ) ) :

	class SG_Plugin extends ShopgatePlugin {

		/** @var SG_Import */
		private $import_service;
		/** @var SG_Export */
		private $export_service;
		/** @var SG_Settings */
		private $settings_service;

		/**
		 * @return SG_Import
		 */
		public function get_import_service() {
			return $this->import_service;
		}

		/**
		 * @param SG_Import $import_service
		 */
		public function set_import_service( $import_service ) {
			$this->import_service = $import_service;
		}

		/**
		 * @return SG_Export
		 */
		public function get_export_service() {
			return $this->export_service;
		}

		/**
		 * @param SG_Export $export_service
		 */
		public function set_export_service( $export_service ) {
			$this->export_service = $export_service;
		}

		/**
		 * @return SG_Settings
		 */
		public function get_settings_service() {
			return $this->settings_service;
		}

		/**
		 * @param SG_Settings $settings_service
		 */
		public function set_settings_service( $settings_service ) {
			$this->settings_service = $settings_service;
		}

		public function startup() {
			$this->set_export_service( new SG_Export() );
			$this->set_import_service( new SG_Import() );
			$this->set_settings_service( new SG_Settings() );
		}

		/**
		 * @inheritdoc
		 */
		public function cron( $jobname, $params, &$message, &$errorcount ) {
			// TODO: Implement cron() method.
		}

		/**
		 * @inheritdoc
		 */
		public function getCustomer( $user, $pass ) {
			return $this->get_export_service()->get_customer( $user, $pass );
		}

		/**
		 * @inheritdoc
		 */
		public function registerCustomer( $user, $pass, ShopgateCustomer $customer ) {
			return $this->get_import_service()->register_customer( $user, $pass, $customer );
		}

		/**
		 * @inheritdoc
		 */
		public function addOrder( ShopgateOrder $order ) {
			return $this->get_import_service()->add_order( $order );
		}

		/**
		 * @inheritdoc
		 */
		public function updateOrder( ShopgateOrder $order ) {
			return $this->get_import_service()->update_order( $order );
		}

		/**
		 * @inheritdoc
		 */
		public function checkCart( ShopgateCart $cart ) {
			return $this->get_export_service()->check_cart( $cart );
		}

		/**
		 * @inheritdoc
		 */
		public function checkStock( ShopgateCart $cart ) {
			// TODO: Implement checkStock() method.
		}

		/**
		 * @inheritdoc
		 */
		public function getSettings() {
			return $this->get_settings_service()->get_settings();
		}

		/**
		 * @inheritdoc
		 */
		public function getOrders(
			$customerToken,
			$customerLanguage,
			$limit = 10,
			$offset = 0,
			$orderDateFrom = '',
			$sortOrder = 'created_desc'
		) {
			// TODO: Implement getOrders() method.
		}

		/**
		 * @inheritdoc
		 */
		public function syncFavouriteList( $customerToken, $items ) {
			// TODO: Implement syncFavouriteList() method.
		}

		/**
		 * @inheritdoc
		 */
		protected function createMediaCsv() {
			// TODO: Implement createMediaCsv() method.
		}

		/**
		 * @inheritdoc
		 */
		protected function createItems( $limit = null, $offset = null, array $uids = [] ) {
			if ( $this->splittedExport ) {
				$limit  = is_null( $limit ) ? $this->exportLimit : $limit;
				$offset = is_null( $offset ) ? $this->exportOffset : $offset;
			}

			$items = $this->get_export_service()->get_items( $limit, $offset, $uids );

			foreach ( $items as $item ) {
				$this->addItemModel( $item );
			}

			return $items;
		}

		/**
		 * @inheritdoc
		 */
		protected function createCategories( $limit = null, $offset = null, array $uids = [] ) {
			if ( $this->splittedExport ) {
				$limit  = is_null( $limit ) ? $this->exportLimit : $limit;
				$offset = is_null( $offset ) ? $this->exportOffset : $offset;
			}

			$categories = $this->get_export_service()->get_categories( $limit, $offset, $uids );

			foreach ( $categories as $category ) {
				$this->addCategoryModel( $category );
			}

			return $categories;
		}

		/**
		 * @inheritdoc
		 */
		protected function createReviews( $limit = null, $offset = null, array $uids = [] ) {
			// TODO: Implement createReviews() method.
		}
	}
endif;
