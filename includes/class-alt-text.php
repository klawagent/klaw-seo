<?php
/**
 * Klaw SEO — Alt Text Automation
 *
 * Auto-fills alt text on upload using post title or cleaned filename.
 * Optional AI-generated alt text via Claude or OpenAI vision APIs.
 * AJAX endpoint for bulk scanning missing alt text.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Alt_Text {

    /**
     * Cron hook name for scheduled scans.
     */
    const CRON_HOOK = 'klaw_seo_alt_text_scan';

    /**
     * Constructor — register hooks.
     */
    public function __construct() {
        add_action( 'add_attachment', [ $this, 'auto_fill_alt' ] );
        add_action( 'wp_ajax_klaw_seo_bulk_alt_scan', [ $this, 'ajax_bulk_scan' ] );
        add_action( 'wp_ajax_klaw_seo_bulk_alt_update', [ $this, 'ajax_bulk_update' ] );
        add_action( 'init', [ $this, 'schedule_scan' ] );
        add_action( self::CRON_HOOK, [ $this, 'run_scheduled_scan' ] );
    }

    /**
     * Schedule or unschedule the recurring scan based on settings.
     */
    public function schedule_scan() {
        $settings  = get_option( 'klaw_seo_settings', [] );
        $frequency = $settings['alt_text_cron_frequency'] ?? 'off';
        $scheduled = wp_next_scheduled( self::CRON_HOOK );

        if ( $frequency === 'off' ) {
            if ( $scheduled ) {
                wp_unschedule_event( $scheduled, self::CRON_HOOK );
            }
            return;
        }

        $recurrence = ( $frequency === 'weekly' ) ? 'weekly' : 'daily';

        if ( ! $scheduled ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, self::CRON_HOOK );
        }
    }

    /**
     * Run the scheduled scan — fills alt text on up to 200 images per run.
     */
    public function run_scheduled_scan() {
        $settings = get_option( 'klaw_seo_settings', [] );

        // Respect the master default-autofill toggle.
        if ( empty( $settings['alt_text_default_enabled'] ) ) {
            return;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm
                ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
               AND p.post_mime_type LIKE 'image/%'
               AND (pm.meta_value IS NULL OR pm.meta_value = '')
             ORDER BY p.ID DESC
             LIMIT 200"
        );

        $updated = 0;
        foreach ( $results as $row ) {
            $alt = $this->generate_default_alt( $row->ID );
            if ( $alt ) {
                update_post_meta( $row->ID, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
                $updated++;
            }
        }

        update_option( 'klaw_seo_alt_text_last_scan', [
            'time'    => time(),
            'updated' => $updated,
            'scanned' => count( $results ),
        ], false );
    }

    /**
     * Auto-fill alt text on image upload.
     *
     * Priority: parent post title > cleaned filename.
     * If AI is enabled, attempt AI-generated alt text instead.
     *
     * @param int $attachment_id Attachment post ID.
     */
    public function auto_fill_alt( $attachment_id ) {
        $settings = get_option( 'klaw_seo_settings', [] );

        // Only process images.
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }

        // Check if already has alt text.
        $existing = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
        if ( $existing ) {
            return;
        }

        // Try AI first if enabled.
        if ( ! empty( $settings['alt_text_ai_enabled'] ) ) {
            $provider = $settings['alt_text_ai_provider'] ?? 'none';
            if ( $provider !== 'none' ) {
                $ai_alt = $this->generate_ai_alt( $attachment_id, $provider, $settings );
                if ( $ai_alt ) {
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $ai_alt ) );
                    return;
                }
            }
        }

        // Fall back to default auto-fill.
        if ( empty( $settings['alt_text_default_enabled'] ) ) {
            return;
        }

        $alt = $this->generate_default_alt( $attachment_id );
        if ( $alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }
    }

    /**
     * Generate a default alt text string.
     *
     * Priority: parent post title > cleaned filename.
     *
     * @param  int    $attachment_id Attachment ID.
     * @return string                Alt text.
     */
    private function generate_default_alt( $attachment_id ) {
        // Try parent post title.
        $attachment = get_post( $attachment_id );
        if ( $attachment && $attachment->post_parent ) {
            $parent_title = get_the_title( $attachment->post_parent );
            if ( $parent_title ) {
                return $parent_title;
            }
        }

        // Fall back to cleaned filename.
        $file = get_attached_file( $attachment_id );
        if ( $file ) {
            $name = pathinfo( $file, PATHINFO_FILENAME );
            // Remove common prefixes/IDs and clean up.
            $name = preg_replace( '/^(IMG|DSC|Screenshot|Screen Shot)[-_]?/i', '', $name );
            $name = preg_replace( '/[-_]+/', ' ', $name );
            $name = preg_replace( '/\d{10,}/', '', $name ); // Remove timestamps.
            $name = trim( $name );
            $name = ucfirst( mb_strtolower( $name ) );

            if ( $name && strlen( $name ) > 2 ) {
                return $name;
            }
        }

        return '';
    }

    /**
     * Generate alt text using an AI vision API.
     *
     * @param  int    $attachment_id Attachment ID.
     * @param  string $provider      'claude' or 'openai'.
     * @param  array  $settings      Plugin settings.
     * @return string|false          AI-generated alt text or false on failure.
     */
    private function generate_ai_alt( $attachment_id, $provider, $settings ) {
        $image_url = wp_get_attachment_url( $attachment_id );
        if ( ! $image_url ) {
            return false;
        }

        // Get image data as base64 for API calls.
        $file_path = get_attached_file( $attachment_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return false;
        }

        // Skip files over 5MB to avoid memory issues with base64 encoding.
        $file_size = filesize( $file_path );
        if ( $file_size === false || $file_size > 5 * 1024 * 1024 ) {
            return false;
        }

        $filetype = wp_check_filetype( $file_path );
        $mime     = $filetype['type'];
        if ( ! $mime ) {
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $image_data = base64_encode( file_get_contents( $file_path ) );

        if ( $provider === 'claude' ) {
            return $this->ai_claude( $image_data, $mime, $settings );
        } elseif ( $provider === 'openai' ) {
            return $this->ai_openai( $image_data, $mime, $settings );
        }

        return false;
    }

    /**
     * Call Claude (Anthropic) API for image description.
     *
     * @param  string $image_b64 Base64-encoded image data.
     * @param  string $mime      MIME type.
     * @param  array  $settings  Plugin settings.
     * @return string|false
     */
    private function ai_claude( $image_b64, $mime, $settings ) {
        $api_key = $settings['alt_text_ai_key_claude'] ?? '';
        if ( ! $api_key ) {
            return false;
        }

        $body = [
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => 100,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mime,
                                'data'       => $image_b64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Generate a concise, descriptive alt text for this image in one sentence. Focus on what is shown, keeping it under 125 characters. Output only the alt text, nothing else.',
                        ],
                    ],
                ],
            ],
        ];

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'timeout' => 30,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['content'][0]['text'] ) ) {
            return trim( $data['content'][0]['text'] );
        }

        return false;
    }

    /**
     * Call OpenAI API for image description.
     *
     * @param  string $image_b64 Base64-encoded image data.
     * @param  string $mime      MIME type.
     * @param  array  $settings  Plugin settings.
     * @return string|false
     */
    private function ai_openai( $image_b64, $mime, $settings ) {
        $api_key = $settings['alt_text_ai_key_openai'] ?? '';
        if ( ! $api_key ) {
            return false;
        }

        $body = [
            'model'      => 'gpt-4o',
            'max_tokens' => 100,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mime . ';base64,' . $image_b64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Generate a concise, descriptive alt text for this image in one sentence. Focus on what is shown, keeping it under 125 characters. Output only the alt text, nothing else.',
                        ],
                    ],
                ],
            ],
        ];

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! empty( $data['choices'][0]['message']['content'] ) ) {
            return trim( $data['choices'][0]['message']['content'] );
        }

        return false;
    }

    /**
     * AJAX: Bulk scan for images missing alt text.
     */
    public function ajax_bulk_scan() {
        check_ajax_referer( 'klaw_seo_bulk_alt_scan' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized.' );
        }

        global $wpdb;

        // Find images without alt text.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            "SELECT p.ID, p.post_title, p.guid
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm
                ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
               AND p.post_mime_type LIKE 'image/%'
               AND (pm.meta_value IS NULL OR pm.meta_value = '')
             ORDER BY p.ID DESC
             LIMIT 200"
        );

        $images = [];
        foreach ( $results as $row ) {
            $images[] = [
                'id'       => $row->ID,
                'filename' => basename( $row->guid ),
                'edit_url' => admin_url( 'post.php?post=' . $row->ID . '&action=edit' ),
            ];
        }

        wp_send_json_success( [
            'total'  => count( $images ),
            'images' => $images,
        ] );
    }

    /**
     * AJAX: Bulk update images missing alt text using post title / cleaned filename.
     */
    public function ajax_bulk_update() {
        check_ajax_referer( 'klaw_seo_bulk_alt_update' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized.' );
        }

        global $wpdb;

        // Find images without alt text.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm
                ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
               AND p.post_mime_type LIKE 'image/%'
               AND (pm.meta_value IS NULL OR pm.meta_value = '')
             ORDER BY p.ID DESC
             LIMIT 200"
        );

        $updated = 0;
        $skipped = 0;

        foreach ( $results as $row ) {
            $alt = $this->generate_default_alt( $row->ID );
            if ( $alt ) {
                update_post_meta( $row->ID, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
                $updated++;
            } else {
                $skipped++;
            }
        }

        wp_send_json_success( [
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => count( $results ),
        ] );
    }
}
