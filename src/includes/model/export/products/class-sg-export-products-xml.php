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
if ( ! class_exists( 'SG_Export_Products_Xml' ) ) :

	class SG_Export_Products_Xml extends Shopgate_Model_Catalog_Product {

		CONST DEFAULT_TAX_CLASS = 'standard';

		/** @var WC_Product|WC_Product_Variation */
		protected $item;
		/** @var SG_Helper_Export_Cache */
		protected $cache;
		/** @var array */
		protected $variation_data = array();
		/** @var array */
		protected $fireMethods = array(
			'setUid',
			'setName',
			'setTaxPercent',
			'setTaxClass',
			'setDescription',
			'setDeeplink',
			'setWeight',
			'setPrice',
			'setCurrency',
			'setVisibility',
			'setStock',
			'setImages',
			'setCategoryPaths',
			'setProperties',
			'setIdentifiers',
			'setRelations',
			'setAttributeGroups',
			'setChildren',
			'setDisplayType',
		);
		/** @var array */
		protected $fireMethodsChildren = array(
			'setUid',
			'setName',
			'setTaxPercent',
			'setTaxClass',
			'setDescription',
			'setDeeplink',
			'setWeight',
			'setPrice',
			'setVisibility',
			'setStock',
			'setChildImages',
			'setProperties',
			'setIdentifiers',
			'setRelations',
			'setAttributes',
			'setInputs',
		);

		/**
		 * @param SG_Helper_Export_Cache $cache
		 */
		public function set_cache( SG_Helper_Export_Cache $cache ) {
			$this->cache = $cache;
		}

		/**
		 * @return SG_Helper_Export_Cache
		 */
		public function get_cache() {
			return $this->cache;
		}

		public function set_variation_data( $variation_data ) {
			$this->variation_data = $variation_data;
		}

		public function setLastUpdate() {
			parent::setLastUpdate( $this->item->post->post_modified );
		}

		public function setUid() {
			parent::setUid( $this->item->get_id() );
		}

		public function setName() {
			parent::setName( $this->item->get_title() );
		}

		public function setTaxClass() {
			$item_tax_class = $this->item->get_tax_class();
			$tax_class      = empty( $item_tax_class )
				? self::DEFAULT_TAX_CLASS
				: $item_tax_class;
			parent::setTaxClass( $tax_class );
		}

		public function setIdentifiers() {
			$identifier = new Shopgate_Model_Catalog_Identifier();
			$identifier->setType( 'SKU' );
			$identifier->setValue( $this->item->get_sku() );

			parent::setIdentifiers( array( $identifier ) );
		}

		public function setDescription() {
			$plugin_configuration = new WC_Integration_Shopgate_Integration();

			switch ( $plugin_configuration->export_description ) {
				case WC_Integration_Shopgate_Integration::EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC:
					$description = $this->item->post->post_excerpt;
					break;
				case WC_Integration_Shopgate_Integration::EXPORT_PRODUCT_DESCRIPTION_DESC_AND_SHORT_DESC:
					$description = $this->item->post->post_content;
					$description .= '<br /><br />';
					$description .= $this->item->post->post_excerpt;
					break;
				case WC_Integration_Shopgate_Integration::EXPORT_PRODUCT_DESCRIPTION_SHORT_DESC_AND_DESC:
					$description = $this->item->post->post_excerpt;
					$description .= '<br /><br />';
					$description .= $this->item->post->post_content;
					break;
				default:
					$description = $this->item->post->post_content;
			}

			parent::setDescription( $description );
		}

		public function setDeeplink() {
			parent::setDeeplink( $this->item->get_permalink() );
		}

		public function setWeight() {
			parent::setWeight( str_replace( ',', '.', $this->item->get_weight() ) );
		}

		public function setVisibility() {
			$visibility = new Shopgate_Model_Catalog_Visibility();
			if ( $this->item->is_visible() != 1 ) {
				$visibility->setLevel( Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_NOT_VISIBLE );
			} else {
				$visibility->setLevel( Shopgate_Model_Catalog_Visibility::DEFAULT_VISIBILITY_CATALOG_AND_SEARCH );
			}

			parent::setVisibility( $visibility );
		}

		public function setImages() {

			$images     = array();
			$main_image = wp_get_attachment_image_src(
				get_post_thumbnail_id( $this->item->get_id() ),
				'single-post-thumbnail'
			);

			if ( ! empty( $main_image ) ) {
				$image = new Shopgate_Model_Media_Image();
				$image->setUrl( $main_image[0] );
				$images[] = $image;
			}

			foreach ( $this->item->get_gallery_attachment_ids() as $attachment_id ) {
				$image = new Shopgate_Model_Media_Image();
				$image->setUid( $attachment_id );
				$image->setUrl( wp_get_attachment_url( $attachment_id ) );

				$images[] = $image;
			}

			parent::setImages( $images );
		}

		public function setChildImages() {
			$images = array();
			$image  = new Shopgate_Model_Media_Image();
			$image->setUid( $this->item->get_image_id() );
			$image->setUrl( wp_get_attachment_url( $this->item->get_image_id() ) );

			$images[] = $image;

			parent::setImages( $images );
		}

		public function setPrice() {
			$priceModel = new Shopgate_Model_Catalog_Price();

			if ( get_option( 'woocommerce_tax_display_shop' ) == 'incl' ) {
				$price        = $this->item->get_price_including_tax();
				$regularPrice = $this->item->get_price_including_tax( 1, $this->item->get_regular_price() );
				$salePrice    = $this->item->get_price_including_tax( 1, $this->item->get_sale_price() );
				$priceModel->setType( Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_GROSS );
			} else {
				$price        = $this->item->get_price_excluding_tax();
				$regularPrice = $this->item->get_price_excluding_tax( 1, $this->item->get_regular_price() );
				$salePrice    = $this->item->get_price_excluding_tax( 1, $this->item->get_sale_price() );
				$priceModel->setType( Shopgate_Model_Catalog_Price::DEFAULT_PRICE_TYPE_NET );
			}

			if ( $this->item->is_on_sale() == 1 ) {
				$priceModel->setSalePrice( $salePrice );
			}

			$priceModel->setPrice( ! empty( $regularPrice ) ? $regularPrice : $price );

			parent::setPrice( $priceModel );
		}

		public function setCurrency() {
			parent::setCurrency( get_woocommerce_currency() );
		}

		public function setCategoryPaths() {
			$categories = array();
			$term_list  = get_the_terms( $this->item->get_id(), SG_Retriever_Terms::TERM_TAXONOMY_CATEGORIES );

			/** @var WP_Term $woocommerce_category */
			foreach ( $term_list as $woocommerce_category ) {

				$category_path = new Shopgate_Model_Catalog_CategoryPath();
				$category_path->setUid( $woocommerce_category->term_id );
				$category_path->setSortOrder(
					$this->cache->get_product_order_index( $this->item->get_id(), $woocommerce_category->term_id )
				);

				$categories[] = $category_path;
			}

			parent::setCategoryPaths( $categories );
		}

		public function setDisplayType() {

			$type = empty( $this->variation_data )
				? 'simple'
				: 'select';

			parent::setDisplayType( $type );
		}

		public function setShipping() {

			$shipping = new Shopgate_Model_Catalog_Shipping();
			$shipping->setAdditionalCostsPerUnit( false );
			$shipping->setCostsPerOrder( false );
			$shipping->setIsFree( false );

			parent::setShipping( $shipping );
		}

		public function setStock() {

			$is_saleable = $this->item->is_in_stock() && $this->item->is_purchasable();

			$stock = new Shopgate_Model_Catalog_Stock();
			$stock->setBackorders( $this->item->is_on_backorder() );
			$stock->setUseStock( $this->item->managing_stock() );
			$stock->setIsSaleable( $is_saleable );
			$stock->setStockQuantity( $this->item->get_stock_quantity() );

			parent::setStock( $stock );
		}

		public function setAttributeGroups() {

			if ( ! empty( $this->variation_data ) ) {

				$attribute_groups = array();
				foreach ( $this->item->get_attributes() as $woo_attribute ) {

					if ( $woo_attribute['is_variation'] == 0 ) {
						continue;
					}

					$attribute = new Shopgate_Model_Catalog_AttributeGroup();
					$attribute->setUid( bin2hex( $woo_attribute['name'] ) );
					$attribute->setLabel( wc_attribute_label( $woo_attribute['name'] ) );

					$attribute_groups[] = $attribute;
				}

				parent::setAttributeGroups( $attribute_groups );
			}
		}

		public function setAttributes() {
			$attributes = array();
			foreach ( $this->item->get_variation_attributes() as $name => $value ) {
				$name      = str_replace( 'attribute_', '', $name );
				$attribute = new Shopgate_Model_Catalog_Attribute();
				$attribute->setUid( bin2hex( $name ) . '_' . bin2hex( $value ) );
				$attribute->setGroupUid( bin2hex( $name ) );
				$attribute->setLabel( $value );

				$attributes[] = $attribute;
			}
			parent::setAttributes( $attributes );
		}

		public function setChildren() {
			$children = array();

			foreach ( $this->variation_data as $variant ) {
				$child = new SG_Export_Products_Xml();
				$child->setItem( $variant );
				$child->fireMethods = $this->fireMethodsChildren;
				$child->setIsChild( 1 );

				$children[] = $child->generateData();
			}

			parent::setChildren( $children );
		}
	}
endif;
