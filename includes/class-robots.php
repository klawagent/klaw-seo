<?php
/**
 * Klaw SEO — Robots.txt
 *
 * Filters the virtual robots.txt output to use custom content from settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Robots {

    /**
     * Constructor — hook into robots_txt filter.
     */
    public function __construct() {
        add_filter( 'robots_txt', [ $this, 'filter_robots' ], 999, 2 );
    }

    /**
     * Replace the default robots.txt content with our custom content.
     *
     * @param  string $output Default robots.txt content.
     * @param  bool   $public Blog visibility setting.
     * @return string         Custom robots.txt content.
     */
    public function filter_robots( $output, $public ) {
        $settings = get_option( 'klaw_seo_settings', [] );
        $content  = $settings['robots_content'] ?? '';

        if ( ! $content ) {
            return $this->get_default_content();
        }

        return $content;
    }

    /**
     * Get the default robots.txt content.
     *
     * @return string
     */
    public function get_default_content() {
        $sitemap_url = home_url( '/sitemap.xml' );

        $content  = "User-agent: *\n";
        $content .= "Disallow: /wp-admin/\n";
        $content .= "Allow: /wp-admin/admin-ajax.php\n";
        $content .= "\n";
        $content .= "Sitemap: {$sitemap_url}\n";

        return $content;
    }
}
