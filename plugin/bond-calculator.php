<?php

/**
 * Plugin Name: Bond Calculator
 * Description: Simple bond calculator with Svelte admin interface
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include main classes
require_once BC_PLUGIN_PATH . 'includes/class-calculator.php';
require_once BC_PLUGIN_PATH . 'includes/class-admin.php';

class BondCalculator
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init()
    {
        // Initialize admin interface
        new BC_Admin();

        // Initialize API routes
        add_action('rest_api_init', function () {
            $api = new BC_API();
            $api->register_routes();
        });

        // Add shortcode for frontend
        add_shortcode('bond_calculator', array($this, 'frontend_shortcode'));
    }

    public function frontend_shortcode($atts)
    {
        return '<div id="bond-calculator-frontend">Frontend calculator will go here</div>';
    }

    public function activate()
    {
        // Create database tables
        BC_Database::create_tables();

        // Create templates directory
        $templates_dir = BC_PLUGIN_PATH . 'templates';
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }

        // Set default template if not exists
        if (!get_option('bc_pdf_template')) {
            update_option('bc_pdf_template', $this->get_default_template());
        }
    }

    private function get_default_template()
    {
        $template_file = BC_PLUGIN_PATH . 'templates/pdf-template.html';

        if (!file_exists($template_file)) {
            error_log('Bond Calculator: Template file not found at ' . $template_file);
            return '<html><body><h1>Template Error</h1><p>Template file not found.</p></body></html>';
        }

        return file_get_contents($template_file);
    }
}

// Initialize the plugin
new BondCalculator();
