<?php
/**
 * Klaw SEO — Alt Text Settings View
 *
 * @var array $options Current plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$opt_name    = Klaw_SEO_Settings::OPTION;
$default_on  = ! empty( $options['alt_text_default_enabled'] );
$ai_on       = ! empty( $options['alt_text_ai_enabled'] );
$ai_provider = $options['alt_text_ai_provider'] ?? 'none';
$key_claude  = $options['alt_text_ai_key_claude'] ?? '';
$key_openai  = $options['alt_text_ai_key_openai'] ?? '';
?>

<h2><?php esc_html_e( 'Alt Text Automation', 'klaw-seo' ); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e( 'Auto-fill Alt Text', 'klaw-seo' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[alt_text_default_enabled]"
                       value="1" <?php checked( $default_on ); ?> />
                <?php esc_html_e( 'Automatically set alt text on upload using post title or cleaned filename', 'klaw-seo' ); ?>
            </label>
        </td>
    </tr>

    <tr>
        <th scope="row"><?php esc_html_e( 'AI-Generated Alt Text', 'klaw-seo' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="<?php echo esc_attr( $opt_name ); ?>[alt_text_ai_enabled]"
                       value="1" <?php checked( $ai_on ); ?>
                       id="klaw-alt-ai-toggle" />
                <?php esc_html_e( 'Enable AI vision-based alt text generation', 'klaw-seo' ); ?>
            </label>
        </td>
    </tr>

    <tr class="klaw-alt-ai-row" <?php echo $ai_on ? '' : 'style="display:none"'; ?>>
        <th scope="row">
            <label for="klaw-alt-ai-provider"><?php esc_html_e( 'AI Provider', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <select id="klaw-alt-ai-provider" name="<?php echo esc_attr( $opt_name ); ?>[alt_text_ai_provider]">
                <option value="none" <?php selected( $ai_provider, 'none' ); ?>><?php esc_html_e( 'None', 'klaw-seo' ); ?></option>
                <option value="claude" <?php selected( $ai_provider, 'claude' ); ?>><?php esc_html_e( 'Claude (Anthropic)', 'klaw-seo' ); ?></option>
                <option value="openai" <?php selected( $ai_provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI (GPT-4 Vision)', 'klaw-seo' ); ?></option>
            </select>
        </td>
    </tr>

    <tr class="klaw-alt-ai-row klaw-alt-key-claude" <?php echo ( $ai_on && $ai_provider === 'claude' ) ? '' : 'style="display:none"'; ?>>
        <th scope="row">
            <label for="klaw-alt-key-claude"><?php esc_html_e( 'Claude API Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="password" id="klaw-alt-key-claude" name="<?php echo esc_attr( $opt_name ); ?>[alt_text_ai_key_claude]"
                   value="<?php echo esc_attr( $key_claude ); ?>" class="regular-text" autocomplete="off" />
        </td>
    </tr>

    <tr class="klaw-alt-ai-row klaw-alt-key-openai" <?php echo ( $ai_on && $ai_provider === 'openai' ) ? '' : 'style="display:none"'; ?>>
        <th scope="row">
            <label for="klaw-alt-key-openai"><?php esc_html_e( 'OpenAI API Key', 'klaw-seo' ); ?></label>
        </th>
        <td>
            <input type="password" id="klaw-alt-key-openai" name="<?php echo esc_attr( $opt_name ); ?>[alt_text_ai_key_openai]"
                   value="<?php echo esc_attr( $key_openai ); ?>" class="regular-text" autocomplete="off" />
        </td>
    </tr>
</table>

<hr />

<h3><?php esc_html_e( 'Bulk Scan', 'klaw-seo' ); ?></h3>
<p class="description">
    <?php esc_html_e( 'Scan your media library for images missing alt text.', 'klaw-seo' ); ?>
</p>

<p>
    <button type="button" class="button button-secondary" id="klaw-alt-bulk-scan">
        <?php esc_html_e( 'Scan for Missing Alt Text', 'klaw-seo' ); ?>
    </button>
    <span id="klaw-alt-scan-spinner" class="spinner" style="float:none;"></span>
</p>

<div id="klaw-alt-scan-results" style="margin-top:15px;"></div>

<p id="klaw-alt-update-wrap" style="display:none; margin-top:10px;">
    <button type="button" class="button button-primary" id="klaw-alt-bulk-update">
        <?php esc_html_e( 'Auto-Fill Missing Alt Text', 'klaw-seo' ); ?>
    </button>
    <span id="klaw-alt-update-spinner" class="spinner" style="float:none;"></span>
    <span class="description" style="margin-left:8px;">
        <?php esc_html_e( 'Uses parent post title or cleaned filename for each image.', 'klaw-seo' ); ?>
    </span>
</p>
<div id="klaw-alt-update-results" style="margin-top:10px;"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle AI rows visibility.
    var aiToggle  = document.getElementById('klaw-alt-ai-toggle');
    var provider  = document.getElementById('klaw-alt-ai-provider');
    var aiRows    = document.querySelectorAll('.klaw-alt-ai-row');
    var keyFields = { claude: document.querySelector('.klaw-alt-key-claude'), openai: document.querySelector('.klaw-alt-key-openai') };

    function updateVisibility() {
        var aiEnabled = aiToggle && aiToggle.checked;
        aiRows.forEach(function (row) {
            if (row.classList.contains('klaw-alt-key-claude') || row.classList.contains('klaw-alt-key-openai')) {
                return; // handled below
            }
            row.style.display = aiEnabled ? '' : 'none';
        });
        var sel = provider ? provider.value : 'none';
        if (keyFields.claude) keyFields.claude.style.display = (aiEnabled && sel === 'claude') ? '' : 'none';
        if (keyFields.openai) keyFields.openai.style.display = (aiEnabled && sel === 'openai') ? '' : 'none';
    }

    if (aiToggle) aiToggle.addEventListener('change', updateVisibility);
    if (provider) provider.addEventListener('change', updateVisibility);

    // Bulk scan.
    var scanBtn = document.getElementById('klaw-alt-bulk-scan');
    var spinner = document.getElementById('klaw-alt-scan-spinner');
    var results = document.getElementById('klaw-alt-scan-results');

    if (scanBtn) {
        scanBtn.addEventListener('click', function () {
            spinner.classList.add('is-active');
            results.innerHTML = '';

            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                spinner.classList.remove('is-active');
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success && data.data) {
                            var html = '<p><strong>' + data.data.total + ' images missing alt text.</strong></p>';
                            if (data.data.images && data.data.images.length) {
                                html += '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Filename</th><th>Edit</th></tr></thead><tbody>';
                                data.data.images.forEach(function (img) {
                                    html += '<tr><td>' + img.id + '</td><td>' + img.filename + '</td><td><a href="' + img.edit_url + '" target="_blank">Edit</a></td></tr>';
                                });
                                html += '</tbody></table>';
                            }
                            results.innerHTML = html;
                        } else {
                            results.innerHTML = '<p class="notice notice-error">' + (data.data || 'Error') + '</p>';
                        }
                    } catch (e) {
                        results.innerHTML = '<p class="notice notice-error">Invalid response.</p>';
                    }
                }
            };
            xhr.send('action=klaw_seo_bulk_alt_scan&_ajax_nonce=' + encodeURIComponent('<?php echo esc_js( wp_create_nonce( 'klaw_seo_bulk_alt_scan' ) ); ?>'));
        });
    }

    // Bulk update.
    var updateWrap    = document.getElementById('klaw-alt-update-wrap');
    var updateBtn     = document.getElementById('klaw-alt-bulk-update');
    var updateSpinner = document.getElementById('klaw-alt-update-spinner');
    var updateResults = document.getElementById('klaw-alt-update-results');

    // Show update button after a scan finds missing images.
    var origOnload = scanBtn ? scanBtn._klawOnload : null;
    var scanObserver = new MutationObserver(function () {
        if (results.innerHTML && results.innerHTML.indexOf('0 images') === -1 && results.querySelector('table')) {
            updateWrap.style.display = '';
        } else {
            updateWrap.style.display = 'none';
        }
    });
    if (results) {
        scanObserver.observe(results, { childList: true, subtree: true });
    }

    if (updateBtn) {
        updateBtn.addEventListener('click', function () {
            updateSpinner.classList.add('is-active');
            updateBtn.disabled = true;
            updateResults.innerHTML = '';

            var xhr2 = new XMLHttpRequest();
            xhr2.open('POST', ajaxurl);
            xhr2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr2.onload = function () {
                updateSpinner.classList.remove('is-active');
                updateBtn.disabled = false;
                if (xhr2.status === 200) {
                    try {
                        var data = JSON.parse(xhr2.responseText);
                        if (data.success && data.data) {
                            var html = '<div class="notice notice-success inline" style="padding:8px 12px;"><p>';
                            html += '<strong>' + data.data.updated + ' images updated.</strong>';
                            if (data.data.skipped > 0) {
                                html += ' ' + data.data.skipped + ' skipped (no suitable alt text found).';
                            }
                            html += '</p></div>';
                            updateResults.innerHTML = html;
                            updateWrap.style.display = 'none';
                        } else {
                            updateResults.innerHTML = '<p class="notice notice-error">' + (data.data || 'Error') + '</p>';
                        }
                    } catch (e) {
                        updateResults.innerHTML = '<p class="notice notice-error">Invalid response.</p>';
                    }
                }
            };
            xhr2.send('action=klaw_seo_bulk_alt_update&_ajax_nonce=' + encodeURIComponent('<?php echo esc_js( wp_create_nonce( 'klaw_seo_bulk_alt_update' ) ); ?>'));
        });
    }
});
</script>
