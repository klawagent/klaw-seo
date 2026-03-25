<?php
/**
 * Klaw SEO — Broken Link Checker
 *
 * Custom table via dbDelta, WP Cron scheduled scanning,
 * DOMDocument link extraction, wp_remote_head batch checks,
 * dashboard widget, and email notifications.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Broken_Links {

    /**
     * Database table name (without prefix).
     */
    const TABLE = 'klaw_seo_broken_links';

    /**
     * Batch size for HTTP checks.
     */
    const BATCH_SIZE = 50;

    /**
     * Constructor — register hooks.
     */
    public function __construct() {
        add_action( 'klaw_seo_check_links', [ $this, 'run_scan' ] );
        add_action( 'admin_init', [ $this, 'schedule_scan' ] );
        add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );
        add_action( 'admin_post_klaw_seo_dismiss_broken_link', [ $this, 'handle_dismiss' ] );

        // Register custom cron interval.
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedule' ] );
    }

    /**
     * Add weekly cron schedule.
     *
     * @param  array $schedules Existing schedules.
     * @return array
     */
    public function add_cron_schedule( $schedules ) {
        $schedules['weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display'  => __( 'Once Weekly', 'klaw-seo' ),
        ];
        return $schedules;
    }

    /**
     * Create the broken links table via dbDelta.
     */
    public static function create_table() {
        global $wpdb;

        $table   = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            url VARCHAR(2083) NOT NULL,
            status_code SMALLINT(3) NOT NULL DEFAULT 0,
            last_checked DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY url_hash (url(191))
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Schedule the cron scan based on settings.
     */
    public function schedule_scan() {
        $settings  = get_option( 'klaw_seo_settings', [] );
        $enabled   = ! empty( $settings['broken_links_enabled'] );
        $frequency = $settings['broken_links_frequency'] ?? 'weekly';
        $hook      = 'klaw_seo_check_links';

        if ( ! $enabled ) {
            $next = wp_next_scheduled( $hook );
            if ( $next ) {
                wp_unschedule_event( $next, $hook );
            }
            return;
        }

        // Map friendly names to WP cron recurrences.
        $recurrence_map = [
            'daily'      => 'daily',
            'twicedaily' => 'twicedaily',
            'weekly'     => 'weekly',
        ];

        $recurrence = $recurrence_map[ $frequency ] ?? 'weekly';

        if ( ! wp_next_scheduled( $hook ) ) {
            wp_schedule_event( time(), $recurrence, $hook );
        }
    }

    /**
     * Run the full broken link scan.
     */
    public function run_scan() {
        global $wpdb;

        $table = $wpdb->prefix . self::TABLE;

        // Use a scan timestamp to track this scan's results.
        $scan_time = current_time( 'mysql' );

        // Get all published posts with content — process in batches.
        $post_types = get_post_types( [ 'public' => true ], 'names' );
        unset( $post_types['attachment'] );

        $type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

        $batch_size = 100;
        $offset     = 0;
        $broken     = [];

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total_posts = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ({$type_placeholders})",
                ...array_values( $post_types )
            )
        );

        while ( $offset < $total_posts ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_content FROM {$wpdb->posts}
                     WHERE post_status = 'publish' AND post_type IN ({$type_placeholders})
                     ORDER BY ID DESC LIMIT %d OFFSET %d",
                    ...array_merge( array_values( $post_types ), [ $batch_size, $offset ] )
                )
            );

            if ( empty( $posts ) ) {
                break;
            }

            $links_to_check = [];

            foreach ( $posts as $post ) {
                $urls = $this->extract_links( $post->post_content );
                foreach ( $urls as $url ) {
                    $links_to_check[] = [
                        'post_id' => $post->ID,
                        'url'     => $url,
                    ];
                }
            }

            // Check links in batches.
            $chunks = array_chunk( $links_to_check, self::BATCH_SIZE );

            foreach ( $chunks as $chunk ) {
                foreach ( $chunk as $link ) {
                    $status = $this->check_url( $link['url'] );

                    if ( $status >= 400 || $status === 0 ) {
                        $broken[] = $link + [ 'status_code' => $status ];

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                        $wpdb->insert(
                            $table,
                            [
                                'post_id'      => $link['post_id'],
                                'url'          => $link['url'],
                                'status_code'  => $status,
                                'last_checked' => $scan_time,
                            ],
                            [ '%d', '%s', '%d', '%s' ]
                        );
                    }
                }
            }

            $offset += $batch_size;
        }

        // Scan completed successfully — remove old results from previous scans.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            $wpdb->prepare( "DELETE FROM {$table} WHERE last_checked < %s", $scan_time )
        );

        // Send email notification if broken links found.
        if ( ! empty( $broken ) ) {
            $this->send_notification( $broken );
        }
    }

    /**
     * Extract URLs from HTML content using DOMDocument.
     *
     * @param  string $content Post HTML content.
     * @return array           Array of URLs.
     */
    private function extract_links( $content ) {
        if ( empty( $content ) ) {
            return [];
        }

        $urls = [];

        // Suppress warnings from malformed HTML.
        $prev = libxml_use_internal_errors( true );

        $doc = new DOMDocument();
        $doc->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $content . '</body></html>',
            LIBXML_NOWARNING | LIBXML_NOERROR
        );

        libxml_clear_errors();
        libxml_use_internal_errors( $prev );

        $anchors = $doc->getElementsByTagName( 'a' );
        foreach ( $anchors as $anchor ) {
            $href = $anchor->getAttribute( 'href' );
            if ( ! $href ) {
                continue;
            }

            // Skip anchors, mailto, tel, javascript.
            if ( preg_match( '/^(#|mailto:|tel:|javascript:)/i', $href ) ) {
                continue;
            }

            // Skip relative URLs that aren't paths.
            if ( strpos( $href, 'http' ) === 0 || strpos( $href, '/' ) === 0 ) {
                // Make relative URLs absolute.
                if ( strpos( $href, '/' ) === 0 ) {
                    $href = home_url( $href );
                }
                $urls[] = $href;
            }
        }

        return array_unique( $urls );
    }

    /**
     * Check a single URL via HEAD request.
     *
     * @param  string $url URL to check.
     * @return int         HTTP status code (0 on connection error).
     */
    private function check_url( $url ) {
        $response = wp_remote_head( $url, [
            'timeout'     => 10,
            'redirection' => 5,
            'sslverify'   => false,
            'user-agent'  => 'Klaw-SEO-Link-Checker/' . KLAW_SEO_VERSION . ' (WordPress)',
        ] );

        if ( is_wp_error( $response ) ) {
            return 0;
        }

        $code = wp_remote_retrieve_response_code( $response );

        // Some servers block HEAD, try GET as fallback for 405.
        if ( $code === 405 ) {
            $response = wp_remote_get( $url, [
                'timeout'     => 10,
                'redirection' => 5,
                'sslverify'   => false,
                'user-agent'  => 'Klaw-SEO-Link-Checker/' . KLAW_SEO_VERSION . ' (WordPress)',
            ] );

            if ( is_wp_error( $response ) ) {
                return 0;
            }

            $code = wp_remote_retrieve_response_code( $response );
        }

        return (int) $code;
    }

    /**
     * Send email notification about broken links.
     *
     * @param array $broken Array of broken link data.
     */
    private function send_notification( $broken ) {
        $settings = get_option( 'klaw_seo_settings', [] );
        $email    = $settings['broken_links_email'] ?? get_option( 'admin_email' );

        if ( ! $email ) {
            return;
        }

        $site_name = get_bloginfo( 'name' );
        $subject   = sprintf( '[%s] Klaw SEO: %d broken links found', $site_name, count( $broken ) );

        $body = sprintf( "Klaw SEO found %d broken links on %s.\n\n", count( $broken ), $site_name );

        $count = 0;
        foreach ( $broken as $link ) {
            $post_title = get_the_title( $link['post_id'] );
            $body      .= sprintf(
                "Post: %s (#%d)\nURL: %s\nStatus: %d\n\n",
                $post_title,
                $link['post_id'],
                $link['url'],
                $link['status_code']
            );
            $count++;
            if ( $count >= 50 ) {
                $body .= sprintf( "... and %d more.\n", count( $broken ) - 50 );
                break;
            }
        }

        $body .= "\nView all results: " . admin_url( 'admin.php?page=klaw-seo-broken-links' ) . "\n";

        wp_mail( $email, $subject, $body );
    }

    /**
     * Register dashboard widget.
     */
    public function register_dashboard_widget() {
        $settings = get_option( 'klaw_seo_settings', [] );
        if ( empty( $settings['broken_links_enabled'] ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'klaw_seo_broken_links_widget',
            __( 'Klaw SEO — Broken Links', 'klaw-seo' ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    /**
     * Render the dashboard widget.
     */
    public function render_dashboard_widget() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( ! $table_exists ) {
            echo '<p>' . esc_html__( 'The broken links scanner has not been run yet.', 'klaw-seo' ) . '</p>';
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

        if ( $count === 0 ) {
            echo '<p>' . esc_html__( 'No broken links detected. Your site is healthy!', 'klaw-seo' ) . '</p>';
        } else {
            printf(
                '<p><strong>%d</strong> %s</p>',
                $count,
                esc_html( _n( 'broken link found.', 'broken links found.', $count, 'klaw-seo' ) )
            );

            // Show latest 5.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $latest = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY last_checked DESC LIMIT 5" );
            if ( $latest ) {
                echo '<ul>';
                foreach ( $latest as $link ) {
                    printf(
                        '<li><code>%s</code> — %d in <a href="%s">%s</a></li>',
                        esc_html( wp_parse_url( $link->url, PHP_URL_PATH ) ?: $link->url ),
                        (int) $link->status_code,
                        esc_url( get_edit_post_link( $link->post_id ) ),
                        esc_html( get_the_title( $link->post_id ) ?: '#' . $link->post_id )
                    );
                }
                echo '</ul>';
            }

            printf(
                '<p><a href="%s" class="button">%s</a></p>',
                esc_url( admin_url( 'admin.php?page=klaw-seo-broken-links' ) ),
                esc_html__( 'View All', 'klaw-seo' )
            );
        }
    }

    /**
     * Handle dismiss broken link action.
     */
    public function handle_dismiss() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'klaw-seo' ) );
        }

        $link_id = absint( $_GET['link_id'] ?? 0 );
        check_admin_referer( 'klaw_seo_dismiss_' . $link_id );

        if ( $link_id ) {
            global $wpdb;
            $table = $wpdb->prefix . self::TABLE;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete( $table, [ 'id' => $link_id ], [ '%d' ] );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-broken-links' ) );
        exit;
    }
}
