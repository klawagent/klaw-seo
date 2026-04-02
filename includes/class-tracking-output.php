<?php
/**
 * Klaw SEO — Tracking Script Output
 *
 * Outputs all configured tracking and custom scripts on the frontend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Tracking_Output {

    /**
     * Constructor — hook into wp_head, wp_body_open, and wp_footer.
     */
    public function __construct() {
        add_action( 'wp_head',      [ $this, 'output_head_scripts' ], 1 );
        add_action( 'wp_body_open', [ $this, 'output_body_scripts' ], 1 );
        add_action( 'wp_footer',    [ $this, 'output_footer_scripts' ], 99 );
    }

    /**
     * Get a tracking setting value.
     *
     * @param  string $key     Setting key.
     * @return string          Setting value or empty string.
     */
    private function get( $key ) {
        $settings = get_option( 'klaw_seo_settings', [] );
        return trim( $settings[ $key ] ?? '' );
    }

    /**
     * Output scripts in <head>.
     */
    public function output_head_scripts() {
        if ( is_admin() ) {
            return;
        }

        // Google Search Console verification.
        $search_console = $this->get( 'tracking_search_console' );
        if ( $search_console ) {
            echo '<!-- Google Search Console -->' . "\n";
            echo '<meta name="google-site-verification" content="' . esc_attr( $search_console ) . '" />' . "\n";
        }

        // Google Analytics (GA4).
        $ga4_id = $this->get( 'tracking_ga4_id' );
        if ( $ga4_id ) {
            echo '<!-- Google Analytics (GA4) -->' . "\n";
            echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $ga4_id ) . '"></script>' . "\n";
            echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag(\'js\',new Date());gtag(\'config\',\'' . esc_js( $ga4_id ) . '\');</script>' . "\n";
        }

        // Google Tag Manager (head).
        $gtm_id = $this->get( 'tracking_gtm_id' );
        if ( $gtm_id ) {
            echo '<!-- Google Tag Manager -->' . "\n";
            echo '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({\'gtm.start\':new Date().getTime(),event:\'gtm.js\'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!=\'dataLayer\'?\'&l=\'+l:\'\';j.async=true;j.src=\'https://www.googletagmanager.com/gtm.js?id=\'+i+dl;f.parentNode.insertBefore(j,f);})(window,document,\'script\',\'dataLayer\',\'' . esc_js( $gtm_id ) . '\');</script>' . "\n";
        }

        // Microsoft Clarity.
        $clarity_id = $this->get( 'tracking_clarity_id' );
        if ( $clarity_id ) {
            echo '<!-- Microsoft Clarity -->' . "\n";
            echo '<script>(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","' . esc_js( $clarity_id ) . '");</script>' . "\n";
        }

        // Meta Pixel.
        $meta_pixel = $this->get( 'tracking_meta_pixel_id' );
        if ( $meta_pixel ) {
            echo '<!-- Meta Pixel -->' . "\n";
            echo '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,\'script\',\'https://connect.facebook.net/en_US/fbevents.js\');fbq(\'init\',\'' . esc_js( $meta_pixel ) . '\');fbq(\'track\',\'PageView\');</script>' . "\n";
            echo '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . esc_attr( $meta_pixel ) . '&ev=PageView&noscript=1" /></noscript>' . "\n";
        }

        // TikTok Pixel.
        $tiktok_id = $this->get( 'tracking_tiktok_pixel_id' );
        if ( $tiktok_id ) {
            echo '<!-- TikTok Pixel -->' . "\n";
            echo '<script>!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript";o.async=!0;o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};ttq.load(\'' . esc_js( $tiktok_id ) . '\');ttq.page();}(window,document,\'ttq\');</script>' . "\n";
        }

        // Pinterest Tag.
        $pinterest_id = $this->get( 'tracking_pinterest_tag_id' );
        if ( $pinterest_id ) {
            echo '<!-- Pinterest Tag -->' . "\n";
            echo '<script>!function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");pintrk(\'load\',\'' . esc_js( $pinterest_id ) . '\');pintrk(\'page\');</script>' . "\n";
            echo '<noscript><img height="1" width="1" style="display:none" alt="" src="https://ct.pinterest.com/v3/?tid=' . esc_attr( $pinterest_id ) . '&event=init&noscript=1" /></noscript>' . "\n";
        }

        // Custom Head Scripts.
        $head_scripts = $this->get( 'tracking_head_scripts' );
        if ( $head_scripts ) {
            echo '<!-- Klaw SEO: Custom Head Scripts -->' . "\n";
            echo $head_scripts . "\n";
        }
    }

    /**
     * Output scripts after <body>.
     */
    public function output_body_scripts() {
        if ( is_admin() ) {
            return;
        }

        // GTM noscript fallback.
        $gtm_id = $this->get( 'tracking_gtm_id' );
        if ( $gtm_id ) {
            echo '<!-- Google Tag Manager (noscript) -->' . "\n";
            echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $gtm_id ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
        }

        // Custom Body Scripts.
        $body_scripts = $this->get( 'tracking_body_scripts' );
        if ( $body_scripts ) {
            echo '<!-- Klaw SEO: Custom Body Scripts -->' . "\n";
            echo $body_scripts . "\n";
        }
    }

    /**
     * Output scripts before </body>.
     */
    public function output_footer_scripts() {
        if ( is_admin() ) {
            return;
        }

        // Live Chat.
        $live_chat = $this->get( 'tracking_live_chat' );
        if ( $live_chat ) {
            echo '<!-- Klaw SEO: Live Chat -->' . "\n";
            echo $live_chat . "\n";
        }

        // Cookie Consent.
        $cookie_consent = $this->get( 'tracking_cookie_consent' );
        if ( $cookie_consent ) {
            echo '<!-- Klaw SEO: Cookie Consent -->' . "\n";
            echo $cookie_consent . "\n";
        }

        // Custom Footer Scripts.
        $footer_scripts = $this->get( 'tracking_footer_scripts' );
        if ( $footer_scripts ) {
            echo '<!-- Klaw SEO: Custom Footer Scripts -->' . "\n";
            echo $footer_scripts . "\n";
        }
    }
}
