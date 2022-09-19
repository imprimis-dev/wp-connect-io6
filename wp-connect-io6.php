<?php 
if(!is_admin()) return;

/**
 * Plugin Name: ImporterONE Cloud WP Connector
 * Plugin URI: https://www.imprimis.it
 * Description: ImporterONE Cloud Connector
 * Version: 1.1.1
 * Author: IMPRIMIS Srl
 * Author URI: https://www.imprimis.it
 */

//Parametri per checkrequirements
define('IO6_PLUGIN_NAME', 'ImporterONE Cloud Connector');
//define('IO6_PREFIX', 'io6_');
define('IO6_DOMAIN', 'io6-wp-connect');
define('IO_PHP_MIN', '7.4.13');
define('IO_PHP_MAX', '7.4.13');
define('IO_WOOCOMMERCE_MIN', '3.0.0');
define('IO_WOOCOMMERCE_MAX', '6.8.2');
define('IO_MAX_EXECUTION_TIME', 300);
define('IO_MEMORY_LIMIT', 512);

define('IO6_LOG_INFO', 'INFO');
define('IO6_LOG_WARNING', 'WARNING');
define('IO6_LOG_ERROR', 'ERROR');

$upload_dir = wp_upload_dir();
$logs_path = $upload_dir['basedir'] . '/io6-logs/';
$logFile = $logs_path . date('Ymd'). '.txt';

if (!wp_mkdir_p($logs_path))
	throw new Exception('Cannot create log folder!');

require_once('core/src/classes/IO6ConnectEngine.class.php');


$io6_configuration = new IO6ConnectConfiguration(get_option('io6_options'));
$io6Engine = new IO6ConnectEngine($io6_configuration);


$_catalogs = [];
$_pricelists = [];
try {
	if(!$io6Engine->CheckApiConnection())
		$_catalogs = false;
	else
		$_catalogs = $io6Engine->GetIO6Catalogs();
	$set_catalogId = 0;

	if(!empty($_catalogs)) {
		$set_catalogId = !empty(array_filter($_catalogs, function ($catalog) use($io6_configuration) {
			return $catalog->id == $io6_configuration->catalogId;
		}));
	}

	if(!$set_catalogId)
		$io6_configuration->catalogId = 0;

	try {
		$_pricelists = $io6Engine->GetIO6PriceLists();
		$set_priceListId = 0;
		
		if(!empty($_pricelists)) {
			$set_priceListId = !empty(array_filter($_pricelists, function ($pricelist) use($io6_configuration) {
				return $pricelist->id == $io6_configuration->priceListId;
			}));
		}

		if(!$set_priceListId)
			$io6_configuration->priceListId = 0;
	}
	catch(Exception $e) {
		$_pricelists = false;
		$io6_configuration->priceListId = 0;
	}
}
catch(Exception $e) {
	$_catalogs = false;	
	$io6_configuration->catalogId = 0;
	
}




require_once('admin/wp-connect-io6-customfields.php');
require_once('admin/wp-connect-io6-admin-page.php');

require_once('wp-connect-io6-functions.php');

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 
	function ( $links ) {
		$action_links = array(
			'<a href="' . admin_url('admin.php?page=io6-main-menu' ) . '">' . __('Impostazioni', IO6_DOMAIN) .'</a>',
		);
		$actions = array_merge( $action_links, $links);
		return $actions;
	}
);
 
add_shortcode("io6-features-html", function($atts) {
  global $post;
  return html_entity_decode(get_post_meta($post->ID, 'io6_features_html', true));
});

function io6_sync() {
  
  //TODO: EM20210407 => security check validation

	foreach (array('transition_post_status', 'save_post', 'pre_post_update', 'add_attachment', 'edit_attachment', 'edit_post', 'post_updated', 'wp_insert_post', 'save_post_product') as $act) {
		remove_all_actions($act);
	}

	$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
  $fastSync = isset($_GET['fastsync']) ? (bool)($_GET['fastsync']) : 0;

	if($currentPage == 1 && !$fastSync) {		
		syncCategories();	
		syncBrands();
	}
  
	$results = syncProducts($currentPage, $fastSync);
	
  status_header(200);

  echo isset($results) ? json_encode($results) : '{}';
  wp_die();
}

if($io6_configuration->concatFeaturesHTML) {
  add_filter('the_content', function ($content) {
    $postId = get_the_ID();
    $postType = get_post_type($postId);
    if($postType == 'product') {
      $featuresHtml = html_entity_decode(get_post_meta($postId, 'io6_features_html', true));
      if(isset($featuresHtml) && !empty($featuresHtml))
        $content .= '<h2>' . __('Scheda tecnica', 'icecool') . '</h2>' . $featuresHtml;
    }
    return $content;
  });
}


if (!function_exists('io6_write_log')) {
	function io6_write_log($log, $level) {		
		global $logFile;
		$time = date('Y-m-d H:i:s');
		$logMessage = sprintf("%s - %s: %s\r\n", $time, $level, (is_array($log) || is_object($log) ? print_r($log, true) : $log));
		//if (true === WP_DEBUG) {
		
		error_log($logMessage, 3, $logFile);
		
		//}
	}
}


?>
