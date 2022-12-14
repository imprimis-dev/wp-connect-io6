<?php

$images_path = $upload_dir['basedir'] . '/io6-images/';

if (!wp_mkdir_p($images_path))
	throw new Exception('Cannot create image folder!');

$wp_categories_cache = array();
$wp_brands_cache = array();
$wp_suppliers_cache = array();
$wp_products_cache = array();

function syncCategories($categories = null)
{
	global $wpdb, $io6Engine, $wp_categories_cache;
	$rootLevel = false;
	if (!isset($categories)) {
		$categories = $io6Engine->GetIO6Categories();
    $rootLevel = true;
    $sql = "SELECT term_id, meta_value FROM $wpdb->termmeta 
    where meta_key='io6_category_code'";
		$results = $wpdb->get_results($wpdb->prepare($sql));
		foreach ($results as $row) {
			$wp_categories_cache[$row->meta_value] = intval($row->term_id);
		}
	}

	$wp_parentCategoryId = 0;

	foreach ($categories as $category) {
		if (!empty($category->parentCode))			
      $wp_parentCategoryId = isset($wp_categories_cache[$category->parentCode]) ? $wp_categories_cache[$category->parentCode] : 0;   
		
    $wp_categoryId = isset($wp_categories_cache[$category->code]) ? $wp_categories_cache[$category->code] : 0;   
		
		$foundByName = false;

		if ($wp_categoryId == 0) {
			$args = array(
				'hide_empty' => false, // also retrieve terms which are not used yet
				'name' => $category->name,
				'taxonomy'  => 'product_cat',
				'child_of' => $wp_parentCategoryId
			);
			$terms = get_terms($args);
			$wp_categoryId = !empty($terms) ? reset($terms)->term_id : 0;
			$foundByName = true;
		}

		$wc_category = null;
		if ($wp_categoryId) {
			$wc_category = wp_update_term($wp_categoryId, 'product_cat', array('name' => $category->name, 'parent' => $wp_parentCategoryId));
			if($foundByName)
				update_term_meta($wp_categoryId, 'io6_category_code', $category->code);
		}
		else {
			$wc_category = wp_insert_term($category->name, 'product_cat', array('parent' => $wp_parentCategoryId));
			if (!is_wp_error($wc_category))
				$wp_categoryId = $wc_category['term_id'];
			else
				throw new Exception($wc_category->get_error_message());

				update_term_meta($wp_categoryId, 'io6_category_code', $category->code);
				$wp_categories_cache[$category->code] = $wp_categoryId;
		}
		
		if (count($category->subCategories) > 0)
			syncCategories($category->subCategories);
	}
	if ($rootLevel)
		wp_cache_set('wp_categories', $wp_categories_cache);
}

function syncBrands()
{
	global $wpdb, $io6Engine, $wp_brands_cache, $io6_configuration;

	$brandField = $io6_configuration->selectedBrandField;

	$brands = $io6Engine->GetIO6Brands();

  //preload brands
  $sql = "SELECT term_id, meta_value FROM $wpdb->termmeta 
  where meta_key='io6_brand_code'";
	$results = $wpdb->get_results($wpdb->prepare($sql));
	foreach ($results as $row) {
		$wp_brands_cache[$row->meta_value] = intval($row->term_id);
	}

	foreach ($brands as $brand) {
    $wp_brandId = isset($wp_brands_cache[$brand->code]) ? $wp_brands_cache[$brand->code] : 0;   
		if($wp_brandId == 0) {
			$args = array(
				'hide_empty' => false, // also retrieve terms which are not used yet
				'name' => $brand->name,
				'taxonomy'  => $brandField
			);
			$terms = get_terms($args);
			$wp_brandId = !empty($terms) ? reset($terms)->term_id : 0;
		}

		$wc_brand = null;
		if ($wp_brandId)
			$wc_brand = wp_update_term($wp_brandId, $brandField, array('name' => $brand->name));
		else {
			$wc_brand = wp_insert_term($brand->name, $brandField);
			if (!is_wp_error($wc_brand))
				$wp_brandId = $wc_brand['term_id'];
			else
				throw new Exception($wc_brand->get_error_message());

			$wp_brands_cache[$brand->code] = $wp_brandId;
			update_term_meta($wp_brandId, 'io6_brand_code', $brand->code);
		}
		
	}
	wp_cache_set('wp_brands', $wp_brands_cache);
}

function syncSuppliers()
{
	global $wpdb, $io6Engine, $wp_suppliers_cache, $io6_configuration;

	$supplierField = "io6_product_supplier";

	$suppliers = $io6Engine->GetIO6Suppliers();

  //preload suppliers
  $sql = "SELECT term_id, meta_value FROM $wpdb->termmeta 
  where meta_key='io6_supplier_code'";
	$results = $wpdb->get_results($wpdb->prepare($sql));
	foreach ($results as $row) {
		$wp_suppliers_cache[$row->meta_value] = intval($row->term_id);
	}

	foreach ($suppliers as $supplier) {
    $wp_supplierId = isset($wp_suppliers_cache[$supplier->id]) ? $wp_suppliers_cache[$supplier->id] : 0;   
		if($wp_supplierId == 0) {
			$args = array(
				'hide_empty' => false, // also retrieve terms which are not used yet
				'name' => $supplier->name,
				'taxonomy'  => $supplierField
			);
			$terms = get_terms($args);
			$wp_supplierId = !empty($terms) ? reset($terms)->term_id : 0;
		}

		$wc_supplier = null;
		if ($wp_supplierId)
			$wc_supplier = wp_update_term($wp_supplierId, $supplierField, array('name' => $supplier->name));
		else {
			$wc_supplier = wp_insert_term($supplier->name, $supplierField);
			if (!is_wp_error($wc_supplier))
				$wp_supplierId = $wc_supplier['term_id'];
			else
				throw new Exception($wc_supplier->get_error_message());
			$wp_suppliers_cache[$supplier->id] = $wp_supplierId;
			update_term_meta($wp_supplierId, 'io6_supplier_code', $supplier->id);
		}		
	}
	wp_cache_set('wp_suppliers', $wp_suppliers_cache);
}

function syncProducts($currentPage = 1, $fastSync = false)
{
	global $wpdb, $images_path, $io6Engine, $wp_products_cache, $wp_brands_cache, $wp_categories_cache, $wp_suppliers_cache, $io6_configuration;

	if ($currentPage == 1) {
		prepareUpdate();    
		
		$wp_products_cache = array();
		$sql = "SELECT post_id, meta_value FROM $wpdb->postmeta  WHERE meta_key='io6_product_id'";
		$results = $wpdb->get_results($wpdb->prepare($sql));
		foreach ($results as $row) {
			$wp_products_cache[$row->meta_value] = intval($row->post_id);
		}
		wp_cache_set('wp_products', $wp_products_cache);
	} else
		$wp_products_cache = wp_cache_get('wp_products');

	$wp_categories_cache = wp_cache_get('wp_categories');
	$wp_brands_cache = wp_cache_get('wp_brands');
	$wp_suppliers_cache = wp_cache_get('wp_suppliers');

	$skuField = $io6_configuration->selectedSkuField;
	$eanField = $io6_configuration->selectedEanField;
	$partNumberField = $io6_configuration->selectedPartNumberField;
	$brandField = $io6_configuration->selectedBrandField;
	$supplierField = 'io6_product_supplier';

	$update_categories = !$fastSync && $io6_configuration->manageCategories;
	$update_title = !$fastSync && $io6_configuration->manageTitle;
	$update_content = !$fastSync && $io6_configuration->manageContent;
	$update_excerpt = !$fastSync && $io6_configuration->manageExcerpt;
	$update_prices = $io6_configuration->managePrices;
	$update_images = !$fastSync && $io6_configuration->manageImages;
	$update_features = !$fastSync && $io6_configuration->manageFeatures;
	$update_features_html = !$fastSync && $io6_configuration->manageFeaturesHTML;
	
	$exclude_unavail_products = $io6_configuration->excludeUnAvail;

	$product_type = get_term_by('name', 'simple', 'product_type');
	if (!$product_type)
		throw new Exception("Impossibile ottenere il 'product_type'. Verificare l'installazione di WooCommerce");


	$io6_results = $io6Engine->GetIO6Products($currentPage);

	$syncResults = array();
	$syncResults['pages'] = $io6_results['pages'];
	$syncResults['elementsFounds'] = $io6_results['elementsFounds'];
	$syncResults['products'] = array();
	foreach ($io6_results['products'] as $product) {
		$io6_manage_categories = false;
		$io6_manage_title = false;
		$io6_manage_content = false;
		$io6_manage_excerpt = false;
		$io6_manage_prices = false;
		$io6_manage_images = false;
		$io6_manage_features = false;
		$io6_manage_features_html = false;

		$retProduct = array('io6_id' => $product->id, 'ean' => $product->ean, 'partnumber' => $product->partNumber);

		try {
			$wp_product_id = isset($wp_products_cache[$product->id]) ? $wp_products_cache[$product->id] : 0;
			$wp_brand_id = isset($wp_brands_cache[$product->brandCode]) ? $wp_brands_cache[$product->brandCode] : 0;
			$wp_category_id = !$fastSync && isset($wp_categories_cache[$product->categoryCode]) ? $wp_categories_cache[$product->categoryCode] : 0;
			$wp_supplier_id = !$fastSync &&	isset($wp_suppliers_cache[$product->supplierId]) ? $wp_suppliers_cache[$product->supplierId] : 0;

			$wp_brand = null;
			$wp_category = null;

			$skuProp = str_replace('io6_sku_', '', $skuField);
			$skuValue = $product->$skuProp;

			if ($wp_product_id == 0) {
				if ($fastSync)
						throw new Exception("Prodotto non aggiornabile con procedura FAST perchè non esistente in WooCommerce o non abbinato ad ImporterONE.");						
				
				if (!empty($product->$skuProp)) {
					$sql = "SELECT post_id FROM $wpdb->postmeta ";					
					if($skuProp == 'partnumber')
						$sql .= "INNER JOIN $wpdb->term_relationships tr ON tr.object_id = $wpdb->postmeta.post_id AND tr.term_taxonomy_id = $wp_brand_id";					
					$sql .= "WHERE $wpdb->postmeta.meta_key='_sku' AND $wpdb->postmeta.meta_value='" . esc_sql($skuValue) . "'";

					
					$results = $wpdb->get_results($wpdb->prepare($sql));
					if (isset($results) && count($results) > 0) {
						$wp_product_id = $results[0]->post_id;
					}
				}

				if ($wp_product_id == 0 && !empty($product->ean)) {
					$sql = "SELECT post_id FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key='$eanField' AND $wpdb->postmeta.meta_value='" . esc_sql($product->ean) . "'";
					$results = $wpdb->get_results($wpdb->prepare($sql));
					if (isset($results) && count($results) > 0) {
						$wp_product_id = $results[0]->post_id;
					}
				}
				
				if ($wp_product_id == 0 && !empty($product->partNumber)) {					
					$sql = "SELECT post_id FROM $wpdb->postmeta ";
					if($skuProp == 'partnumber')
						$sql .= "INNER JOIN $wpdb->term_relationships tr ON tr.object_id = $wpdb->postmeta.post_id AND tr.term_taxonomy_id = $wp_brand_id";
					$sql .= " WHERE $wpdb->postmeta.meta_key='$partNumberField' AND $wpdb->postmeta.meta_value='" . esc_sql($product->partNumber) . "'";
					$results = $wpdb->get_results($wpdb->prepare($sql));
					if (isset($results) && count($results) > 0) {
						$wp_product_id = $results[0]->post_id;
					}
				}
			}

			$newReference = $wp_product_id == 0;
			

			if ($newReference && $exclude_unavail_products && (int)$product->avail <= 0) {
				throw new Exception("Product $skuValue doesn't exists and cannot be created because is unavail.");
			}


			if ($newReference && $fastSync) { 
				throw new Exception("Product $skuValue doesn't exists and cannot be created during fastSync.");
			}

			$activeState = $product->isActive && $product->statusCode != 99;

			if ($wp_product_id) {
				$retProduct['wp_product_id'] = $wp_product_id;
				$post_metas = get_post_meta($wp_product_id);

				if (!isset($post_metas['io6_exclude'][0]) || intval($post_metas['io6_exclude'][0]) == 1) {
					throw new Exception("Product $wp_product_id is not managed by " . IO6_PLUGIN_NAME);
				}

				$io6_manage_categories = isset($post_metas['io6_manage_categories'][0]) ? intval($post_metas['io6_manage_categories'][0]) : 2;
				$io6_manage_title = isset($post_metas['io6_manage_title'][0]) ? intval($post_metas['io6_manage_title'][0]) : 2;
				$io6_manage_content = isset($post_metas['io6_manage_content'][0]) ? intval($post_metas['io6_manage_content'][0]) : 2;
				$io6_manage_excerpt = isset($post_metas['io6_manage_excerpt'][0]) ? intval($post_metas['io6_manage_excerpt'][0]) : 2;
				$io6_manage_prices = isset($post_metas['io6_manage_prices'][0]) ? intval($post_metas['io6_manage_prices'][0]) : 2;
				$io6_manage_images = isset($post_metas['io6_manage_images'][0]) ? intval($post_metas['io6_manage_images'][0]) : 2;
				$io6_manage_features = isset($post_metas['io6_manage_features'][0]) ? intval($post_metas['io6_manage_features'][0]) : 2;
				$io6_manage_features_html = isset($post_metas['io6_manage_features_html'][0]) ? intval($post_metas['io6_manage_features_html'][0]) : 2;

				$wc_product = new WC_Product($wp_product_id);
        
				$io6_manage_categories = $activeState ? ($io6_manage_categories != 2 ? $io6_manage_categories : $update_categories) : false;
				$io6_manage_title = $activeState ? ($io6_manage_title != 2 ? $io6_manage_title : $update_title) : false;
				$io6_manage_content = $activeState ? ($io6_manage_content != 2 ? $io6_manage_content : $update_content) : false;
				$io6_manage_excerpt = $activeState ? ($io6_manage_excerpt != 2 ? $io6_manage_excerpt : $update_excerpt) : false;
				$io6_manage_prices = $activeState ? ($io6_manage_prices != 2 ? $io6_manage_prices : $update_prices) : false;
				$io6_manage_images = $activeState ? ($io6_manage_images != 2 ? $io6_manage_images : $update_images) : false;
				$io6_manage_features = $activeState ? ($io6_manage_features != 2 ? $io6_manage_features : $update_features) : false;
				$io6_manage_features_html = $activeState ? ($io6_manage_features_html != 2 ? $io6_manage_features_html : $update_features_html) : false;

				if ($fastSync) {
					$io6_manage_categories = false;
					$io6_manage_title = false;
					$io6_manage_content = false;
					$io6_manage_excerpt = false;
					$io6_manage_prices = $activeState ? ($io6_manage_prices != 2 ? $io6_manage_prices : true) : false;
					$io6_manage_images = false;
					$io6_manage_features = false;
					$io6_manage_features_html = false;
				}
			} else {
				$io6_manage_categories = true;
				$io6_manage_title = true;
				$io6_manage_content = true;
				$io6_manage_excerpt = true;
				$io6_manage_prices = true;
				$io6_manage_images = $update_images;
				$io6_manage_features = $update_features;
				$io6_manage_features_html = $update_features_html;
			}

			$retProduct['activeState'] = $activeState;
			if (!$wp_product_id && !$activeState) {
				throw new Exception("Product $skuValue isn't active.");
			}


			if (!$fastSync) {
				if ($wp_brand_id == 0) {
					$args = array(
						'hide_empty' => false,
						'meta_query' => array(
							array(
								'key'       => 'io6_brand_code',
								'value'     => $product->brandCode,
								'compare'   => '='
							)
						),
						'taxonomy'  => $brandField,
					);
					$terms = get_terms($args);
					$wp_brand = !empty($terms) ? reset($terms) : null;
					$wp_brand_id = isset($wp_brand) ? $wp_brand->term_id : 0;
				}

				if ($wp_supplier_id == 0) {
					$args = array(
						'hide_empty' => false,
						'meta_query' => array(
							array(
								'key'       => 'io6_supplier_code',
								'value'     => $product->supplierId,
								'compare'   => '='
							)
						),
						'taxonomy'  => $supplierField,
					);
					$terms = get_terms($args);
					$wp_supplier = !empty($terms) ? reset($terms) : null;
					$wp_supplier_id = isset($wp_supplier) ? $wp_supplier->term_id : 0;
				}

				if ($wp_category_id == 0) {
					$args = array(
						'hide_empty' => false,
						'meta_query' => array(
							array(
								'key'       => 'io6_category_code',
								'value'     => $product->categoryCode,
								'compare'   => '='
							)
						),
						'taxonomy'  => 'product_cat',
					);
					$terms = get_terms($args);
					$wp_category = !empty($terms) ? reset($terms) : null;
					$wp_category_id = isset($wp_category) ? $wp_category->term_id : 0;
				} else {
					$wp_category = get_term($wp_category_id, 'product_cat');
				}


				if ($wp_brand_id == 0 || $wp_category_id == 0) {
					throw new Exception("No brand [$product->brandCode] or category [$product->categoryCode] found for product $wp_product_id");
				}

				$wp_product = array();
				$wp_product['ID'] = $wp_product_id;

				$wp_product['post_status'] = "publish";
				$wp_product['post_type'] = "product";

				if ($io6_manage_title && isset($product->title)) {
					$wp_product['post_title'] = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
						return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
					}, $product->title);
					$wp_product['post_title'] =  substr(preg_replace('/[<>;=#{}]/', '', $wp_product['post_title']), 0, 128);
				}
				if ($io6_manage_content && isset($product->fullDescription)) {
					$wp_product['post_content'] = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
						return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
					}, $product->fullDescription);
					$wp_product['post_content'] =  preg_replace('/\\\\n/', '<br/>', $wp_product['post_content']);
				}

				if ($io6_manage_excerpt && isset($product->shortDescription)) {
					$wp_product['post_excerpt'] = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
						return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
					}, $product->shortDescription);

					$wp_product['post_excerpt'] =  substr(preg_replace('/\\\\n/', '<br/>', $wp_product['post_excerpt']), 0, 400);
				}
				if ($activeState) {
					$wp_product_id = $newReference ? wp_insert_post($wp_product, true) : wp_update_post($wp_product, true);

					if (is_wp_error($wp_product_id)) {
						throw new Exception($wp_product_id->get_error_message());
					}
				}


				//TODO: EM20210329 => campi da gestire

				//$arrivalAvail;			NN c'è un campo su WooCommerce
				//$arrivalDate;				NN c'è un campo su WooCommerce
				//$officialPrice;			NN c'è un campo su WooCommerce			
				//$raeeAmount;				NN c'è un campo su WooCommerce

        if($newReference)
				  $wc_product = new WC_Product($wp_product_id);
			}

			if ($io6_manage_categories) {

				$current_cat_ids = wc_get_product_term_ids($wp_product_id, 'product_cat');
				
				if (!in_array($wp_category_id, $current_cat_ids)) {
					$categories = array();
					$categories[] = $wp_category_id;

					$parent = $wp_category->parent;
					while ($parent > 0) {
						// Make an array of all term ids up to the parent.
						$categories[] = $parent;
						$grandpa = get_term($parent, 'product_cat');
						$parent = $grandpa->parent;
					}

					// If multiple terms are returned, update the object terms
					if (count($categories) > 0) wp_set_object_terms($wp_product_id, $categories, 'product_cat');
				}
				//}
			}
			update_post_meta($wp_product_id, 'io6_exclude', '0');
			update_post_meta($wp_product_id, 'io6_product_id', $product->id);

			if ($activeState) {
				update_post_meta($wp_product_id, 'io6_updated', 1);

				if (!$fastSync) {
					$wc_product->set_sku($skuValue);

					if (isset($product->partNumber) && !empty($product->partNumber))
						update_post_meta($wp_product_id, $partNumberField, $product->partNumber);

					if (isset($product->ean) && !empty($product->ean))
						update_post_meta($wp_product_id, $eanField, $product->ean);

					wp_set_object_terms($wp_product_id, $wp_brand_id, $brandField);
					wp_set_object_terms($wp_product_id, $wp_supplier_id, $supplierField);

					if ($newReference)
						wp_set_object_terms($wp_product_id, array($product_type->term_id), 'product_type');

					$wc_product->set_manage_stock(true);
					$wc_product->set_weight($product->weight);
					$wc_product->set_width($product->width);
					$wc_product->set_length($product->length);
					$wc_product->set_height($product->height);
				}

				if ($io6_manage_prices) {
					$wc_product->set_regular_price(round($product->sellingPrice + $product->siaeAmount, 2));
					$wc_product->set_sale_price(round($product->sellingCustomPrice + $product->siaeAmount, 2));
					$wc_product->set_price(round($product->sellingCustomPrice + $product->siaeAmount, 2));

					$utcCustomPriceUntil = strtotime($product->sellingCustomPriceUntil);

					if ($utcCustomPriceUntil !== false && $utcCustomPriceUntil < 253402300799) {
						$wc_product->set_date_on_sale_to($utcCustomPriceUntil);
					} else
						$wc_product->set_date_on_sale_to(null);
				}
			}

			$wc_product->set_stock_status((int)$product->avail > 0 && $activeState ? 'instock' : 'outofstock');
			$wc_product->set_stock_quantity($activeState ? (int)$product->avail : 0);
			$wc_product->set_catalog_visibility($activeState ? 'visible' : 'hidden');

			$wc_product->save();

			if ($io6_manage_images) {

				$sql = "SELECT wp.ID FROM $wpdb->posts wp LEFT JOIN $wpdb->postmeta wpm ON wpm.post_id = wp.ID AND wpm.meta_key='io6_imageuri'
			 WHERE wpm.post_id IS NULL AND wp.`post_type`='attachment' AND wp.post_mime_type <> '' AND wp.post_parent = " . $wp_product_id;
				$results = $wpdb->get_results($wpdb->prepare($sql));

				//Deleting all images not from ImporterONE
				foreach ($results as $attachment_to_delete) {
					wp_delete_attachment($attachment_to_delete->ID);
				}

				$sql = "SELECT p2.post_id, p2.meta_key, p2.meta_value FROM $wpdb->postmeta INNER JOIN $wpdb->postmeta p2 ON p2.post_id = $wpdb->postmeta.meta_value AND (p2.meta_key='io6_imageuri' OR p2.meta_key='_wp_attached_file') WHERE $wpdb->postmeta.meta_key='_thumbnail_id' AND $wpdb->postmeta.post_id=$wp_product_id";
				$results = $wpdb->get_results($wpdb->prepare($sql));

				$gallery = array();
				foreach ($results as $row) {
					$gallery[$row->post_id]['postid'] = $row->post_id;
					if ($row->meta_key == '_wp_attached_file') {
						$gallery[$row->post_id]['attached_file'] = $row->meta_value;
						$gallery[$row->post_id]['absolute_path'] = $images_path . basename($row->meta_value);
					} else
						$gallery[$row->post_id]['imageuri'] = $row->meta_value;
				}
				//getting gallery
				$sql = "SELECT p2.post_id, p2.meta_key, p2.meta_value FROM $wpdb->postmeta p INNER JOIN $wpdb->postmeta p2 ON	p2.post_id REGEXP CONCAT('(^', REPLACE(p.meta_value, ',', '$)|(^'), '$)') AND (p2.meta_key='io6_imageuri' OR p2.meta_key='_wp_attached_file') WHERE p.meta_key = '_product_image_gallery' AND p.post_id = $wp_product_id";
				$results = $wpdb->get_results($wpdb->prepare($sql));

				foreach ($results as $row) {
					$gallery[$row->post_id]['postid'] = $row->post_id;
					if ($row->meta_key == '_wp_attached_file') {
						$gallery[$row->post_id]['attached_file'] = $row->meta_value;
						$gallery[$row->post_id]['absolute_path'] = $images_path . basename($row->meta_value);
					} else
						$gallery[$row->post_id]['imageuri'] = $row->meta_value;
				}

				$product_image_gallery = array();
				foreach ($product->images as $key => $image) {
					$wp_image = reset(array_filter(
						$gallery,
						function ($e) use ($image) {
							return $e['imageuri'] == $image->imageUri;
						}
					));

					$attach_id = !empty($wp_image) ? $wp_image['postid'] : 0;
					if (!empty($attach_id)) {
						$wp_image_last_update = get_post_meta($attach_id, 'io6_last_update', true);
						if (empty($wp_image_last_update))
							$wp_image_last_update = date('YmdHis', filemtime($wp_image['absolute_path']));
					}

					$to_download = empty($wp_image) || !file_exists($wp_image['absolute_path']) || date('Y-m-d H:i:s', strtotime($image->lastUpdate)) > $wp_image_last_update;

					if ($to_download) {
						//scarica l'immagine

						$image_data     = isset($image->imageUri) ? file_get_contents($image->imageUri) : null; // Get image data								
						if (!isset($image_data) || $image_data === false) continue;

						$image_filename = wp_unique_filename($images_path, basename($image->imageUri));
						$image_filepath = $images_path . $image_filename;

						file_put_contents($image_filepath, $image_data);

						//usleep(1000000/3);							
						if ($attach_id == 0) {
							$wp_filetype = wp_check_filetype($image_filename, null);

							$attachment = array(
								'guid' 					 => $images_path . $image_filename,
								'post_mime_type' => $wp_filetype['type'],
								'post_title'     => sanitize_file_name($image_filename),
								'post_content'   => '',
								'post_status'    => 'inherit'
							);

							// Create the attachment
							$attach_id = wp_insert_attachment($attachment, $image_filepath, $wp_product_id);
							update_post_meta($attach_id, 'io6_imageuri', $image->imageUri);
							// Include image.php
						}
						// Define attachment metadata
						$attach_data = wp_generate_attachment_metadata($attach_id, $image_filepath);

						// Assign metadata to attachment
						wp_update_attachment_metadata($attach_id, $attach_data);

						update_post_meta($attach_id, 'io6_last_update', date('Y-m-d H:i:s', strtotime($image->lastUpdate)));
					}
					if ($key == 0)
						set_post_thumbnail($wp_product_id, $attach_id);
					if ($key > 0)
						$product_image_gallery[] = $attach_id;
				}
				if (count($product_image_gallery))
					update_post_meta($wp_product_id, '_product_image_gallery',  implode(',', $product_image_gallery));

				//pulizia immagini inversa				
				$sql = "SELECT wp.ID, wpm.meta_value FROM $wpdb->posts wp
								INNER JOIN $wpdb->postmeta wpm ON wpm.post_id = wp.ID
								WHERE wp.post_parent= $wp_product_id AND wp.post_type='attachment' AND wp.post_mime_type <> '' AND wpm.meta_key='io6_imageuri'";

				$results = $wpdb->get_results($wpdb->prepare($sql));

				foreach ($results as $row) {
					$foundImage = reset(array_filter($product->images, function ($image) use ($row) {
						return $image->imageUri == $row->meta_value;
					}));
					if (!$foundImage) {
						wp_delete_attachment($row->ID, true);
					}
				}
			}

			if ($io6_manage_features_html)
				update_post_meta($wp_product_id, 'io6_features_html', htmlentities($product->featuresHtml));

			if ($io6_manage_features) {
				$serialized_attributes = [];
				$position = 0;
				foreach ($product->features as $feature) {
					if (!$feature->searchable) continue;

					$slug = substr($feature->code . '-' . sanitize_title($feature->name), 0, 27);
					$wc_attribute_id = wc_attribute_taxonomy_id_by_name($slug);

					if ($wc_attribute_id == 0) {
						$retValue = wc_create_attribute(array('name' => $feature->name, 'slug' => $slug));
						if (is_wp_error($retValue))
							throw new Exception($retValue->get_error_message());
					}

					$taxonomy = 'pa_' . $slug;

					if (!taxonomy_exists($taxonomy))
						register_taxonomy($taxonomy, array('product'));

					$feature_value = substr($feature->value, 0, 200);

					$ret_term = term_exists(sanitize_title($feature_value), $taxonomy);

					if (!isset($ret_term)) {
						$ret_term = wp_insert_term($feature_value, $taxonomy, array('slug' => sanitize_title($feature_value)));
						if (is_wp_error($ret_term))
							throw new Exception($ret_term->get_error_message());
					}

					wp_set_object_terms($wp_product_id, intval($ret_term['term_id']), $taxonomy);

					$serialized_attributes[$taxonomy] = array('name' => $taxonomy, 'value' => '', 	'position' => $position, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 1);
					$position++;
				}

				update_post_meta($wp_product_id, '_product_attributes', $serialized_attributes);
			}
			$retProduct['status_message'] = "Ok";
		} catch (Exception $e) {
			$retProduct['status_message'] = $e->getMessage();
			io6_write_log($e, IO6_LOG_WARNING);
		}
		array_push($syncResults['products'], $retProduct);
	}

	 if ($currentPage >= $io6_results['pages'])
	 	resetCatalog();

	return $syncResults;
}


function prepareUpdate()
{
	global $wpdb, $io6Engine;

	$sql = "DELETE FROM $wpdb->postmeta WHERE meta_key='io6_updated'";
	$wpdb->query($sql);
}

function resetCatalog()
{
	global $wpdb, $io6Engine;

	$sql = "UPDATE $wpdb->posts p					
					INNER JOIN $wpdb->postmeta pm_exclude ON pm_exclude.post_id = p.ID AND pm_exclude.meta_key='io6_exclude '
					LEFT OUTER JOIN $wpdb->postmeta pm_updated ON pm_updated.post_id = p.ID AND pm_updated.meta_key='io6_updated'	
					SET p.post_status='private'
					WHERE p.post_type='product' AND p.post_status='publish' AND pm_exclude.meta_value = 0 AND IFNULL(pm_updated.meta_value, 0) = 0 ";

	$wpdb->query($sql);
}
