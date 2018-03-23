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
if ( ! class_exists( 'SG_Install' ) ) :

	class SG_Install {

		/**
		 * Install Shopgate related tables
		 */
		public static function install() {
			global $wpdb;

			$table_name_shopgate_order    = $wpdb->prefix . 'shopgate_order';
			$table_name_shopgate_customer = $wpdb->prefix . 'shopgate_customer';

			$charset_collate = $wpdb->get_charset_collate();

			$sql_shopgate_order = "CREATE TABLE IF NOT EXISTS $table_name_shopgate_order (
                                `shopgate_order_id` int(11) NOT NULL AUTO_INCREMENT,
                                `order_id` int(11) NOT NULL,
                                `store_id` int(11) NOT NULL,
                                `shopgate_order_number` varchar(20) NOT NULL,
                                `is_shipping_blocked` int(1) NOT NULL DEFAULT '1',
                                `is_paid` int(1) NOT NULL DEFAULT '0',
                                `is_sent_to_shopgate` int(11) NOT NULL DEFAULT '0',
                                `is_cancellation_sent_to_shopgate` int(11) NOT NULL DEFAULT '0',
                                `is_test` int(11) NOT NULL DEFAULT '0',
                                `is_customer_invoice_blocked` int(11) NOT NULL DEFAULT '0',
                                `reported_shipping_collections` tinytext,
                                `received_data` mediumtext NOT NULL,
                                PRIMARY KEY (`shopgate_order_id`)
        ) ENGINE=MyISAM $charset_collate;";

			$sql_shopgate_customer = "CREATE TABLE IF NOT EXISTS $table_name_shopgate_customer (
                                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                `customer_id` int(10) unsigned NOT NULL,
                                `token` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
                                PRIMARY KEY (`id`)
        ) ENGINE=InnoDB $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql_shopgate_order );
			dbDelta( $sql_shopgate_customer );
		}
	}
endif;
