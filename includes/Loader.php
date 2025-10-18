<?php

namespace WPAutoPosts;

class WPAutoPostsLoader
{
    public function __construct()
    {
        add_action('init', [$this, 'registerCron']);
        add_action('wp_auto_posts_daily', [$this, 'runImport']);
    }

    public function registerCron()
    {
        if (!wp_next_scheduled('wp_auto_posts_daily')) {
            wp_schedule_event(time(), 'daily', 'wp_auto_posts_daily');
        }
    }

    public function runImport()
    {
        $importer = new WPAutoPostsImporter();
        $importer->import();
    }
}
