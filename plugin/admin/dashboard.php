<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$js_file = BC_PLUGIN_PATH . 'dist/admin.js';
$css_file = BC_PLUGIN_PATH . 'dist/app.css'; // Changed from admin.css to app.css
$build_exists = file_exists($js_file) && file_exists($css_file);
?>
<div class="wrap">
    <?php if (!$build_exists): ?>
        <div class="notice notice-warning">
            <h2>Build Required</h2>
            <p>The Svelte admin interface needs to be built. Run these commands in your project root:</p>
            <pre style="background: #f1f1f1; padding: 10px; margin: 10px 0;">
npm install
npm run build
            </pre>
            <p>Then refresh this page.</p>
        </div>
    <?php else: ?>
        <div id="bc-admin-app">
            <!-- Svelte component will mount here -->
            
        </div>
    <?php endif; ?>
</div>
