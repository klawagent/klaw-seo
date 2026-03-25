<?php
/**
 * Klaw SEO — Broken Links Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name  = Klaw_SEO_Settings::OPTION;
$enabled   = ! empty( $options['broken_links_enabled'] );
$frequency = $options['broken_links_frequency'] ?? 'weekly';
$email     = $options['broken_links_email'] ?? get_option( 'admin_email' );

global $wpdb;
$table = $wpdb->prefix . 'klaw_seo_broken_links';

// Check if table exists before querying.
$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

$broken_links = [];
if ( $table_exists ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
    $broken_links = $wpdb->get_results(
        "SELECT * FROM {$table} ORDER BY last_checked DESC LIMIT 200"
    );
}

$next_run = wp_next_scheduled( 'klaw_seo_check_links' );
?>

<h2><?php esc_html_e( 'Broken Link Checker', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Enable Scanner', 'klaw-seo' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[broken_links_enabled]"
                       value="1" <?php checked( $enabled ); ?> />
                <?php esc_html_e( 'Automatically scan for broken links on a schedule', 'klaw-seo' ); ?>
            </label>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-bl-frequency"><?php esc_html_e( 'Scan Frequency', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <select id="klaw-bl-frequency" name="<?php echo esc_attr( $opt_name ); ?>[broken_links_frequency]">
                <option value="daily" <?php selected( $frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'klaw-seo' ); ?></option>
                <option value="twicedaily" <?php selected( $frequency, 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'klaw-seo' ); ?></option>
                <option value="weekly" <?php selected( $frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'klaw-seo' ); ?></option>
            </select>
        </td>
    </tr>

    <tr>
        <th scope="row">
            <label for="klaw-bl-email"><?php esc_html_e( 'Notification Email', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="email" id="klaw-bl-email" name="<?php echo esc_attr( $opt_name ); ?>[broken_links_email]"
                   value="<?php echo esc_attr( $email ); ?>" class="regular-text" />
            <p class="description"><?php esc_html_e( 'Receive an email report when broken links are found.', 'klaw-seo' ); ?></p>
        </td>
    </tr>
</table>

<?php if ( $next_run ) : ?>
    <p>
        <strong><?php esc_html_e( 'Next scheduled scan:', 'klaw-seo' ); ?></strong>
        <?php echo esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_run ), 'F j, Y g:i A' ) ); ?>
    </p>
<?php endif; ?>

<?php if ( ! empty( $broken_links ) ) : ?>
    <h3><?php esc_html_e( 'Results', 'klaw-seo' ); ?></h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Post', 'klaw-seo' ); ?></th>
                <th><?php esc_html_e( 'Broken URL', 'klaw-seo' ); ?></th>
                <th><?php esc_html_e( 'Status Code', 'klaw-seo' ); ?></th>
                <th><?php esc_html_e( 'Last Checked', 'klaw-seo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'klaw-seo' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $broken_links as $link ) :
                $post_title = get_the_title( $link->post_id );
                $edit_url   = get_edit_post_link( $link->post_id );
                $dismiss_url = wp_nonce_url(
                    admin_url( 'admin-post.php?action=klaw_seo_dismiss_broken_link&link_id=' . $link->id ),
                    'klaw_seo_dismiss_' . $link->id
                );
                ?>
                <tr>
                    <td>
                        <?php if ( $edit_url ) : ?>
                            <a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $post_title ?: '#' . $link->post_id ); ?></a>
                        <?php else : ?>
                            <?php echo esc_html( $post_title ?: '#' . $link->post_id ); ?>
                        <?php endif; ?>
                    </td>
                    <td><code style="word-break:break-all;"><?php echo esc_html( $link->url ); ?></code></td>
                    <td>
                        <span class="klaw-seo-status-<?php echo esc_attr( $link->status_code ); ?>">
                            <?php echo esc_html( $link->status_code ); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html( $link->last_checked ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'klaw-seo' ); ?></a>
                        <?php if ( $edit_url ) : ?>
                            | <a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit Post', 'klaw-seo' ); ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php elseif ( $table_exists ) : ?>
    <p><?php esc_html_e( 'No broken links found. Looking good!', 'klaw-seo' ); ?></p>
<?php endif; ?>
