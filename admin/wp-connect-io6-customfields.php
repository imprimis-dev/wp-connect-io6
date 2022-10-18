<?php

add_action('admin_init', function () {
  add_meta_box(
    'io6_features_html',
    'Scheda tecnica',
    'io6_render_features_html',
    'product',
    'normal',
    'low'
  );
});


function io6_render_features_html()
{
  global $post;

  wp_nonce_field(basename(__FILE__), 'features_fields');
  
  $featuresHtml = html_entity_decode(get_post_meta($post->ID, 'io6_features_html', true));
  
  wp_editor(
    $featuresHtml,
    'custom_editor',
    array(
      'media_buttons' => true,
      'textarea_name' => 'io6_features_html'
    )
  );
}



add_action('save_post', function ($post_id, $post) {

  
  if (!current_user_can('edit_post', $post_id)) {
    return $post_id;
  }

  if (!isset($_POST['io6_features_html']) || !wp_verify_nonce($_POST['features_fields'], basename(__FILE__))) {
    return $post_id;
  }

  $io6_features_html = esc_textarea($_POST['io6_features_html']);

  if ('revision' === $post->post_type) {
    return;
  }
  if (empty($io6_features_html))
    delete_post_meta($post_id, 'io6_features_html');
  else
    update_post_meta($post_id, 'io6_features_html', $io6_features_html);
}, 1, 2);


add_action('woocommerce_product_options_inventory_product_data', function () {
  
  global $io6_configuration;

  if ($io6_configuration->selectedEanField === 'io6_eancode') {
    // EAN CODE
    woocommerce_wp_text_input(
      array(
        'id' => 'io6_eancode',
        'label' => __('EAN Code', IO6_DOMAIN),
        'placeholder' => 'EAN Code',
        'desc_tip' => 'true',
        'description' => __('Enter the EAN Code here.', IO6_DOMAIN)
      )
    );
  }
  if ($io6_configuration->selectedPartNumberField === 'io6_partnumber') {
    // PART NUMBER
    woocommerce_wp_text_input(
      array(
        'id' => 'io6_partnumber',
        'label' => __('PartNumber', IO6_DOMAIN),
        'placeholder' => 'PartNumber',
        'desc_tip' => 'true',
        'description' => __('Enter the PartNumber here.', IO6_DOMAIN)
      )
    );
  }
});
add_action('woocommerce_product_options_general_product_data', function () {
	global $post;
	
	$set_exclude_value = get_post_meta($post->ID, 'io6_exclude');
  woocommerce_wp_checkbox(
		
    array(
      'id' => 'io6_exclude',
      'label' => '',
      'desc_tip' => false,			
      'cbvalue' => 1,
			'value' => empty($set_exclude_value) ? 1 : $set_exclude_value[0],
      'description' => sprintf(__('Escludi da %s', IO6_DOMAIN), IO6_PLUGIN_NAME)			
    )
    
  );
});
add_action('woocommerce_product_options_advanced', function () {
  
  
	$options = array(
    '2' => __('Default', IO6_DOMAIN),
    '0' => __('No', IO6_DOMAIN),
    '1' => __('Si', IO6_DOMAIN)
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_categories',
      'label' => __('Aggiorna le categorie', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_title',
      'label' => __('Aggiorna il titolo', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_content',
      'label' => __('Aggiorna il contenuto', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_excerpt',
      'label' => __('Aggiorna la breve descrizione', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );
	
  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_prices',
      'label' => __('Aggiorna i prezzi', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_images',
      'label' => __('Aggiorna le immagini', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
     

    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_features',
      'label' => __('Aggiorna gli attributi', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );

  woocommerce_wp_select(
    array(
      'id' => 'io6_manage_features_html',
      'label' => __('Aggiorna la scheda tecnica', IO6_DOMAIN),
      'desc_tip' => false,
      'options' => $options
      
    )
  );
});

add_action('woocommerce_process_product_meta', function ($post_id) {
  global $io6_configuration;

  update_post_meta($post_id, 'io6_exclude', isset($_POST['io6_exclude']) ? '1' : '0');

  update_post_meta($post_id, 'io6_manage_categories', $_POST['io6_manage_categories']);
  update_post_meta($post_id, 'io6_manage_title', $_POST['io6_manage_title']);
	update_post_meta($post_id, 'io6_manage_prices', $_POST['io6_manage_prices']);
  update_post_meta($post_id, 'io6_manage_content', $_POST['io6_manage_content']);
  update_post_meta($post_id, 'io6_manage_excerpt', $_POST['io6_manage_excerpt']);
  update_post_meta($post_id, 'io6_manage_images', $_POST['io6_manage_images']);
  update_post_meta($post_id, 'io6_manage_features', $_POST['io6_manage_features']);
  update_post_meta($post_id, 'io6_manage_features_html', $_POST['io6_manage_features_html']);

  if ($io6_configuration->selectedEanField === 'io6_eancode') {
    // Save Text Field
    $custom_field = sanitize_text_field($_POST['io6_eancode']);
    if (!empty($custom_field))
      update_post_meta($post_id, 'io6_eancode', esc_attr($custom_field));
    else
      delete_post_meta($post_id, 'io6_eancode');
  }

  if ($io6_configuration->selectedPArtNumberField === 'io6_partnumber') {
    // Save Text Field
    $custom_field = sanitize_text_field($_POST['io6_partnumber']);
    if (!empty($custom_field))
      update_post_meta($post_id, 'io6_partnumber', esc_attr($custom_field));
    else
      delete_post_meta($post_id, 'io6_partnumber');
  }
});




  add_action('init', function () {
		global $io6_configuration;

		if ($io6_configuration->selectedBrandField === 'io6_product_brand') {
			register_taxonomy('io6_product_brand', array('product'), array(
				'labels' => array(
					'name' => 'Marche',
					'singular_name' => 'Marca',
					'search_items' => 'Cerca Marche',
					'all_items' => 'Tutte le Marche',
					'parent_item' => 'Parent Brand',
					'parent_item_colon' => 'Parent Brand:',
					'edit_item' => 'Modifica Marca',
					'update_item' => 'Aggiorna Marca',
					'add_new_item' => 'Aggiungi Nuova Marca',
					'new_item_name' => 'Nuova Marca',
					'not_found' => 'No Brand Found',
					'menu_name' => 'Marche'
				),
				'hierarchical' => false,
				'query_var' => true,
				'public' => true,
				'show_tagcloud' => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'sort' => '',
				'rewrite' => array('slug' => 'brand', 'with_front' => false),
				'show_ui' => true
			));
	}
	register_taxonomy('io6_product_supplier', array('product'), array(
		'labels' => array(
			'name' => 'Fornitori',
			'singular_name' => 'Fornitore',
			'search_items' => 'Cerca Fornitore',
			'all_items' => 'Tutte i Fornitori',
			'parent_item' => 'Parent Supplier',
			'parent_item_colon' => 'Parent Supplier:',
			'edit_item' => 'Modifica Fornitore',
			'update_item' => 'Aggiorna Fornitore',
			'add_new_item' => 'Aggiungi Nuovo Fornitore',
			'new_item_name' => 'Nuovo Fornitore',
			'not_found' => 'No Supplier Found',
			'menu_name' => 'Fornitori'
		),
		'hierarchical' => false,
		'query_var' => true,
		'public' => true,
		'show_tagcloud' => true,
		'show_admin_column' => false,
		'show_in_nav_menus' => false,
		'sort' => '',
		'rewrite' => array('slug' => 'supplier', 'with_front' => false),
		'show_ui' => true
	));
}, 0);



?>