<?php
/**
 * Klaw SEO — Settings Page
 *
 * Top-level "Klaw SEO" admin menu with submenus for every feature area.
 * All settings stored in a single option: klaw_seo_settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Settings {

    /**
     * Option name used across the plugin.
     */
    const OPTION = 'klaw_seo_settings';

    /**
     * Capability required for accessing the settings.
     */
    const CAPABILITY = 'manage_options';

    /**
     * Settings page slug prefix.
     */
    const SLUG = 'klaw-seo';

    /**
     * Submenus definition.
     *
     * @var array
     */
    private $submenus = [];

    /**
     * Constructor — register hooks.
     */
    public function __construct() {
        $this->submenus = [
            'general'        => __( 'General', 'klaw-seo' ),
            'social'         => __( 'Social', 'klaw-seo' ),
            'local-business' => __( 'Local Business', 'klaw-seo' ),
            'schema-health'  => __( 'Schema & Health', 'klaw-seo' ),
            'sitemaps'       => __( 'Sitemaps', 'klaw-seo' ),
            'redirects'      => __( 'Redirects', 'klaw-seo' ),
            'robots'         => __( 'Robots.txt', 'klaw-seo' ),
            'alt-text'       => __( 'Alt Text', 'klaw-seo' ),
            'broken-links'   => __( 'Broken Links', 'klaw-seo' ),
            'tracking'       => __( 'Tracking', 'klaw-seo' ),
        ];

        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register top-level menu and all submenus.
     */
    public function register_menus() {
        // Top-level menu.
        add_menu_page(
            __( 'Klaw SEO', 'klaw-seo' ),
            __( 'Klaw SEO', 'klaw-seo' ),
            self::CAPABILITY,
            self::SLUG,
            [ $this, 'render_page' ],
            'dashicons-search',
            80
        );

        // First submenu replaces the default duplicate.
        $first = true;
        foreach ( $this->submenus as $slug => $label ) {
            add_submenu_page(
                self::SLUG,
                $label . ' — Klaw SEO',
                $label,
                self::CAPABILITY,
                $first ? self::SLUG : self::SLUG . '-' . $slug,
                [ $this, 'render_page' ]
            );
            $first = false;
        }
    }

    /**
     * Register the single settings option with WordPress Settings API.
     */
    public function register_settings() {
        register_setting( 'klaw_seo_settings_group', self::OPTION, [
            'type'              => 'array',
            'sanitize_callback' => [ $this, 'sanitize' ],
            'default'           => [],
        ] );
    }

    /**
     * Sanitize all settings on save.
     *
     * @param  array $input Raw form input.
     * @return array        Sanitized settings merged with existing.
     */
    public function sanitize( $input ) {
        $existing = get_option( self::OPTION, [] );

        if ( ! is_array( $input ) ) {
            return $existing;
        }

        // Text fields.
        $text_fields = [
            'title_separator',
            'title_template_post',
            'title_template_page',
            'title_template_archive',
            'title_template_home',
            'description_source',
            'business_name',
            'business_type',
            'business_street',
            'business_city',
            'business_state',
            'business_zip',
            'business_country',
            'business_phone',
            'business_email',
            'business_lat',
            'business_lng',
            'business_price_range',
            'business_hours',
            'event_post_type',
            'event_date_field',
            'event_time_field',
            'event_venue_field',
            'event_description_field',
            'alt_text_ai_provider',
            'alt_text_ai_key_claude',
            'alt_text_ai_key_openai',
            'alt_text_cron_frequency',
            'broken_links_frequency',
            'broken_links_email',
            'robots_content',
            'tracking_ga4_id',
            'tracking_search_console',
            'tracking_clarity_id',
            'tracking_meta_pixel_id',
            'tracking_gtm_id',
            'tracking_tiktok_pixel_id',
            'tracking_pinterest_tag_id',
        ];

        foreach ( $text_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                if ( in_array( $key, [ 'business_hours', 'robots_content' ], true ) ) {
                    $existing[ $key ] = sanitize_textarea_field( $input[ $key ] );
                } elseif ( in_array( $key, [ 'business_email', 'broken_links_email' ], true ) ) {
                    $existing[ $key ] = sanitize_email( $input[ $key ] );
                } elseif ( in_array( $key, [ 'business_lat', 'business_lng' ], true ) ) {
                    $existing[ $key ] = floatval( $input[ $key ] );
                } else {
                    $existing[ $key ] = sanitize_text_field( $input[ $key ] );
                }
            }
        }

        // URL fields.
        $url_fields = [
            'social_facebook',
            'social_instagram',
            'social_twitter',
            'social_linkedin',
            'social_pinterest',
            'social_youtube',
            'default_og_image',
            'business_gbp_url',
        ];

        foreach ( $url_fields as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $existing[ $key ] = esc_url_raw( $input[ $key ] );
            }
        }

        // Checkboxes — must be explicitly set per page to avoid wiping out other pages.
        $checkbox_fields = [
            'sitemap_enabled',
            'sitemap_ping',
            'alt_text_default_enabled',
            'alt_text_ai_enabled',
            'broken_links_enabled',
        ];

        // Determine which page is being saved.
        $page = sanitize_text_field( $input['_klaw_settings_page'] ?? '' );

        if ( $page === 'sitemaps' ) {
            $existing['sitemap_enabled'] = ! empty( $input['sitemap_enabled'] ) ? '1' : '';
            $existing['sitemap_ping']    = ! empty( $input['sitemap_ping'] ) ? '1' : '';

            // Post type checkboxes.
            $public_types = get_post_types( [ 'public' => true ], 'names' );
            unset( $public_types['attachment'] );
            foreach ( $public_types as $pt ) {
                $key = 'sitemap_post_type_' . $pt;
                $existing[ $key ] = ! empty( $input[ $key ] ) ? '1' : '';
            }
        } elseif ( $page === 'alt-text' ) {
            $existing['alt_text_default_enabled'] = ! empty( $input['alt_text_default_enabled'] ) ? '1' : '';
            $existing['alt_text_ai_enabled']      = ! empty( $input['alt_text_ai_enabled'] ) ? '1' : '';
        } elseif ( $page === 'schema-health' ) {
            $existing['schema_website_enabled']      = ! empty( $input['schema_website_enabled'] ) ? '1' : '';
            $existing['schema_blog_posting_enabled'] = ! empty( $input['schema_blog_posting_enabled'] ) ? '1' : '';

            // Per-post-type ItemList toggles.
            $public_types = get_post_types( [ 'public' => true ], 'names' );
            unset( $public_types['attachment'] );
            foreach ( $public_types as $pt ) {
                $key = 'schema_item_list_' . $pt;
                $existing[ $key ] = ! empty( $input[ $key ] ) ? '1' : '';
            }
        } elseif ( $page === 'broken-links' ) {
            $existing['broken_links_enabled'] = ! empty( $input['broken_links_enabled'] ) ? '1' : '';
        } elseif ( $page === 'tracking' ) {
            // Textarea fields — contain raw HTML/JS, only wp_unslash.
            $tracking_textarea_fields = [
                'tracking_live_chat',
                'tracking_cookie_consent',
                'tracking_head_scripts',
                'tracking_body_scripts',
                'tracking_footer_scripts',
            ];
            foreach ( $tracking_textarea_fields as $key ) {
                if ( isset( $input[ $key ] ) ) {
                    $existing[ $key ] = wp_unslash( $input[ $key ] );
                }
            }
        }

        return $existing;
    }

    /**
     * Render the current settings page by loading the appropriate view file.
     */
    public function render_page() {
        if ( ! current_user_can( self::CAPABILITY ) ) {
            return;
        }

        $screen  = get_current_screen();
        $tab     = $this->get_current_tab( $screen );
        $options = get_option( self::OPTION, [] );
        ?>
        <div class="wrap klaw-seo-settings">
            <h1><?php esc_html_e( 'Klaw SEO', 'klaw-seo' ); ?></h1>

            <nav class="nav-tab-wrapper klaw-seo-nav">
                <?php foreach ( $this->submenus as $slug => $label ) :
                    $url    = admin_url( 'admin.php?page=' . ( $slug === 'general' ? self::SLUG : self::SLUG . '-' . $slug ) );
                    $active = ( $tab === $slug ) ? ' nav-tab-active' : '';
                    ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo esc_attr( $active ); ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" class="klaw-seo-settings-form">
                <?php
                settings_fields( 'klaw_seo_settings_group' );
                echo '<input type="hidden" name="' . esc_attr( self::OPTION ) . '[_klaw_settings_page]" value="' . esc_attr( $tab ) . '" />';

                $view = KLAW_SEO_DIR . 'admin/views/settings-' . $tab . '.php';
                if ( file_exists( $view ) ) {
                    include $view;
                }

                if ( ! in_array( $tab, [ 'redirects', 'broken-links' ], true ) ) {
                    submit_button();
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Determine which tab is active based on the screen ID / page parameter.
     *
     * @param  WP_Screen $screen Current admin screen.
     * @return string            Tab slug.
     */
    private function get_current_tab( $screen ) {
        if ( ! $screen ) {
            return 'general';
        }

        $page = sanitize_text_field( $_GET['page'] ?? self::SLUG );

        foreach ( $this->submenus as $slug => $label ) {
            $menu_slug = ( $slug === 'general' ) ? self::SLUG : self::SLUG . '-' . $slug;
            if ( $page === $menu_slug ) {
                return $slug;
            }
        }

        return 'general';
    }

    /**
     * Render a standard settings checkbox toggle row.
     *
     * Settings that haven't been saved yet default to ON — so installing the
     * plugin and visiting a new feature's tab does not need explicit opt-in.
     *
     * @param string $key         Setting key within klaw_seo_settings.
     * @param string $label       Human-readable row label.
     * @param string $description Optional description shown under the label.
     * @param array  $options     Current options array.
     */
    public static function render_toggle( $key, $label, $description, $options ) {
        // Default to ON if key hasn't been stored yet.
        $checked = array_key_exists( $key, $options ) ? ! empty( $options[ $key ] ) : true;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html( $label ); ?></th>
            <td>
                <label>
                    <input type="checkbox"
                           name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"
                           value="1"
                           <?php checked( $checked ); ?> />
                    <?php esc_html_e( 'Enable', 'klaw-seo' ); ?>
                </label>
                <?php if ( $description ) : ?>
                    <p class="description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
