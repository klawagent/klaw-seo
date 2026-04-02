<?php
/**
 * Klaw SEO — Tracking Scripts Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name = Klaw_SEO_Settings::OPTION;
?>

<!-- Analytics & Tracking -->
<h2><?php esc_html_e( 'Analytics & Tracking', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-tracking-ga4"><?php esc_html_e( 'Google Analytics (GA4)', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-ga4" name="<?php echo esc_attr( $opt_name ); ?>[tracking_ga4_id]"
                   value="<?php echo esc_attr( $options['tracking_ga4_id'] ?? '' ); ?>" class="regular-text" placeholder="G-XXXXXXXXXX" />
            <p class="description"><?php esc_html_e( 'Enter your GA4 Measurement ID (e.g. G-XXXXXXXXXX)', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-search-console"><?php esc_html_e( 'Google Search Console', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-search-console" name="<?php echo esc_attr( $opt_name ); ?>[tracking_search_console]"
                   value="<?php echo esc_attr( $options['tracking_search_console'] ?? '' ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Enter the content value from the Google verification meta tag', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-clarity"><?php esc_html_e( 'Microsoft Clarity', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-clarity" name="<?php echo esc_attr( $opt_name ); ?>[tracking_clarity_id]"
                   value="<?php echo esc_attr( $options['tracking_clarity_id'] ?? '' ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Enter your Clarity Project ID', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-meta-pixel"><?php esc_html_e( 'Meta Pixel', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-meta-pixel" name="<?php echo esc_attr( $opt_name ); ?>[tracking_meta_pixel_id]"
                   value="<?php echo esc_attr( $options['tracking_meta_pixel_id'] ?? '' ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Enter your Meta (Facebook) Pixel ID', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>

<!-- Marketing -->
<h2><?php esc_html_e( 'Marketing', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-tracking-gtm"><?php esc_html_e( 'Google Tag Manager', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-gtm" name="<?php echo esc_attr( $opt_name ); ?>[tracking_gtm_id]"
                   value="<?php echo esc_attr( $options['tracking_gtm_id'] ?? '' ); ?>" class="regular-text" placeholder="GTM-XXXXXXX" />
            <p class="description"><?php esc_html_e( 'Enter your GTM Container ID (e.g. GTM-XXXXXXX)', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-tiktok"><?php esc_html_e( 'TikTok Pixel', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-tiktok" name="<?php echo esc_attr( $opt_name ); ?>[tracking_tiktok_pixel_id]"
                   value="<?php echo esc_attr( $options['tracking_tiktok_pixel_id'] ?? '' ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Enter your TikTok Pixel ID', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-pinterest"><?php esc_html_e( 'Pinterest Tag', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tracking-pinterest" name="<?php echo esc_attr( $opt_name ); ?>[tracking_pinterest_tag_id]"
                   value="<?php echo esc_attr( $options['tracking_pinterest_tag_id'] ?? '' ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Enter your Pinterest Tag ID', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>

<!-- Engagement -->
<h2><?php esc_html_e( 'Engagement', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-tracking-live-chat"><?php esc_html_e( 'Live Chat', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-tracking-live-chat" name="<?php echo esc_attr( $opt_name ); ?>[tracking_live_chat]"
                      rows="5" class="large-text"><?php echo esc_textarea( $options['tracking_live_chat'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Paste your live chat embed code (Tidio, Crisp, Intercom, etc.)', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-cookie-consent"><?php esc_html_e( 'Cookie Consent', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-tracking-cookie-consent" name="<?php echo esc_attr( $opt_name ); ?>[tracking_cookie_consent]"
                      rows="5" class="large-text"><?php echo esc_textarea( $options['tracking_cookie_consent'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Paste your cookie consent banner code (CookieYes, Termly, etc.)', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>

<!-- Custom Scripts -->
<h2><?php esc_html_e( 'Custom Scripts', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-tracking-head-scripts"><?php esc_html_e( 'Head Scripts', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-tracking-head-scripts" name="<?php echo esc_attr( $opt_name ); ?>[tracking_head_scripts]"
                      rows="6" class="large-text code"><?php echo esc_textarea( $options['tracking_head_scripts'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Code added before </head>. Use for custom tracking or verification tags.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-body-scripts"><?php esc_html_e( 'Body Scripts', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-tracking-body-scripts" name="<?php echo esc_attr( $opt_name ); ?>[tracking_body_scripts]"
                      rows="6" class="large-text code"><?php echo esc_textarea( $options['tracking_body_scripts'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Code added after <body>. Use for GTM noscript fallbacks or similar.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tracking-footer-scripts"><?php esc_html_e( 'Footer Scripts', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-tracking-footer-scripts" name="<?php echo esc_attr( $opt_name ); ?>[tracking_footer_scripts]"
                      rows="6" class="large-text code"><?php echo esc_textarea( $options['tracking_footer_scripts'] ?? '' ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Code added before </body>. Use for chat widgets or deferred scripts.', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>
