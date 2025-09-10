<?php

/**
 * Plugin Name: Bond Calculator
 * Description: Bond and transfer cost calculator with PDF generation
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

// Include main class
require_once BC_PLUGIN_PATH . 'includes/class-bond-calculator.php';

// Initialize plugin
function bond_calculator_init()
{
    new BondCalculator();
}
add_action('plugins_loaded', 'bond_calculator_init');

// Activation hook
register_activation_hook(__FILE__, 'bond_calculator_activate');
function bond_calculator_activate()
{
    // Create database tables, set defaults, etc.
}
