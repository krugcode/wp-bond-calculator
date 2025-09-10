<?php

class BC_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Bond Calculator',
            'Bond Calculator',
            'manage_options',
            'bond-calculator',
            array($this, 'admin_page'),
            'dashicons-calculator',
            26
        );
    }

    public function admin_page()
    {
        include BC_PLUGIN_PATH . 'admin/dashboard.php';
    }

    private function is_vite_dev_server_running()
    {
        $vite_dev_url = 'http://localhost:5173';
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'method' => 'HEAD'
            ]
        ]);
        return @file_get_contents($vite_dev_url, false, $context) !== false;
    }

    public function enqueue_admin_scripts($hook)
    {
        // Only load on our admin page
        if ('toplevel_page_bond-calculator' !== $hook) {
            return;
        }

        if ($this->is_vite_dev_server_running()) {
            // Development mode - load from Vite dev server
            wp_enqueue_script(
                'bc-admin-js',
                'http://localhost:5173/src/admin.js',
                array(),
                null,
                true
            );
            // Note: Vite handles CSS injection automatically in dev mode
        } else {
            // Production mode - load built assets
            $js_file = BC_PLUGIN_PATH . 'admin/dist/admin.js';
            $css_file = BC_PLUGIN_PATH . 'admin/dist/app.css';

            if (file_exists($js_file) && file_exists($css_file)) {
                wp_enqueue_script(
                    'bc-admin-js',
                    BC_PLUGIN_URL . 'admin/dist/admin.js',
                    array(),
                    filemtime($js_file), // Use file modification time for cache busting
                    true
                );
                wp_enqueue_style(
                    'bc-admin-css',
                    BC_PLUGIN_URL . 'admin/dist/app.css',
                    array(),
                    filemtime($css_file)
                );
            } else {
                // Show admin notice if built files don't exist
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Bond Calculator: Please run <code>npm run build</code> to build the admin assets.</p></div>';
                });
                return;
            }
        }

        // Pass data to JavaScript (works in both dev and prod)
        wp_localize_script('bc-admin-js', 'bcAdmin', array(
            'apiUrl' => rest_url('bond-calculator/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isDev' => $this->is_vite_dev_server_running()
        ));
    }
}
