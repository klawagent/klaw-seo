<?php
/**
 * Klaw SEO — Redirects
 *
 * Custom database table for URL redirects, template_redirect hook for matching,
 * CRUD admin-post handlers, CSV import/export.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Redirects {

    /**
     * Database table name (without prefix).
     */
    const TABLE = 'klaw_seo_redirects';

    /**
     * Constructor — register hooks.
     */
    public function __construct() {
        add_action( 'template_redirect', [ $this, 'check_redirect' ] );
        add_action( 'admin_post_klaw_seo_save_redirect', [ $this, 'handle_save' ] );
        add_action( 'admin_post_klaw_seo_delete_redirect', [ $this, 'handle_delete' ] );
        add_action( 'admin_post_klaw_seo_import_redirects', [ $this, 'handle_import' ] );
        add_action( 'admin_post_klaw_seo_export_redirects', [ $this, 'handle_export' ] );
    }

    /**
     * Create the redirects table via dbDelta.
     */
    public static function create_table() {
        global $wpdb;

        $table   = $wpdb->prefix . self::TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_url VARCHAR(500) NOT NULL,
            target_url VARCHAR(500) NOT NULL DEFAULT '',
            redirect_type SMALLINT(3) NOT NULL DEFAULT 301,
            hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_url (source_url(191))
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Check for a matching redirect on every front-end request.
     * Uses object cache for performance.
     */
    public function check_redirect() {
        if ( is_admin() ) {
            return;
        }

        $request_uri = trailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
        $cache_key   = 'klaw_seo_redirect_' . md5( $request_uri );
        $redirect    = wp_cache_get( $cache_key, 'klaw_seo' );

        if ( $redirect === false ) {
            global $wpdb;
            $table = $wpdb->prefix . self::TABLE;

            // Try exact match first, then without trailing slash.
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $redirect = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE source_url = %s OR source_url = %s LIMIT 1",
                    $request_uri,
                    untrailingslashit( $request_uri )
                )
            );

            // Cache the result (including null to avoid repeated DB queries).
            wp_cache_set( $cache_key, $redirect ?: 'none', 'klaw_seo', 300 );
        }

        if ( ! $redirect || $redirect === 'none' ) {
            return;
        }

        // Increment hit counter asynchronously.
        $this->increment_hits( $redirect->id );

        $type = (int) $redirect->redirect_type;

        // 410 Gone — no redirect.
        if ( $type === 410 ) {
            status_header( 410 );
            nocache_headers();
            echo '<!DOCTYPE html><html><head><title>410 Gone</title></head><body><h1>410 Gone</h1><p>This resource is no longer available.</p></body></html>';
            exit;
        }

        // Build absolute target URL.
        $target = $redirect->target_url;
        if ( $target && strpos( $target, 'http' ) !== 0 ) {
            $target = home_url( $target );
        }

        if ( $target ) {
            wp_redirect( esc_url( $target ), $type );
            exit;
        }
    }

    /**
     * Increment the hit counter for a redirect.
     *
     * @param int $id Redirect row ID.
     */
    private function increment_hits( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query(
            $wpdb->prepare( "UPDATE {$table} SET hits = hits + 1 WHERE id = %d", $id )
        );
    }

    /**
     * Handle save (add/edit) redirect form submission.
     */
    public function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'klaw-seo' ) );
        }

        check_admin_referer( 'klaw_seo_redirect_save', 'klaw_seo_redirect_nonce' );

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $id     = absint( $_POST['redirect_id'] ?? 0 );
        $source = sanitize_text_field( $_POST['redirect_source'] ?? '' );
        $target = sanitize_text_field( $_POST['redirect_target'] ?? '' );
        $type   = absint( $_POST['redirect_type'] ?? 301 );

        // Normalize target: if it looks like an external domain (has a dot,
        // doesn't start with / or http), prepend https://.
        if ( $target && strpos( $target, '/' ) !== 0 && strpos( $target, 'http' ) !== 0 && strpos( $target, '.' ) !== false ) {
            $target = 'https://' . $target;
        }
        $target = esc_url_raw( $target );

        if ( ! $source ) {
            wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&error=missing_source' ) );
            exit;
        }

        // If user entered a full URL, extract just the path.
        if ( preg_match( '#^https?://#i', $source ) ) {
            $parsed_path = wp_parse_url( $source, PHP_URL_PATH );
            $source      = $parsed_path ?: '/';
        }

        // Normalize source to start with /.
        if ( strpos( $source, '/' ) !== 0 ) {
            $source = '/' . $source;
        }

        $data = [
            'source_url'    => $source,
            'target_url'    => $target,
            'redirect_type' => $type,
        ];

        $formats = [ '%s', '%s', '%d' ];

        if ( $id ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $table, $data, [ 'id' => $id ], $formats, [ '%d' ] );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert( $table, $data, $formats );
        }

        // Clear cache.
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'klaw_seo' );
        } else {
            wp_cache_flush();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&updated=1' ) );
        exit;
    }

    /**
     * Handle delete redirect.
     */
    public function handle_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'klaw-seo' ) );
        }

        $id = absint( $_GET['redirect_id'] ?? 0 );
        check_admin_referer( 'klaw_seo_delete_redirect_' . $id );

        if ( $id ) {
            global $wpdb;
            $table = $wpdb->prefix . self::TABLE;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
        }

        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'klaw_seo' );
        } else {
            wp_cache_flush();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&deleted=1' ) );
        exit;
    }

    /**
     * Handle CSV import.
     */
    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'klaw-seo' ) );
        }

        check_admin_referer( 'klaw_seo_import_redirects', 'klaw_seo_import_nonce' );

        if ( empty( $_FILES['klaw_seo_csv']['tmp_name'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&error=no_file' ) );
            exit;
        }

        $file = $_FILES['klaw_seo_csv']['tmp_name'];

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&error=read_fail' ) );
            exit;
        }

        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE;
        $count   = 0;
        $is_first = true;

        // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            // Skip header row if present.
            if ( $is_first ) {
                $is_first = false;
                if ( isset( $row[0] ) && strtolower( trim( $row[0] ) ) === 'source' ) {
                    continue;
                }
            }

            if ( count( $row ) < 2 ) {
                continue;
            }

            $source = sanitize_text_field( $row[0] );
            $target = sanitize_text_field( $row[1] );
            $type   = isset( $row[2] ) ? absint( $row[2] ) : 301;

            // Normalize target: external domain without protocol gets https://.
            if ( $target && strpos( $target, '/' ) !== 0 && strpos( $target, 'http' ) !== 0 && strpos( $target, '.' ) !== false ) {
                $target = 'https://' . $target;
            }
            $target = esc_url_raw( $target );

            if ( ! $source ) {
                continue;
            }

            // If full URL provided, extract just the path.
            if ( preg_match( '#^https?://#i', $source ) ) {
                $parsed_path = wp_parse_url( $source, PHP_URL_PATH );
                $source      = $parsed_path ?: '/';
            }

            if ( strpos( $source, '/' ) !== 0 ) {
                $source = '/' . $source;
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->insert(
                $table,
                [
                    'source_url'    => $source,
                    'target_url'    => $target,
                    'redirect_type' => $type,
                ],
                [ '%s', '%s', '%d' ]
            );
            $count++;
        }

        fclose( $handle );
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( 'klaw_seo' );
        } else {
            wp_cache_flush();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=klaw-seo-redirects&imported=' . $count ) );
        exit;
    }

    /**
     * Handle CSV export.
     */
    public function handle_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'klaw-seo' ) );
        }

        check_admin_referer( 'klaw_seo_export_redirects' );

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( "SELECT source_url, target_url, redirect_type, hits FROM {$table} ORDER BY id ASC", ARRAY_A );

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="klaw-seo-redirects-' . gmdate( 'Y-m-d' ) . '.csv"' );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'source', 'target', 'type', 'hits' ] );

        if ( $rows ) {
            foreach ( $rows as $row ) {
                fputcsv( $output, $row );
            }
        }

        fclose( $output );
        exit;
    }
}
