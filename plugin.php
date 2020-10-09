<?php

use Afatoga\AutoLoader;
use Afatoga\Api\RestServer;

/**
 * Plugin.
 *
 * @package af-restserver
 * @wordpress-plugin
 *
 * Plugin Name:     af_restserver
 * Description:     REST API for a custom theme
 * Author:          Afatoga
 * Author URL:      https://leoweb.cz
 * Version:         0.1
 * Domain Path:     /
 */


 /**
 * Autoloader
 */
require plugin_dir_path( __FILE__ ).'/app/AutoLoader.php';
$loader = new AutoLoader();
$loader->register();
$loader->addNamespace( 'Afatoga', plugin_dir_path( __FILE__ ).'/app' ); 

/**
 * WP API hook
 */

add_action("rest_api_init", function () {
  $af_restserver = new RestServer();
  $af_restserver->register_routes();
});
