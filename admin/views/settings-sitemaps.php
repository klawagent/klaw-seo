<?php
/**
 * Klaw SEO — Sitemaps Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name = Klaw_SEO_Settings::OPTION;
$enabled  = ! empty( $options['sitemap_enabled'] );
$ping     = ! empty( $options['sitemap_ping'] );

$public_types = get_post_types( [ 'public' => true ], 'objects' );
unset( $public_types['attachment'] );
?>

<h2><?php esc_html_e( 'XML Sitemap', 'klaw-seo' ); ?></h2>

<?php if ( $enabled ) : ?>
    <p>
        <?php esc_html_e( 'Your sitemap is available at:', 'klaw-seo' ); ?>
        <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank">
            <?php echo esc_html( home_url( '/sitemap.xml' ) ); ?>
        </a>
    </p>
<?php endif; ?>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Enable Sitemap', 'klaw-seo' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[sitemap_enabled]"
                       value="1" <?php checked( $enabled ); ?> />
                <?php esc_html_e( 'Generate XML sitemap', 'klaw-seo' ); ?>
            </label>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php esc_html_e( 'Include Post Types', 'klaw-seo' ); ?></th>
        <td>
            <fieldset>
                <?php foreach ( $public_types as $pt ) :
                    $key     = 'sitemap_post_type_' . $pt->name;
                    $checked = ! empty( $options[ $key ] );
                    // Default to checked for post and page.
                    if ( ! isset( $options[ $key ] ) && in_array( $pt->name, [ 'post', 'page' ], true ) ) {
                        $checked = true;
                    }
                    ?>
                    <label style="display:block;margin-bottom:4px;">
                        <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[<?php echo esc_attr( $key ); ?>]"
                               value="1" <?php checked( $checked ); ?> />
                        <?php echo esc_html( $pt->labels->name . ' (' . $pt->name . ')' ); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php esc_html_e( 'Search Engine Ping', 'klaw-seo' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[sitemap_ping]"
                       value="1" <?php checked( $ping ); ?> />
                <?php esc_html_e( 'Ping Google and Bing when a post is published', 'klaw-seo' ); ?>
            </label>
        </td>
    </tr>
</table>
