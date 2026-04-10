<?php
/**
 * Klaw SEO — XML Sitemap
 *
 * Generates a sitemap index at /sitemap.xml and per-type sitemaps at /sitemap-{type}.xml.
 * Excludes noindexed posts. Paginates at 1000 URLs per sitemap.
 * Pings Google and Bing on post publish when enabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Sitemap {

    /**
     * Maximum URLs per sitemap page.
     */
    const PER_PAGE = 1000;

    /**
     * Constructor — register hooks.
     */
    public function __construct() {
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'render' ] );
        add_action( 'transition_post_status', [ $this, 'maybe_ping' ], 10, 3 );

        // Remove WordPress core sitemaps to avoid conflict.
        add_filter( 'wp_sitemaps_enabled', '__return_false' );
    }

    /**
     * Register rewrite rules for sitemap URLs.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( '^sitemap\.xml$', 'index.php?klaw_sitemap=index', 'top' );
        // Also serve the WordPress core sitemap URL since core sitemaps are disabled.
        add_rewrite_rule( '^wp-sitemap\.xml$', 'index.php?klaw_sitemap=index', 'top' );
        add_rewrite_rule( '^sitemap-([a-z0-9_-]+?)(?:-(\d+))?\.xml$', 'index.php?klaw_sitemap=$matches[1]&klaw_sitemap_page=$matches[2]', 'top' );
    }

    /**
     * Register custom query vars.
     *
     * @param  array $vars Existing query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'klaw_sitemap';
        $vars[] = 'klaw_sitemap_page';
        return $vars;
    }

    /**
     * Intercept the request and render the sitemap if applicable.
     */
    public function render() {
        $sitemap = get_query_var( 'klaw_sitemap' );
        if ( ! $sitemap ) {
            return;
        }

        $settings = get_option( 'klaw_seo_settings', [] );
        if ( empty( $settings['sitemap_enabled'] ) ) {
            return;
        }

        header( 'Content-Type: application/xml; charset=UTF-8' );
        header( 'X-Robots-Tag: noindex' );

        if ( $sitemap === 'index' ) {
            echo $this->build_index( $settings );
        } else {
            $page = max( 1, (int) get_query_var( 'klaw_sitemap_page', 1 ) );
            echo $this->build_sitemap( $sitemap, $page, $settings );
        }

        exit;
    }

    /**
     * Build the sitemap index XML.
     *
     * @param  array  $settings Plugin settings.
     * @return string           XML string.
     */
    private function build_index( $settings ) {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $types = $this->get_enabled_types( $settings );

        foreach ( $types as $type ) {
            $count   = $this->get_post_count( $type );
            $pages   = max( 1, ceil( $count / self::PER_PAGE ) );
            $lastmod = $this->get_type_lastmod( $type );

            for ( $i = 1; $i <= $pages; $i++ ) {
                $suffix = $i > 1 ? '-' . $i : '';
                $url    = home_url( "/sitemap-{$type}{$suffix}.xml" );
                $xml   .= "  <sitemap>\n";
                $xml   .= "    <loc>" . esc_url( $url ) . "</loc>\n";
                if ( $lastmod ) {
                    $xml .= "    <lastmod>" . esc_html( $lastmod ) . "</lastmod>\n";
                }
                $xml   .= "  </sitemap>\n";
            }
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Build a single post-type sitemap.
     *
     * @param  string $type     Post type slug.
     * @param  int    $page     Page number.
     * @param  array  $settings Plugin settings.
     * @return string           XML string.
     */
    private function build_sitemap( $type, $page, $settings ) {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $types = $this->get_enabled_types( $settings );
        if ( ! in_array( $type, $types, true ) ) {
            $xml .= '</urlset>';
            return $xml;
        }

        $offset = ( $page - 1 ) * self::PER_PAGE;

        $posts = get_posts( [
            'post_type'      => $type,
            'post_status'    => 'publish',
            'posts_per_page' => self::PER_PAGE,
            'offset'         => $offset,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'OR',
                [
                    'key'     => '_klaw_seo_noindex',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_klaw_seo_noindex',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ],
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ] );

        foreach ( $posts as $post_id ) {
            $url  = get_permalink( $post_id );
            $mod  = get_post_modified_time( 'Y-m-d\TH:i:sP', true, $post_id );

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url( $url ) . "</loc>\n";
            $xml .= "    <lastmod>" . esc_html( $mod ) . "</lastmod>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Get list of post types enabled in sitemap settings.
     *
     * @param  array $settings Plugin settings.
     * @return array           Post type slugs.
     */
    private function get_enabled_types( $settings ) {
        $public = get_post_types( [ 'public' => true ], 'names' );
        unset( $public['attachment'] );

        $enabled = [];
        foreach ( $public as $pt ) {
            $key = 'sitemap_post_type_' . $pt;
            // Default to enabled for post and page when not explicitly set.
            if ( isset( $settings[ $key ] ) ) {
                if ( ! empty( $settings[ $key ] ) ) {
                    $enabled[] = $pt;
                }
            } elseif ( in_array( $pt, [ 'post', 'page' ], true ) ) {
                $enabled[] = $pt;
            }
        }

        return $enabled;
    }

    /**
     * Get the most recent modification time for a post type as ISO 8601.
     *
     * @param  string $type Post type.
     * @return string       ISO 8601 datetime or empty string.
     */
    private function get_type_lastmod( $type ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $mod = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_modified_gmt FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status = 'publish'
                 ORDER BY post_modified_gmt DESC LIMIT 1",
                $type
            )
        );

        if ( ! $mod || $mod === '0000-00-00 00:00:00' ) {
            return '';
        }

        return mysql2date( 'Y-m-d\TH:i:sP', $mod, false );
    }

    /**
     * Count published, indexable posts of a given type.
     *
     * @param  string $type Post type.
     * @return int
     */
    private function get_post_count( $type ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_klaw_seo_noindex'
                 WHERE p.post_type = %s
                   AND p.post_status = 'publish'
                   AND (pm.meta_value IS NULL OR pm.meta_value != '1')",
                $type
            )
        );
    }

    /**
     * Ping search engines when a post transitions to 'publish'.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     */
    public function maybe_ping( $new_status, $old_status, $post ) {
        if ( $new_status !== 'publish' || $old_status === 'publish' ) {
            return;
        }

        $settings = get_option( 'klaw_seo_settings', [] );
        if ( empty( $settings['sitemap_enabled'] ) || empty( $settings['sitemap_ping'] ) ) {
            return;
        }

        $types = $this->get_enabled_types( $settings );
        if ( ! in_array( $post->post_type, $types, true ) ) {
            return;
        }

        $sitemap_url = urlencode( home_url( '/sitemap.xml' ) );

        // Ping in background — non-blocking.
        wp_remote_get( 'https://www.google.com/ping?sitemap=' . $sitemap_url, [
            'blocking' => false,
            'timeout'  => 5,
        ] );
        wp_remote_get( 'https://www.bing.com/ping?sitemap=' . $sitemap_url, [
            'blocking' => false,
            'timeout'  => 5,
        ] );
    }
}
