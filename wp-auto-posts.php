<?php

/**
 * Plugin Name:       WP Auto Posts Importer
 * Plugin URI:        https://t.me/MerelyiGor
 * Description: Імпортує статті з API щодня через WP Cron.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:           Merelyigor
 * Author URI:       https://t.me/MerelyiGor
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

# constant plugin
const WP_AUTO_POST_PLUGIN_PATH = __FILE__;
const WP_AUTO_POST_PLUGIN_DIR_PATH = __DIR__;
const WP_AUTO_POST_PLUGIN_LIMIT_LOG_DB = 10;

require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/Loader.php';
require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/APIClient.php';
require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/PostImporter.php';
require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/MediaHandler.php';
require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/WPAutoPostsLogger.php';
require_once plugin_dir_path(WP_AUTO_POST_PLUGIN_PATH) . 'includes/WPAutoPostsShortcode.php';

/** Завантаження логіки імпорту по крону **/
new WPAutoPosts\WPAutoPostsLoader();

/** Сторінка в адмінці та логування **/
new WPAutoPosts\WPAutoPostsLogger();

/** Завантаження логіки шорткоду **/
new WPAutoPosts\WPAutoPostsShortcode();

/**
 * вставка у functions.php для примусового імпорту
 * при завантаженні сторінки /?WP_Auto_Posts_Loader=1
 */
//if (isset($_GET['WP_Auto_Posts_Loader']) && $_GET['WP_Auto_Posts_Loader'] == 1) {
//    (new WPAutoPosts\WPAutoPostsLoader())->runImport();
//}
