<?php 

add_shortcode("io6-features-html", function($atts) {
  global $post;
  return html_entity_decode(get_post_meta($post->ID, 'io6_features_html', true));
});

if(!is_admin()) return;

/**
 * Plugin Name: ImporterONE Cloud WP Connector
 * Plugin URI: https://www.imprimis.it
 * Description: ImporterONE Cloud Connector
 * Version: 1.1.3
 * Author: IMPRIMIS Srl
 * Author URI: https://www.imprimis.it
 */

//Parametri per checkrequirements
define('IO6_PLUGIN_NAME', 'ImporterONE Cloud Connector');
define('IO6_DOMAIN', 'io6-wp-connect');
define('IO6_PHP_MIN', '7.4.13');
define('IO6_PHP_MAX', '7.4.33');
define('IO6_WOOCOMMERCE_MIN', '5.4.0');
define('IO6_WOOCOMMERCE_MAX', '7.1.0');
define('IO6_MAX_EXECUTION_TIME', 300);
define('IO6_MEMORY_LIMIT', 512);


define('IO6_LOG_INFO', 'INFO');
define('IO6_LOG_WARNING', 'WARNING');
define('IO6_LOG_ERROR', 'ERROR');

$upload_dir = wp_upload_dir();
$logs_path = $upload_dir['basedir'] . '/io6-logs/';
$logFile = $logs_path . date('Ymd'). '.txt';

if (!wp_mkdir_p($logs_path))
	throw new Exception('Cannot create log folder!');

require_once('core/src/classes/IO6ConnectEngine.php');


$io6_configuration = new IO6ConnectConfiguration(get_option('io6_options'));
$io6Engine = new IO6ConnectEngine($io6_configuration);


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
 

function io6_test_api() {
	global $io6Engine;
	try {
		$results = $io6Engine->TestAPI($_GET['ep'], $_GET['t']);
	}
	catch(Exception $ex) {
		$results = null;
	}
	
	status_header(200);

  echo isset($results) ? json_encode($results) : '{}';
  wp_die();
}

function io6_sync() {
  
	foreach (array('transition_post_status', 'save_post', 'pre_post_update', 'add_attachment', 'edit_attachment', 'edit_post', 'post_updated', 'wp_insert_post', 'save_post_product') as $act) {
		remove_all_actions($act);
	}

	$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
  $fastSync = isset($_GET['fastsync']) ? (bool)($_GET['fastsync']) : 0;

	if($currentPage == 1 && !$fastSync) {				
		syncBrands();
		syncSuppliers();
		syncCategories();	
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
				
		error_log($logMessage, 3, $logFile);
		
	}
}


?>
