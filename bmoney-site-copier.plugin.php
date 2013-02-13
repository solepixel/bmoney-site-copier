<?php
/**
 * Plugin Name: BMoney Site Copier
 * Plugin URI: https://github.com/solepixel/bmoney-site-copier/
 * Description: Copy a site from one domain to another.
 * Version: 1.1
 * Author: Brian DiChiara
 * Author URI: http://www.briandichiara.com
 */

define('BMSC_VERSION', '1.1');
define('BMSC_PI_NAME', 'Site Copier');
define('BMSC_PI_DESCRIPTION', 'Copy a site from one domain to another.');
define('BMSC_OPT_PREFIX', 'bmcm_');
define('BMSC_PATH', plugin_dir_path( __FILE__ ));
define('BMSC_DIR', plugin_dir_url( __FILE__ ));

include_once(BMSC_PATH.'inc/functions.php');
include_once(BMSC_PATH.'updater/updater.php');
require_once(BMSC_PATH.'classes/str_replace_db.class.php');
require_once(BMSC_PATH.'classes/bm-site-copier.class.php');

global $bmsc_plugin;
$bmsc_plugin = new BM_Site_Copier();
$bmsc_plugin->initialize();