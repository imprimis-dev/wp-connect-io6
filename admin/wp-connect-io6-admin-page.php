<?php

add_action('admin_notices', function () {
  global $_catalogs, $_pricelists, $io6_configuration;

  $screen = get_current_screen();
  if ($screen->parent_base == 'io6-main-menu') {
    if (!defined('WC_VERSION')) {
      echo '<div class="notice notice-error is-dismissible">
             <p>' . __('Woocommerce non installato', IO6_DOMAIN) . '</p>
         </div>';
    }
    if ($_catalogs === false) {
      echo '<div class="notice notice-error">
							 <p>' . __('API SETTINGS non validi o API Endpoint non raggiungibile.', IO6_DOMAIN) . '</p>
					 </div>';
    }
    if (is_array($_catalogs) && count($_catalogs) == 0) {
      echo '<div class="notice notice-warning">
							 <p>' . __('Non sono presenti Cataloghi Personali attivi. Verifica sul tuo ImporterONE Cloud.', IO6_DOMAIN) . '</p>
					 </div>';
    }
    if ($io6_configuration->catalogId != 0 && (!is_array($_pricelists) || count($_pricelists) == 0)) {
      echo '<div class="notice notice-warning">
							 <p>' . __('Non sono presenti Listini attivi. Verifica sul tuo ImporterONE Cloud.', IO6_DOMAIN) . '</p>
					 </div>';
    }
  }
});

add_action('admin_enqueue_scripts', function ($hook) {
  // Only add to the edit.php admin page.
  // See WP docs.
  wp_enqueue_style('main', plugin_dir_url(__DIR__) . '/assets/css/main.css');

  if ('importerone-cloud-connector_page_io6_menu_execute' !== $hook) {
    return;
  }

  wp_enqueue_script('main', plugin_dir_url(__DIR__) . '/assets/js/main.js', array('jquery'), false, true);



  wp_register_script('io6_settings_js', '');
  wp_enqueue_script('io6_settings_js');
  wp_add_inline_script('io6_settings_js', 'var io6_ajax_url = "' . admin_url('admin-ajax.php') . '";');
});



add_action('wp_ajax_nopriv_io6-sync', 'io6_sync');
add_action('wp_ajax_io6-sync', 'io6_sync');

add_action('admin_menu', function () {
  add_menu_page(
    sprintf(__('%s - Configura', IO6_DOMAIN), IO6_PLUGIN_NAME),
    IO6_PLUGIN_NAME,
    'manage_options',
    'io6-main-menu',
    'admin_io6_settings'
  );
  add_submenu_page('io6-main-menu', __('Configura', IO6_DOMAIN), __('Configura', IO6_DOMAIN), 'manage_options', 'io6-main-menu', 'admin_io6_settings');
  add_submenu_page('io6-main-menu', __('Esegui', IO6_DOMAIN), __('Esegui', IO6_DOMAIN), 'manage_options', 'io6_menu_execute', function () {
    global $io6_configuration;

?>

    <div class="wrap">
      <h1><?php printf(__('%s - Esegui', IO6_DOMAIN), IO6_PLUGIN_NAME); ?></h1>

      <?php
      if ($io6_configuration->catalogId == 0 || $io6_configuration->priceListId == 0) {
      ?>
        <p><?php printf(__('La configurazione di %s non è completa. Torna alla pagina delle impostazioni.', IO6_DOMAIN), IO6_PLUGIN_NAME) ?></p>
      <?php

      } else {
      ?>
        <div class="fast-sync"><input type="checkbox" id="io6-fast-sync" value="1" /><label for="io6-fast-sync">Aggiornamento veloce</label></div>
        <input type="button" id="io6-exec-sync" class="button button-primary" value="<?php printf(__('Aggiorna da ImporterONE', IO6_DOMAIN)) ?>">
        <input type="button" id="io6-exec-cancel-sync" class="button button-secondary d-none" value="<?php printf(__('Cancel', IO6_DOMAIN)) ?>">

        <div id="io6-exec-sync-info" class="sync-info"></div>
        <div id="io6-exec-sync-status" class="sync-status"></div>
      <?php } ?>
    </div>
  <?php
  });
});

function admin_io6_settings() {
  global $io6_configuration, $_catalogs;
  if (!current_user_can('manage_options'))
    return;


  // add error/update messages

  // check if the user have submitted the settings
  // WordPress will add the "settings-updated" $_GET parameter to the url
  //if (isset($_GET['settings-updated'])) {
  // add settings saved message with the class of "updated"
  //add_settings_error('icecool_messages', 'icecool_message', __('Settings Saved', 'icecool'), 'updated');
  //}

  // show error/update messages
  settings_errors(IO6_DOMAIN . '_messages');
  ?>
  <div class="wrap">
    <h1><?php printf(__('%s - Configura', IO6_DOMAIN), IO6_PLUGIN_NAME); ?></h1>

    <div class="config-info">
      <p>Configura in questa pagina i parametri per la connessione con ImporterONE Cloud</p>
      <p>Accedi alla pagina <strong>Integrazioni CM</strong>S nel portale <a href="https://app.importerone.it" target="_blank">app.importerone.it</a> per abilitare la connessione dal tuo WooCommerce</p>
      <p>Crea un Token indicando il dominio del tuo sito: <strong><?php echo $_SERVER['HTTP_HOST'] ?></strong></p>
      <p>Copia il Token generato e l'indirizzo dell'API Endpoint che trovi in quella stessa pagina per valorizzare i dati qui sotto.</p>
    </div>
    <?php
    if (defined('WC_VERSION')) { ?>
      <form action="options.php" method="post">
        <?php
        settings_fields(IO6_DOMAIN);
        do_settings_sections('io6_section_api');
        if (!empty($_catalogs))
          do_settings_sections('io6_section_general');
        if ($io6_configuration->catalogId && $io6_configuration->priceListId) {
          do_settings_sections('io6_section_products');
          do_settings_sections('io6_section_import');
        }
        submit_button(__('Save Settings', IO6_DOMAIN));
        ?>

      </form>
    <?php } ?>
  </div>
<?php
}

add_action('admin_init', function () {
  global $io6_configuration, $_catalogs;

  register_setting(IO6_DOMAIN, 'io6_options' /*, 'ic_options_validate'*/);

  add_settings_section(
    'io6_section_api',
    __('API Settings', IO6_DOMAIN),
    function () {
      //echo '<p>GENERAL Here you can set all the options for using the API</p>';
    },
    'io6_section_api'
  );
  if (!empty($_catalogs)) {
    add_settings_section(
      'io6_section_general',
      __('General Settings', IO6_DOMAIN),
      function () {
        //echo '<p>GENERAL Here you can set all the options for using the API</p>';
      },
      'io6_section_general'
    );
  }
  if ($io6_configuration->catalogId && $io6_configuration->priceListId) {
    add_settings_section(
      'io6_section_products',
      __('Products Settings', IO6_DOMAIN),
      function () {
        //echo '<p>GENERAL Here you can set all the options for using the API</p>';
      },
      'io6_section_products'
    );

    add_settings_section(
      'io6_section_import',
      __('Import Settings', IO6_DOMAIN),
      function () {
        //echo '<p>GENERAL Here you can set all the options for using the API</p>';
      },
      'io6_section_import'
    );
  }

  add_settings_field(
    'io6_apiendpoint',
    'EndPoint',
    'io6_render_apiendpoint',
    'io6_section_api',
    'io6_section_api',
    array(
      'id' => 'io6_apiendpoint',
      'required' => true
    )
  );


  add_settings_field(
    'io6_apitoken',
    'Token',
    'io6_render_apitoken',
    'io6_section_api',
    'io6_section_api',
    array(
      'id' => 'io6_apitoken',
      'required' => true
    )
  );

  add_settings_field(
    'io6_catalog',
    'Catalogo',
    'io6_render_catalog',
    'io6_section_general',
    'io6_section_general',
    array(
      'id' => 'io6_catalog',
      'required' => true
    )
  );

  if ($io6_configuration->catalogId) {
    add_settings_field(
      'io6_pricelist',
      'Listino',
      'io6_render_pricelist',
      'io6_section_general',
      'io6_section_general',
      array(
        'id' => 'io6_pricelist',
        'required' => true
      )
    );

    if ($io6_configuration->priceListId) {
      add_settings_field(
        'io6_select_sku_field',
        'Seleziona il campo SKU',
        'io6_render_select_sku_field',
        'io6_section_general',
        'io6_section_general',
        array(
          'id' => 'io6_select_sku_field'
        )
      );


      add_settings_field(
        'io6_select_partnumber_field',
        'Seleziona il campo PartNumber',
        'io6_render_select_partnumber_field',
        'io6_section_general',
        'io6_section_general',
        array(
          'id' => 'io6_select_partnumber_field'
        )
      );


      add_settings_field(
        'io6_select_ean_field',
        'Seleziona il campo EAN',
        'io6_render_select_ean_field',
        'io6_section_general',
        'io6_section_general',
        array(
          'id' => 'io6_select_ean_field'
        )
      );


      add_settings_field(
        'io6_select_brand_field',
        'Seleziona il campo Marchio',
        'io6_render_select_brand_field',
        'io6_section_general',
        'io6_section_general',
        array(
          'id' => 'io6_select_brand_field'
        )
      );
    }
  }
  add_settings_field(
    'io6_manage_categories',
    'Aggiorna le categorie',
    'io6_render_manage_categories',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_categories'
    )
  );
  add_settings_field(
    'io6_manage_title',
    'Aggiorna i titoli',
    'io6_render_manage_title',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_title'
    )
  );

  add_settings_field(
    'io6_manage_content',
    'Aggiorna il contenuto',
    'io6_render_manage_content',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_content'
    )
  );

  add_settings_field(
    'io6_manage_excerpt',
    'Aggiorna breve descrizione',
    'io6_render_manage_excerpt',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_excerpt'
    )
  );


  add_settings_field(
    'io6_manage_prices',
    'Aggiorna i prezzi',
    'io6_render_manage_prices',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_prices'
    )
  );

  add_settings_field(
    'io6_manage_images',
    'Aggiorna immagini',
    'io6_render_manage_images',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_images'
    )
  );

  add_settings_field(
    'io6_manage_features',
    'Aggiorna attributi',
    'io6_render_manage_features',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_features'
    )
  );

  add_settings_field(
    'io6_manage_features_html',
    'Aggiorna scheda tecnica',
    'io6_render_manage_features_html',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_manage_features_html'
    )
  );

  add_settings_field(
    'io6_concat_features_html',
    'Accoda scheda tecnica',
    'io6_render_concat_features_html',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_concat_features_html'
    )
  );

  add_settings_field(
    'io6_features_html_template',
    'Seleziona template da utilizzare',
    'io6_render_features_html_template',
    'io6_section_products',
    'io6_section_products',
    array(
      'id' => 'io6_features_html_template'
    )
  );

  add_settings_field(
    'io6_pagesize',
    'Prodotti elaborati alla volta',
    'io6_render_pagesize',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_pagesize'
    )
  );

  /*add_settings_field(
    'io6_exclude_status',
    'Disattiva obsoleti',
    'io6_render_exclude_status',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_exclude_status'
    )
  );*/


  add_settings_field(
    'io6_image_limit',
    'Numero massimo immagini',
    'io6_render_image_limit',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_image_limit'
    )
  );

  add_settings_field(
    'io6_exclude_noimage',
    'Disattiva senza immagini',
    'io6_render_exclude_noimage',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_exclude_noimage'
    )
  );

  // add_settings_field(
  //   'io6_exclude_unavail',
  //   'Prodotti non disponibili',
  //   'io6_render_exclude_unavail',
  //   'io6_section_import',
  //   'io6_section_import',
  //   array(
  //     'id' => 'io6_exclude_unavail'
  //   )
  // );

  add_settings_field(
    'io6_exclude_avail_lessthan',
    'Disattiva prodotti con disponibilità minore di',
    'io6_render_exclude_avail_lessthan',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_exclude_avail_lessthan'
    )
  );

  add_settings_field(
    'io6_exclude_avail_type',
    'Tipo di disponibilità da considerare',
    'io6_render_exclude_avail_type',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_exclude_avail_type'
    )
  );


  add_settings_field(
    'io6_cron_cmd',
    'Comando per Operazioni Pianificate',
    'io6_render_cron_cmd',
    'io6_section_import',
    'io6_section_import',
    array(
      'id' => 'io6_cron_cmd'
    )
  );


  // add_settings_field(
  // 	'ic_add_features_shortcode',
  // 	'Shortcode caratteristiche',
  // 	'ic_render_add_features_shortcode',
  // 	'ic_section_icecat',
  // 	'ic_section_icecat',
  // 	array(
  // 		'id' => 'ic_add_features_shortcode'
  // 	)
  // );
});

// function ic_options_validate($input)
// {
// 	$newinput['api_key'] = trim($input['api_key']);
// 	if (!preg_match('/^[a-z0-9]{32}$/i', $newinput['api_key'])) {
// 		$newinput['api_key'] = '';
// 	}

// 	return $newinput;
// }

function io6_render_apiendpoint($args) {
  global $io6_configuration;
?>
  <input type="url" id="<?php echo esc_attr($args['id']); ?>" class="regular-text" name="io6_options[apiendpoint]" value="<?php echo esc_attr($io6_configuration->apiEndPoint) ?>" <?php echo ($args['required'] ? 'required' : '') ?> />
<?php

}

function io6_render_apitoken($args) {
  global $io6_configuration;
?>
  <input type="text" id="<?php echo esc_attr($args['id']); ?>" class="regular-text" name="io6_options[apitoken]" value="<?php echo esc_attr($io6_configuration->apiToken) ?>" <?php echo ($args['required'] ? 'required' : '') ?> />
<?php

}



function io6_render_catalog($args) {
  global $io6_configuration, $_catalogs;
?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[catalog]">
    <option value="0"></option>
    <?php
    foreach ($_catalogs as $catalog) {
    ?>
      <option value="<?php echo $catalog->id ?>" <?php echo ($io6_configuration->catalogId == $catalog->id ? ' selected' : '') ?>><?php echo $catalog->name ?></option>
    <?php
    }

    ?>

  </select>

<?php

}


function io6_render_pricelist($args) {
  global $io6_configuration, $_pricelists;

?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[price_list]">
    <option value="0"></option>
    <?php
    foreach ($_pricelists as $priceList) {
    ?>
      <option value="<?php echo $priceList->id ?>" <?php echo ($io6_configuration->priceListId == $priceList->id ? ' selected' : '') ?>><?php echo $priceList->name ?></option>
    <?php
    }

    ?>

  </select>

<?php

}


function io6_render_select_brand_field($args) {
  global $io6_configuration;

  $taxonomies = get_taxonomies(null, 'objects');
?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[select_brand_field]">
    <option value="io6_product_brand"><?php printf(__('Gestito da %s', IO6_DOMAIN), IO6_PLUGIN_NAME); ?></option>
    <?php
    foreach ($taxonomies as $taxonomy) {
      $key = $taxonomy->name;
      if ($key == 'io6_product_brand') continue;
    ?>
      <option value='<?php echo $key ?>' <?php echo ($key == $io6_configuration->selectedBrandField ? 'selected' : '')  ?>><?php echo $taxonomy->label ?></option>
    <?php
    }
    ?>
  </select>

<?php

}

function io6_render_select_sku_field($args) {
  global $io6_configuration;

?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[select_sku_field]">
    <option value="io6_sku_partNumber" <?php echo $io6_configuration->selectedSkuField == 'io6_sku_partNumber' ? 'selected' : '' ?>><?php _e('PartNumber', IO6_DOMAIN); ?></option>
    <option value="io6_sku_ean" <?php echo $io6_configuration->selectedSkuField == 'io6_sku_ean' ? 'selected' : '' ?>><?php _e('EAN', IO6_DOMAIN); ?></option>
    <option value="io6_sku_id" <?php echo $io6_configuration->selectedSkuField == 'io6_sku_id' ? 'selected' : '' ?>><?php _e('Id Prodotto', IO6_DOMAIN); ?></option>
  </select>

<?php

}

function io6_render_select_partnumber_field($args) {
  global $wpdb, $io6_configuration;

  $sql = "SELECT DISTINCT pm.meta_key FROM $wpdb->posts p
          INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID
          WHERE p.post_type='product' AND LEFT(pm.meta_key, 4) <> 'io6_' AND pm.meta_key <> '_sku'";

  $metas = $wpdb->get_results($wpdb->prepare($sql));
?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[select_partnumber_field]">
    <option value="" <?php echo $io6_configuration->selectedPartNumberField == '' ? 'selected' : '' ?>><?php _e('Non utilizzare', IO6_DOMAIN); ?></option>
    <option value="io6_partnumber" <?php echo $io6_configuration->selectedPartNumberField == 'io6_partnumber' ? 'selected' : '' ?>><?php printf(__('Gestito da %s', IO6_DOMAIN), IO6_PLUGIN_NAME); ?></option>
    <?php
    foreach ($metas as $meta) {
      $key = $meta->meta_key;
    ?>
      <option value='<?php echo $key ?>' <?php echo ($key == $io6_configuration->selectedPartNumberField ? 'selected' : '')  ?>><?php echo $key ?></option>
    <?php
    }
    ?>
  </select>

<?php

}

function io6_render_select_ean_field($args) {
  global $wpdb, $io6_configuration;
  $sql = "SELECT DISTINCT pm.meta_key FROM $wpdb->posts p
          INNER JOIN $wpdb->postmeta pm ON pm.post_id = p.ID
          WHERE p.post_type='product' AND LEFT(pm.meta_key, 4) <> 'io6_' AND pm.meta_key <> '_sku'";

  $metas = $wpdb->get_results($wpdb->prepare($sql));
?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[select_ean_field]">
    <option value="" <?php echo $io6_configuration->selectedEanField == '' ? 'selected' : '' ?>><?php _e('Non utilizzare', IO6_DOMAIN); ?></option>
    <option value="io6_eancode" <?php echo $io6_configuration->selectedEanField == 'io6_eancode' ? 'selected' : '' ?>><?php printf(__('Gestito da %s', IO6_DOMAIN), IO6_PLUGIN_NAME); ?></option>
    <?php
    foreach ($metas as $meta) {
      $key = $meta->meta_key;
    ?>
      <option value='<?php echo $key ?>' <?php echo ($key == $io6_configuration->selectedEanField ? 'selected' : '')  ?>><?php echo $key ?></option>
    <?php
    }
    ?>
  </select>

<?php

}


function io6_render_manage_categories($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageCategories;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_categories]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento delle categorie prodotto.
  </label>

<?php

}

function io6_render_manage_title($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageTitle;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_title]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento dei titolo prodotto.
  </label>

<?php

}

function io6_render_manage_content($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageContent;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_content]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento del contenuto prodotto.
  </label>

<?php

}

function io6_render_manage_excerpt($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageExcerpt;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_excerpt]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento della breve descrizione del prodotto.
  </label>

<?php

}


function io6_render_manage_prices($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->managePrices;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_prices]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento dei prezzi del prodotto.
  </label>

<?php

}

function io6_render_manage_images($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageImages;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_images]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento delle immagini prodotto.
  </label>

<?php

}


function io6_render_manage_features($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageFeatures;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_features]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento degli attributi prodotto.
  </label>

<?php

}


function io6_render_manage_features_html($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->manageFeaturesHTML;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[manage_features_html]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Abilita l'aggiornamento della scheda tecnica.
  </label>

<?php

}


function io6_render_concat_features_html($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->concatFeaturesHTML;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[concat_features_html]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Accoda la scheda tecnica al contenuto del prodotto.
  </label>

<?php

}

function io6_render_features_html_template($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->featuresHTMLTemplate;
?>
   <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[features_html_template]">
      <option value='0' <?php echo ($io6_configuration->featuresHTMLTemplate == 0 ? 'selected' : '')  ?>><?php echo _e('Default',IO6_DOMAIN) ?></option>
      <option value='1' <?php echo ($io6_configuration->featuresHTMLTemplate == 1 ? 'selected' : '')  ?>><?php echo _e('Bootstrap 4.x',IO6_DOMAIN) ?></option>
      <option value='2' <?php echo ($io6_configuration->featuresHTMLTemplate == 2 ? 'selected' : '')  ?>><?php echo _e('Bootstrap 5.x (Plugin MASONRY necessario)', IO6_DOMAIN) ?></option>
  </select>
  <label for="<?php echo esc_attr($args['id']); ?>">
    Seleziona il template da utilizzare per la scheda tecnica.
  </label>

<?php

}




function io6_render_pagesize($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->pageSize;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[page_size]" class="small-text" type="number" value="<?php echo $defaultValue; ?>" />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Indica quanti prodotti elaborare ad ogni richiesta durante le esecuzioni pianificate
  </label>

<?php

}


function io6_render_image_limit($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->imageLimit;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[image_limit]" min="0" class="small-text" type="number" value="<?php echo $defaultValue; ?>" />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Indica il numero massimo di immagini da importare: 0 = tutte
  </label>

<?php

}

/*
function io6_render_exclude_status($args) {
  global $io6_options;
  $defaultValue = $io6_options === false ? 1 : $io6_options['exclude_status'];
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[exclude_status]" type="checkbox" value="1" <?php echo ($defaultValue == 1 ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Disattiva prodotti obsoleti
  </label>

<?php

}*/

function io6_render_exclude_noimage($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->excludeNoImage;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[exclude_noimage]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Disattiva prodotti senza immagini
  </label>

<?php

}


function io6_render_exclude_avail_lessthan($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->excludeAvailLessThan;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[exclude_avail_lessthan]" min="0" class="small-text" type="number" value="<?php echo $defaultValue; ?>" />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Indica la disponibilità minima dei prodotti da importare. 0 = Nessuna verifica sulla disponibilità 
  </label>

<?php

}

function io6_render_exclude_avail_type($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->excludeAvailType;
?>
  <select id="<?php echo esc_attr($args['id']); ?>" name="io6_options[exclude_avail_type]">
      <option value='0' <?php echo ($io6_configuration->excludeAvailType == 0 ? 'selected' : '')  ?>><?php echo _e('Disponibilità',IO6_DOMAIN) ?></option>
      <option value='1' <?php echo ($io6_configuration->excludeAvailType == 1 ? 'selected' : '')  ?>><?php echo _e('In Arrivo',IO6_DOMAIN) ?></option>
      <option value='2' <?php echo ($io6_configuration->excludeAvailType == 2 ? 'selected' : '')  ?>><?php echo _e('Entrambe',IO6_DOMAIN) ?></option>
  </select>
  <label for="<?php echo esc_attr($args['id']); ?>">
    Indica il tipo di disponibilità da considerare per la regola precedente.
  </label>

<?php

}

/*
function io6_exclude_avail_type($args) {
  global $io6_configuration;
  $defaultValue = $io6_configuration->excludeUnAvail;
?>
  <input id="<?php echo esc_attr($args['id']); ?>" name="io6_options[exclude_unavail]" type="checkbox" value="1" <?php echo ($defaultValue ? 'checked' : '') ?> />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Non creare prodotti non disponibili
   </label>
 <?php
}
*/


function io6_render_cron_cmd($args) {

  $defaultValue = "php " . plugin_dir_path(__DIR__) . 'wp-connect-io6-cron.php ' . site_url() . '/wp-admin/admin-ajax.php?action=io6-sync&fastsync=false';
?>
  <input id="<?php echo esc_attr($args['id']); ?>" class="large-text" type="text" readonly value="<?php echo $defaultValue; ?>" />
  <label for="<?php echo esc_attr($args['id']); ?>">
    Per configurare un task pianificato copiare il comando mostrato
  </label>

<?php

}
