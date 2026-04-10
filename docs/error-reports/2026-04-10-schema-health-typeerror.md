# Error Report: Schema & Health Tab TypeError

**Date:** 2026-04-10
**Severity:** High (critical error, tab unusable)
**Affected versions:** Klaw SEO with the Schema & Health tab (added 2026-04-10 in commit `9d35fe4`)
**Fix commit:** `e5814e9` — "Update 2026.4.10 — Fix TypeError in render_toggle + defer translations"
**Time spent diagnosing:** ~90 minutes (most of it chasing incorrect theories)
**Reporter:** Charming Austin Texas staging site discovered the bug; same bug existed silently on Cherrywood Coffeehouse production.

---

## Symptoms

When clicking the **Klaw SEO → Schema & Health** tab in WordPress admin, the page rendered:

```
Schema & Health
Control which structured data schemas Klaw SEO outputs. All toggles default to enabled.

Structured Data

There has been a critical error on this website. Please check your site
admin email inbox for instructions. If you continue to have problems,
please try the support forums.
```

The error appeared inline within the settings page (not a full-page replacement), meaning WordPress's fatal error handler caught a PHP exception during rendering but allowed the surrounding admin chrome to continue.

All other Klaw SEO tabs (General, Social, Local Business, Sitemaps, Redirects, Robots.txt, Alt Text, Broken Links, Tracking) rendered normally. The fault was specific to `settings-schema-health.php`.

---

## Root Cause

**`Klaw_SEO_Settings::render_toggle()` called `array_key_exists( $key, $options )` while `$options` was `bool false` instead of `array`.**

Full error from `debug.log`:

```
Fatal error: Uncaught TypeError: array_key_exists(): Argument #2 ($array)
must be of type array, bool given in
/home/.../wp-content/plugins/klaw-seo/admin/class-settings-page.php:326
Stack trace:
#0 .../settings-schema-health.php(19): Klaw_SEO_Settings::render_toggle()
#1 .../class-settings-page.php(278): include('.../settings-schema-health.php')
#2 .../wp-includes/class-wp-hook.php(341): Klaw_SEO_Settings->render_page()
#3 .../wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters()
#4 .../wp-includes/plugin.php(522): WP_Hook->do_action()
#5 .../wp-admin/admin.php(264): do_action()
#6 {main}
```

### Why `$options` was `false`

In `render_page()`:
```php
$options = get_option( self::OPTION, [] );
```

The documented contract of `get_option( $key, $default )` is: if the option doesn't exist, return `$default`. If it exists, return its stored value. The stored value is normally whatever was passed to `update_option()`.

Direct phpMyAdmin inspection of the `wp_options` row on Charming Austin staging confirmed the stored value was a valid serialized array (`a:34:{...}`, 1617 bytes, 34 keys) — **yet `get_option()` returned `false`**. This violates the normal contract.

**The actual cause of the coercion was never definitively identified.** Possibilities we did not exhaustively test:

- A plugin or mu-plugin hooking `pre_option_klaw_seo_settings` or `option_klaw_seo_settings` filter and returning `false` under some condition.
- A WordPress 6.7+ interaction with the `_load_textdomain_just_in_time` too-early translation notice (see below) causing option-loading to short-circuit.
- WPX Hosting-specific object cache or persistent cache returning a stale / corrupt result for this option key.
- PHP 8.x strict type coercion somewhere in the option retrieval chain.

The fix (defensive coercion, see below) makes the root cause academic for correctness, but **the underlying "why" is still unknown**. If the same class of bug surfaces again in a different tab or setting, this is a clue that something in the Klaw SEO ↔ WordPress option layer is unreliable on at least some hosts.

---

## Fix

Two layers of defense in `admin/class-settings-page.php`:

### 1. Normalize `$options` in `render_page()`

```php
$options = get_option( self::OPTION, [] );

// Defensive: get_option can return bool false if the option value
// was saved as a non-array (corruption, legacy data, or a plugin
// filtering the option). Normalize to array so views don't fatal.
if ( ! is_array( $options ) ) {
    $options = [];
}
```

### 2. Belt-and-suspenders in `render_toggle()`

```php
public static function render_toggle( $key, $label, $description, $options ) {
    // Defensive: callers should pass an array, but fall back if not.
    if ( ! is_array( $options ) ) {
        $options = [];
    }
    $checked = array_key_exists( $key, $options ) ? ! empty( $options[ $key ] ) : true;
    // ...
}
```

---

## Secondary Issue: Translation Loading Too Early

While fixing the TypeError, the debug log also surfaced this WordPress 6.7+ notice:

```
Notice: Function _load_textdomain_just_in_time was called incorrectly.
Translation loading for the klaw-seo domain was triggered too early.
This is usually an indicator for some code in the plugin or theme
running too early. Translations should be loaded at the init action
or later.
```

### Cause

The `Klaw_SEO_Settings::__construct()` was calling `__()` to build the `$this->submenus` array:

```php
public function __construct() {
    $this->submenus = [
        'general' => __( 'General', 'klaw-seo' ),
        'social'  => __( 'Social', 'klaw-seo' ),
        // ... 8 more
    ];
    add_action( 'admin_menu', [ $this, 'register_menus' ] );
    add_action( 'admin_init', [ $this, 'register_settings' ] );
}
```

The constructor fires during `plugins_loaded` (via `klaw_seo_init()`), which runs **before** `init`. WordPress 6.7 tightened translation loading: calling `__()` for a text domain before `init` triggers this notice because the language files have not been loaded yet.

### Fix

Moved the submenus array construction out of the constructor into a lazy `get_submenus()` helper, invoked only from methods that fire at or after `admin_menu` (which is after `init`):

```php
public function __construct() {
    add_action( 'admin_menu', [ $this, 'register_menus' ] );
    add_action( 'admin_init', [ $this, 'register_settings' ] );
}

private function get_submenus() {
    if ( empty( $this->submenus ) ) {
        $this->submenus = [
            'general' => __( 'General', 'klaw-seo' ),
            // ...
        ];
    }
    return $this->submenus;
}
```

All three call sites (`register_menus()`, the nav tabs loop in `render_page()`, and `get_current_tab()`) were updated to use `$this->get_submenus()` instead of `$this->submenus`.

### Was this causing the TypeError?

**Unclear.** The translation notice and the TypeError both appeared on the same page load, but no direct causal link was established. The translation fix is worthwhile regardless — it resolves a real WordPress 6.7+ best-practice violation and silences the notice. If the notice was indirectly triggering the option-coercion (e.g. via error-handler state corruption or the textdomain loader running code with side effects), fixing the notice also removes that as a variable.

---

## Diagnostic Journey (What We Tried That Didn't Help)

This took 90 minutes of troubleshooting, and most of that time was spent on theories that turned out to be wrong. Future debugging of similar "critical error on one admin tab" issues should skip directly to reading the actual PHP fatal in `debug.log` — **don't theorize about root cause before seeing the error message.**

### Theories explored and ruled out

| # | Theory | Why ruled out |
|---|---|---|
| 1 | Old file on disk (FTP client not overwriting) | User verified file size on production matched local (~13KB). Then verified via Plugin File Editor that `render_toggle()` existed byte-for-byte as in the local source. |
| 2 | PHP opcache holding stale bytecode | User deactivated/reactivated the plugin and flushed WP-level caches with no change. Would have been the right answer on Cherrywood earlier in the day, but wasn't the issue here. |
| 3 | Duplicate plugin registration / class conflict | `active_plugins` option contained exactly one `klaw-seo/klaw-seo.php` entry. |
| 4 | Option stored as non-array in database | phpMyAdmin query showed `klaw_seo_settings` was a valid serialized array of 34 keys. This is what made the actual cause confusing — the DB had an array, but `get_option()` still returned `false`. |
| 5 | W3 Total Cache stale object cache | User deactivated W3TC entirely, no change. |
| 6 | PHP version / syntax issue with static methods | Ruled out by reading the file directly — syntax is standard PHP 7.4+ compatible. |

### What finally worked

Adding `WP_DISABLE_FATAL_ERROR_HANDLER` to `wp-config.php` alongside `WP_DEBUG` / `WP_DEBUG_DISPLAY` / `WP_DEBUG_LOG`. WordPress's own fatal error handler was catching the exception and replacing the error details with the generic "critical error" message **regardless** of `WP_DEBUG_DISPLAY`. Only by bypassing the WP fatal handler did the actual PHP stack trace surface.

**This is the key insight for future debugging:** `WP_DEBUG` alone is insufficient to see fatal errors on screen in modern WordPress (5.2+). Always pair it with `WP_DISABLE_FATAL_ERROR_HANDLER` when you need to see raw fatals.

---

## Prevention Guidelines for Future Klaw SEO Development

1. **Always defensively coerce `get_option()` returns when you expect an array.** Even with a registered `default` of `[]`, `get_option()` can return `false` in edge cases (hosting filters, corrupted cache, etc.).

   ```php
   $options = get_option( self::OPTION, [] );
   if ( ! is_array( $options ) ) {
       $options = [];
   }
   ```

2. **Never call `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_x()`, etc. in a class constructor** if the constructor runs on `plugins_loaded`. Move translation calls to methods that run on `init` or later. Use lazy initialization helpers if you need the translated strings in a property.

3. **When debugging a "critical error on this website" message, always enable this combo in `wp-config.php`** before theorizing:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_DISPLAY', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
   ```
   Then reload the broken page. Read the actual fatal message BEFORE guessing.

4. **Every helper that receives external data should validate its type** (defensive programming). The `render_toggle()` method now does this for `$options` — similar guards should exist in any public-facing static helper that accepts user-provided or setting-derived input.

5. **When a fix works but the root cause is still unknown**, document it as unknown (as this report does) rather than pretending the fix explains the whole picture. Future you will thank present you when a related bug surfaces.

---

## Verification (post-fix)

- ✅ Charming Austin Texas staging: Schema & Health tab renders cleanly
- ✅ Cherrywood Coffeehouse production: Schema & Health tab renders cleanly
- ✅ `debug.log` on Charming Austin staging: no TypeError
- ✅ `debug.log` on Charming Austin staging: no `_load_textdomain_just_in_time` notice for klaw-seo domain
