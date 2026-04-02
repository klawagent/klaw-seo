<?php
/**
 * Plugin Name: Klaw SEO
 * Plugin URI:  https://welcomein.io
 * Description: Lightweight, agency-grade WordPress SEO plugin. Meta titles, descriptions, Open Graph, sitemaps, schema markup, redirects, and more.
 * Version:     1.0.0
 * Author:      Welcome In
 * Author URI:  https://welcomein.io
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: klaw-seo
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'KLAW_SEO_VERSION', '1.0.0' );
define( 'KLAW_SEO_FILE', __FILE__ );
define( 'KLAW_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'KLAW_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin activation.
 */
function klaw_seo_activate() {
    require_once KLAW_SEO_DIR . 'includes/class-redirects.php';
    require_once KLAW_SEO_DIR . 'includes/class-broken-links.php';
    require_once KLAW_SEO_DIR . 'includes/class-sitemap.php';
    Klaw_SEO_Redirects::create_table();
    Klaw_SEO_Broken_Links::create_table();
    update_option( 'klaw_seo_db_version', KLAW_SEO_VERSION );

    // Register sitemap rewrite rules before flushing so they get written.
    $sitemap = new Klaw_SEO_Sitemap();
    $sitemap->add_rewrite_rules();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'klaw_seo_activate' );

/**
 * Plugin deactivation.
 */
function klaw_seo_deactivate() {
    wp_clear_scheduled_hook( 'klaw_seo_check_links' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'klaw_seo_deactivate' );

/**
 * Load plugin files.
 */
function klaw_seo_init() {
    require_once KLAW_SEO_DIR . 'includes/class-meta-box.php';
    require_once KLAW_SEO_DIR . 'includes/class-head-output.php';
    require_once KLAW_SEO_DIR . 'includes/class-sitemap.php';
    require_once KLAW_SEO_DIR . 'includes/class-schema.php';
    require_once KLAW_SEO_DIR . 'includes/class-redirects.php';
    require_once KLAW_SEO_DIR . 'includes/class-broken-links.php';
    require_once KLAW_SEO_DIR . 'includes/class-alt-text.php';
    require_once KLAW_SEO_DIR . 'includes/class-robots.php';
    require_once KLAW_SEO_DIR . 'includes/class-tracking-output.php';
    require_once KLAW_SEO_DIR . 'admin/class-settings-page.php';
    require_once KLAW_SEO_DIR . 'admin/class-admin-columns.php';

    new Klaw_SEO_Meta_Box();
    new Klaw_SEO_Head_Output();
    new Klaw_SEO_Sitemap();
    new Klaw_SEO_Schema();
    new Klaw_SEO_Redirects();
    new Klaw_SEO_Broken_Links();
    new Klaw_SEO_Alt_Text();
    new Klaw_SEO_Robots();
    new Klaw_SEO_Tracking_Output();
    new Klaw_SEO_Settings();
    new Klaw_SEO_Admin_Columns();
}
add_action( 'plugins_loaded', 'klaw_seo_init' );

/**
 * Enqueue admin assets.
 */
function klaw_seo_admin_assets( $hook ) {
    $screen = get_current_screen();
    $is_edit = in_array( $hook, [ 'post.php', 'post-new.php' ], true );
    $is_settings = $screen && strpos( $screen->id, 'klaw-seo' ) !== false;

    if ( ! $is_edit && ! $is_settings ) {
        return;
    }

    wp_enqueue_style(
        'klaw-seo-admin',
        KLAW_SEO_URL . 'assets/css/admin.css',
        [],
        KLAW_SEO_VERSION
    );

    if ( $is_settings ) {
        wp_enqueue_media();
    }

    if ( $is_edit ) {
        wp_enqueue_media();
        wp_enqueue_script(
            'klaw-seo-meta-box',
            KLAW_SEO_URL . 'assets/js/meta-box.js',
            [],
            KLAW_SEO_VERSION,
            true
        );
        $settings = get_option( 'klaw_seo_settings', [] );
        wp_localize_script( 'klaw-seo-meta-box', 'klawSeoData', [
            'siteTitle'     => get_bloginfo( 'name' ),
            'separator'     => $settings['title_separator'] ?? '|',
            'titleTemplate' => $settings[ 'title_template_' . ( $screen->post_type ?? 'post' ) ] ?? '{post_title} {sep} {site_title}',
        ] );

        wp_enqueue_script(
            'klaw-seo-faq',
            KLAW_SEO_URL . 'assets/js/faq-repeater.js',
            [],
            KLAW_SEO_VERSION,
            true
        );
    }
}
add_action( 'admin_enqueue_scripts', 'klaw_seo_admin_assets' );

/**
 * Conflict detection — warn if another SEO plugin is active.
 */
function klaw_seo_conflict_notice() {
    $conflicts = [];
    if ( defined( 'WPSEO_VERSION' ) ) {
        $conflicts[] = 'Yoast SEO';
    }
    if ( class_exists( 'RankMath' ) ) {
        $conflicts[] = 'Rank Math';
    }
    if ( class_exists( 'AIOSEO\\Plugin\\AIOSEO' ) ) {
        $conflicts[] = 'All in One SEO';
    }
    if ( empty( $conflicts ) ) {
        return;
    }
    $names = implode( ', ', $conflicts );
    echo '<div class="notice notice-warning"><p><strong>Klaw SEO:</strong> ' .
         esc_html( $names ) . ' is also active. Please deactivate it to avoid duplicate SEO output.</p></div>';
}
add_action( 'admin_notices', 'klaw_seo_conflict_notice' );

/**
 * Helper: get a plugin setting with default.
 */
function klaw_seo_get( $key, $default = '' ) {
    $settings = get_option( 'klaw_seo_settings', [] );
    return $settings[ $key ] ?? $default;
}
