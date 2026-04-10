<?php
/**
 * Klaw SEO — Schema & Health Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<h2><?php esc_html_e( 'Schema & Health', 'klaw-seo' ); ?></h2>
<p><?php esc_html_e( 'Control which structured data schemas Klaw SEO outputs. All toggles default to enabled.', 'klaw-seo' ); ?></p>

<h3><?php esc_html_e( 'Structured Data', 'klaw-seo' ); ?></h3>
<table class="form-table" role="presentation">
    <?php
    Klaw_SEO_Settings::render_toggle(
        'schema_website_enabled',
        __( 'WebSite + SearchAction', 'klaw-seo' ),
        __( 'Outputs WebSite schema with SearchAction on the homepage. Helps Google show a sitelinks searchbox in brand search results.', 'klaw-seo' ),
        $options
    );

    Klaw_SEO_Settings::render_toggle(
        'schema_blog_posting_enabled',
        __( 'BlogPosting (blog posts)', 'klaw-seo' ),
        __( 'Outputs BlogPosting schema on single posts with author, publisher, and publish/modified dates. Publisher logo comes from your WordPress Site Icon.', 'klaw-seo' ),
        $options
    );
    ?>
</table>

<h3><?php esc_html_e( 'ItemList on Archive Pages', 'klaw-seo' ); ?></h3>
<p class="description"><?php esc_html_e( 'Outputs ItemList schema on post type archive pages to help Google display rich carousel results. Events automatically include startDate, URL, and location.', 'klaw-seo' ); ?></p>
<table class="form-table" role="presentation">
    <?php
    $public_types = get_post_types( [ 'public' => true ], 'objects' );
    unset( $public_types['attachment'] );

    foreach ( $public_types as $pt ) {
        // Skip post types that have no archive.
        if ( ! $pt->has_archive && $pt->name !== 'post' ) {
            continue;
        }

        $key     = 'schema_item_list_' . $pt->name;
        // ItemList defaults to OFF — it's a heavy addition and should be opt-in per type.
        $checked = ! empty( $options[ $key ] );
        ?>
        <tr>
            <th scope="row"><?php echo esc_html( $pt->labels->name ); ?></th>
            <td>
                <label>
                    <input type="checkbox"
                           name="klaw_seo_settings[<?php echo esc_attr( $key ); ?>]"
                           value="1"
                           <?php checked( $checked ); ?> />
                    <?php printf( esc_html__( 'Enable ItemList on %s archive', 'klaw-seo' ), esc_html( strtolower( $pt->labels->name ) ) ); ?>
                </label>
            </td>
        </tr>
        <?php
    }
    ?>
</table>
