<?php
/**
 * Klaw SEO — General Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sep       = $options['title_separator'] ?? '|';
$tpl_post  = $options['title_template_post'] ?? '{post_title} {sep} {site_title}';
$tpl_page  = $options['title_template_page'] ?? '{post_title} {sep} {site_title}';
$tpl_arch  = $options['title_template_archive'] ?? '{archive_title} {sep} {site_title}';
$tpl_home  = $options['title_template_home'] ?? '{site_title} {sep} {tagline}';
$desc_src  = $options['description_source'] ?? 'excerpt_first';
$default_desc = $options['default_meta_description'] ?? '';
$opt_name  = Klaw_SEO_Settings::OPTION;
?>

<h2><?php esc_html_e( 'Title & Description Settings', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-title-separator"><?php esc_html_e( 'Title Separator', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <select id="klaw-title-separator" name="<?php echo esc_attr( $opt_name ); ?>[title_separator]">
                <?php
                $separators = [ '|', '-', '~', '>', '*' ];
                foreach ( $separators as $s ) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr( $s ),
                        selected( $sep, $s, false ),
                        esc_html( $s )
                    );
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e( 'Character displayed between the page title and site name.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tpl-post"><?php esc_html_e( 'Post Title Template', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tpl-post" name="<?php echo esc_attr( $opt_name ); ?>[title_template_post]"
                   value="<?php echo esc_attr( $tpl_post ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Tokens: {post_title}, {site_title}, {sep}, {tagline}', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tpl-page"><?php esc_html_e( 'Page Title Template', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tpl-page" name="<?php echo esc_attr( $opt_name ); ?>[title_template_page]"
                   value="<?php echo esc_attr( $tpl_page ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Tokens: {post_title}, {site_title}, {sep}, {tagline}', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tpl-archive"><?php esc_html_e( 'Archive Title Template', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tpl-archive" name="<?php echo esc_attr( $opt_name ); ?>[title_template_archive]"
                   value="<?php echo esc_attr( $tpl_arch ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Tokens: {archive_title}, {site_title}, {sep}, {tagline}', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-tpl-home"><?php esc_html_e( 'Homepage Title Template', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="text" id="klaw-tpl-home" name="<?php echo esc_attr( $opt_name ); ?>[title_template_home]"
                   value="<?php echo esc_attr( $tpl_home ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Tokens: {site_title}, {sep}, {tagline}', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php esc_html_e( 'Description Source', 'klaw-seo' ); ?></th>
        <td>
            <fieldset>
                <label>
                    <input type="radio" name="<?php echo esc_attr( $opt_name ); ?>[description_source]"
                           value="excerpt_first" <?php checked( $desc_src, 'excerpt_first' ); ?> />
                    <?php esc_html_e( 'Use excerpt first, then fall back to content', 'klaw-seo' ); ?>
                </label>
                <br />
                <label>
                    <input type="radio" name="<?php echo esc_attr( $opt_name ); ?>[description_source]"
                           value="content_first" <?php checked( $desc_src, 'content_first' ); ?> />
                    <?php esc_html_e( 'Always use content (ignore excerpt)', 'klaw-seo' ); ?>
                </label>
            </fieldset>
            <p class="description"><?php esc_html_e( 'Controls auto-generated meta descriptions when none is set manually.', 'klaw-seo' ); ?></p>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-default-desc"><?php esc_html_e( 'Default Meta Description', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-default-desc" name="<?php echo esc_attr( $opt_name ); ?>[default_meta_description]"
                      rows="3" class="large-text"
                      placeholder="<?php esc_attr_e( 'e.g. Austin\'s neighborhood coffeehouse and brunch spot serving locals since 2009.', 'klaw-seo' ); ?>"><?php echo esc_textarea( $default_desc ); ?></textarea>
            <p class="description">
                <?php esc_html_e( 'Used as the fallback meta description for any page (including the homepage) that does not have its own description. Aim for 130–160 characters. Overrides the WordPress tagline.', 'klaw-seo' ); ?>
            </p>
        </td>
    </tr>
</table>
