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


define( "_SHOPGATE_API", true );
define( "SHOPGATE_PLUGIN_VERSION", "2.9.0" );

require_once( __DIR__ . '/../../../../wp-load.php' );
require_once( __DIR__ . '/../../woocommerce/woocommerce.php' );

WooCommerce::instance()->includes();

// load library
require_once( __DIR__ . '/../vendor/autoload.php' );

//load plugin file
require_once( __DIR__ . '/../includes/model/abstract/class-sg-abstract-loader.php' );
require_once( __DIR__ . '/../includes/helper/class-sg-helper-item.php' );
require_once( __DIR__ . '/../includes/helper/export/class-sg-helper-export-cache.php' );
require_once( __DIR__ . '/../includes/model/import/class-sg-import-order.php' );
require_once( __DIR__ . '/../includes/model/import/class-sg-import-customer.php' );
require_once( __DIR__ . '/../includes/model/export/categories/class-sg-export-categories-xml.php' );
require_once( __DIR__ . '/../includes/model/export/products/class-sg-export-products-xml.php' );
require_once( __DIR__ . '/../includes/model/export/class-sg-export-cart.php' );
require_once( __DIR__ . '/../includes/model/retriever/class-sg-retriever-terms.php' );
require_once( __DIR__ . '/../includes/model/retriever/class-sg-retriever-products.php' );
require_once( __DIR__ . '/../includes/model/retriever/class-sg-retriever-customer.php' );
require_once( __DIR__ . '/../includes/model/service/class-sg-export.php' );
require_once( __DIR__ . '/../includes/model/service/class-sg-import.php' );
require_once( __DIR__ . '/../includes/model/service/class-sg-settings.php' );
require_once( __DIR__ . '/../includes/model/shopgate/class-sg-shopgate-order.php' );
require_once( __DIR__ . '/../includes/model/shopgate/class-sg-shopgate-customer.php' );
require_once( __DIR__ . '/../includes/class-sg-integration.php' );
require_once( __DIR__ . '/../includes/class-sg-configuration.php' );
require_once( __DIR__ . '/../includes/class-sg-plugin.php' );

$configuration = new SG_Configuration();
$plugin        = new SG_Plugin();
$builder       = new ShopgateBuilder( $configuration );

$builder->buildLibraryFor( $plugin );
$plugin->handleRequest( $_REQUEST );

