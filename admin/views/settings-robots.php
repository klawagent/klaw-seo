<?php
/**
 * Klaw SEO — Robots.txt Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name = Klaw_SEO_Settings::OPTION;
$content  = $options['robots_content'] ?? '';

if ( empty( $content ) ) {
    $sitemap_url = home_url( '/sitemap.xml' );
    $content     = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: {$sitemap_url}\n";
}
?>

<h2><?php esc_html_e( 'Robots.txt', 'klaw-seo' ); ?></h2>

<p class="description">
    <?php esc_html_e( 'This content will be served as your virtual robots.txt file. WordPress generates robots.txt dynamically — this overrides the default output.', 'klaw-seo' ); ?>
</p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="klaw-robots-content"><?php esc_html_e( 'Robots.txt Content', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <textarea id="klaw-robots-content" name="<?php echo esc_attr( $opt_name ); ?>[robots_content]"
                      rows="12" class="large-text code"><?php echo esc_textarea( $content ); ?></textarea>
        </td>
    </tr>
</table>

<p>
    <button type="button" class="button" id="klaw-robots-reset">
        <?php esc_html_e( 'Reset to Default', 'klaw-seo' ); ?>
    </button>
    <a href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" class="button" style="margin-left:5px;">
        <?php esc_html_e( 'View robots.txt', 'klaw-seo' ); ?>
    </a>
</p>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var resetBtn = document.getElementById('klaw-robots-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            var defaultContent = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: <?php echo esc_js( home_url( '/sitemap.xml' ) ); ?>\n";
            document.getElementById('klaw-robots-content').value = defaultContent;
        });
    }
});
</script>
