<?php
/**
 * Klaw SEO — Head Output
 *
 * Outputs all SEO meta tags, Open Graph, Twitter Cards, canonical URLs, and robots directives.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Head_Output {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 1 );
        add_filter( 'document_title_parts', [ $this, 'filter_title' ], 99 );
        add_filter( 'get_the_archive_title', [ $this, 'clean_archive_title' ], 99 );
        add_filter( 'wp_robots', [ $this, 'filter_wp_robots' ], 20 );
        // Remove WordPress default canonical to avoid duplicates.
        remove_action( 'wp_head', 'rel_canonical' );
    }

    /**
     * Explicitly set index/follow (or noindex/nofollow) on the wp_robots tag.
     *
     * WordPress's default behavior is to omit "index, follow" because it's
     * implied. We add it explicitly so users checking the page source get
     * unambiguous confirmation.
     *
     * @param  array $robots Robots directives array.
     * @return array
     */
    public function filter_wp_robots( $robots ) {
        $noindex = false;

        if ( is_singular() ) {
            $meta = get_post_meta( get_the_ID(), '_klaw_seo_noindex', true );
            if ( $meta === '1' ) {
                $noindex = true;
            }
        }

        // Clear any conflicting values before setting ours.
        unset( $robots['index'], $robots['follow'], $robots['noindex'], $robots['nofollow'] );

        if ( $noindex ) {
            $robots['noindex']  = true;
            $robots['nofollow'] = true;
        } else {
            $robots['index']  = true;
            $robots['follow'] = true;
        }

        return $robots;
    }

    /**
     * Filter the document <title> tag.
     */
    public function filter_title( $parts ) {
        // Strip HTML from title (WP 6.1+ wraps archive titles in <span>).
        // Also remove WordPress prefixes like "Archives:", "Category:", etc.
        if ( isset( $parts['title'] ) ) {
            $parts['title'] = wp_strip_all_tags( $parts['title'] );
            $parts['title'] = preg_replace( '/^(Archives|Category|Tag|Author):\s*/i', '', $parts['title'] );
        }

        if ( is_singular() ) {
            $custom = get_post_meta( get_the_ID(), '_klaw_seo_title', true );
            if ( $custom ) {
                return [ 'title' => $custom ];
            }
        }

        // Allow other plugins (e.g. Klaw Events) to supply a custom title for
        // post type archive pages like /events/.
        if ( is_post_type_archive() ) {
            $pt_slug = $this->current_archive_post_type();
            if ( $pt_slug ) {
                $custom_title = apply_filters( 'klaw_seo_archive_title', '', $pt_slug );
                if ( $custom_title ) {
                    return [ 'title' => $custom_title ];
                }
            }
        }

        // Apply title template — returns full title string, so remove other parts.
        $settings = get_option( 'klaw_seo_settings', [] );
        $sep      = $settings['title_separator'] ?? '|';

        if ( isset( $parts['title'] ) ) {
            $template_result = $this->apply_template( $parts['title'], $settings, $sep );
            if ( $template_result !== $parts['title'] ) {
                // Template was applied — it already contains site name/sep, so remove WP defaults.
                return [ 'title' => $template_result ];
            }
        }

        return $parts;
    }

    /**
     * Strip "Archives:", "Category:", etc. prefixes from archive titles.
     */
    public function clean_archive_title( $title ) {
        return preg_replace( '/^.+?:\s*/u', '', wp_strip_all_tags( $title ) );
    }

    /**
     * Output all SEO tags in <head>.
     */
    public function output() {
        echo "\n<!-- Klaw SEO -->\n";

        $this->output_description();
        $this->output_canonical();
        $this->output_open_graph();
        $this->output_twitter();

        echo "<!-- /Klaw SEO -->\n\n";
    }

    /**
     * Meta description.
     */
    private function output_description() {
        $desc = '';

        if ( is_singular() ) {
            $desc = get_post_meta( get_the_ID(), '_klaw_seo_description', true );
            if ( ! $desc ) {
                $desc = $this->auto_description();
            }
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $desc = term_description();
            $desc = wp_strip_all_tags( $desc );
            $desc = mb_substr( $desc, 0, 160 );
        } elseif ( is_post_type_archive() ) {
            // Allow other plugins (e.g. Klaw Events) to supply a custom
            // description for post type archive pages like /events/.
            $pt_slug = $this->current_archive_post_type();
            if ( $pt_slug ) {
                $desc = apply_filters( 'klaw_seo_archive_description', '', $pt_slug );

                // Fall back to the post type's registered description.
                if ( ! $desc ) {
                    $pt = get_post_type_object( $pt_slug );
                    if ( $pt && ! empty( $pt->description ) ) {
                        $desc = wp_strip_all_tags( $pt->description );
                    }
                }
            }
        }

        // Universal fallback chain: Klaw SEO default description -> WP tagline.
        if ( ! $desc ) {
            $desc = $this->site_default_description();
        }

        if ( $desc ) {
            printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $desc ) );
        }
    }

    /**
     * Get the post type slug for the current post type archive page.
     *
     * Uses get_queried_object() as the primary source since WordPress
     * populates it with a WP_Post_Type instance on these pages. Falls back
     * to get_query_var('post_type') which can be an array on some setups.
     *
     * @return string Empty string if not on a resolvable post type archive.
     */
    private function current_archive_post_type() {
        $queried = get_queried_object();
        if ( $queried instanceof WP_Post_Type ) {
            return $queried->name;
        }

        $pt = get_query_var( 'post_type' );
        if ( is_array( $pt ) ) {
            $pt = reset( $pt );
        }
        return is_string( $pt ) ? $pt : '';
    }

    /**
     * Get the site-wide fallback description.
     *
     * Priority: Klaw SEO "Default Meta Description" setting -> WordPress tagline.
     *
     * @return string
     */
    private function site_default_description() {
        $settings = get_option( 'klaw_seo_settings', [] );
        $default  = trim( $settings['default_meta_description'] ?? '' );
        if ( $default ) {
            return $default;
        }
        return (string) get_bloginfo( 'description' );
    }

    /**
     * Canonical URL.
     */
    private function output_canonical() {
        $url = '';

        if ( is_singular() ) {
            $url = get_post_meta( get_the_ID(), '_klaw_seo_canonical', true );
            if ( ! $url ) {
                $url = get_permalink();
            }
        } elseif ( is_front_page() ) {
            $url = home_url( '/' );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $url = get_term_link( get_queried_object() );
        } elseif ( is_post_type_archive() ) {
            $url = get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        // Handle pagination.
        $paged = get_query_var( 'paged', 0 );
        if ( $paged > 1 && $url ) {
            $url = get_pagenum_link( $paged );
        }

        if ( $url && ! is_wp_error( $url ) ) {
            printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $url ) );
        }
    }

    /**
     * Open Graph tags.
     */
    private function output_open_graph() {
        $tags = [];

        $tags['og:site_name'] = get_bloginfo( 'name' );
        $tags['og:locale']    = get_locale();

        if ( is_singular() ) {
            $id = get_the_ID();

            $tags['og:type']  = ( get_post_type() === 'page' ) ? 'website' : 'article';
            $tags['og:url']   = get_permalink( $id );
            $tags['og:title'] = get_post_meta( $id, '_klaw_seo_og_title', true )
                                ?: get_post_meta( $id, '_klaw_seo_title', true )
                                ?: get_the_title( $id );

            $tags['og:description'] = get_post_meta( $id, '_klaw_seo_og_description', true )
                                      ?: get_post_meta( $id, '_klaw_seo_description', true )
                                      ?: $this->auto_description()
                                      ?: $this->site_default_description();

        } elseif ( is_front_page() ) {
            $tags['og:type']        = 'website';
            $tags['og:url']         = home_url( '/' );
            $tags['og:title']       = get_bloginfo( 'name' );
            $tags['og:description'] = $this->site_default_description();
        } else {
            // Archives, search — anything else that gets shared into a chat
            // still deserves the branded card. Without og:image here, scrapers
            // fall back to whatever <img> they find on the page — in practice
            // the site logo, which crops horribly in square link previews.
            $tags['og:type']        = 'website';
            $tags['og:url']         = $this->archive_url();
            $tags['og:title']       = wp_get_document_title();
            $tags['og:description'] = $this->site_default_description();
        }

        // Image: custom OG > featured image > site default — plus dimension
        // hints, which Facebook needs to render the card on the FIRST scrape
        // instead of leaving it blank until an async image fetch completes.
        $image = $this->social_image();
        if ( $image ) {
            $tags['og:image'] = $image['url'];
            if ( $image['width'] && $image['height'] ) {
                $tags['og:image:width']  = $image['width'];
                $tags['og:image:height'] = $image['height'];
            }
            if ( $image['type'] ) {
                $tags['og:image:type'] = $image['type'];
            }
            if ( $image['alt'] ) {
                $tags['og:image:alt'] = $image['alt'];
            }
        }

        foreach ( $tags as $prop => $content ) {
            if ( $content ) {
                printf( '<meta property="%s" content="%s" />' . "\n", esc_attr( $prop ), esc_attr( $content ) );
            }
        }
    }

    /**
     * Twitter Card tags.
     */
    private function output_twitter() {
        $id = is_singular() ? get_the_ID() : 0;

        $title = $id ? ( get_post_meta( $id, '_klaw_seo_og_title', true )
                        ?: get_post_meta( $id, '_klaw_seo_title', true )
                        ?: get_the_title( $id ) )
                     : ( is_front_page() ? get_bloginfo( 'name' ) : wp_get_document_title() );

        $desc = $id ? ( get_post_meta( $id, '_klaw_seo_og_description', true )
                        ?: get_post_meta( $id, '_klaw_seo_description', true )
                        ?: $this->auto_description() )
                    : '';

        // Universal fallback.
        if ( ! $desc ) {
            $desc = $this->site_default_description();
        }

        $social = $this->social_image();
        $image  = $social ? $social['url'] : '';

        echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
        printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );

        if ( $desc ) {
            printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $desc ) );
        }
        if ( $image ) {
            printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
        }
    }

    /**
     * Resolve the social share image for the current view, with dimensions.
     *
     * Priority: per-post custom OG image > featured image > site default.
     * Width/height/type/alt resolve when the image is a Media Library
     * attachment; for an external URL only the URL itself is returned.
     * Memoized per request — the OG and Twitter emitters both call it.
     *
     * @return array|null { url, width, height, type, alt } or null when no image.
     */
    private function social_image() {
        static $resolved = false;
        static $image    = null;

        if ( $resolved ) {
            return $image;
        }
        $resolved = true;

        $url = '';

        if ( is_singular() ) {
            $id  = get_the_ID();
            $url = get_post_meta( $id, '_klaw_seo_og_image', true );

            if ( ! $url && has_post_thumbnail( $id ) ) {
                $att_id = (int) get_post_thumbnail_id( $id );
                $src    = wp_get_attachment_image_src( $att_id, 'large' );
                if ( $src ) {
                    $image = [
                        'url'    => $src[0],
                        'width'  => (int) $src[1],
                        'height' => (int) $src[2],
                        'type'   => (string) get_post_mime_type( $att_id ),
                        'alt'    => (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ),
                    ];
                    return $image;
                }
            }
        }

        if ( ! $url ) {
            $url = klaw_seo_get( 'default_og_image' );
        }
        if ( ! $url ) {
            return $image;
        }

        $image = [ 'url' => $url, 'width' => 0, 'height' => 0, 'type' => '', 'alt' => '' ];

        // Dimensions resolve only for Media Library files. The URL a custom
        // field or the settings page stores is the full-size attachment URL,
        // which is the only form attachment_url_to_postid() matches.
        $att_id = (int) attachment_url_to_postid( $url );
        if ( $att_id ) {
            $src = wp_get_attachment_image_src( $att_id, 'full' );
            if ( $src ) {
                $image['width']  = (int) $src[1];
                $image['height'] = (int) $src[2];
            }
            $image['type'] = (string) get_post_mime_type( $att_id );
            $image['alt']  = (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true );
        }

        return $image;
    }

    /**
     * Best-effort permalink for non-singular views (mirrors output_canonical()).
     * Returns '' when the view has no clean permalink (search, 404, date) —
     * the caller skips empty tags.
     */
    private function archive_url() {
        if ( is_home() ) {
            $page_for_posts = (int) get_option( 'page_for_posts' );
            return $page_for_posts ? (string) get_permalink( $page_for_posts ) : home_url( '/' );
        }
        if ( is_category() || is_tag() || is_tax() ) {
            $link = get_term_link( get_queried_object() );
            return is_wp_error( $link ) ? '' : (string) $link;
        }
        if ( is_post_type_archive() ) {
            return (string) get_post_type_archive_link( $this->current_archive_post_type() );
        }
        return '';
    }

    /**
     * Auto-generate description from current post.
     */
    private function auto_description() {
        $post = get_post();
        if ( ! $post ) {
            return '';
        }

        $settings = get_option( 'klaw_seo_settings', [] );
        $source   = $settings['description_source'] ?? 'excerpt_first';

        if ( $source === 'excerpt_first' && $post->post_excerpt ) {
            return wp_trim_words( $this->plain_text( $post->post_excerpt ), 25, '...' );
        }

        return mb_substr( $this->plain_text( $post->post_content ), 0, 160 );
    }

    /**
     * Reduce post markup to plain prose for meta descriptions. A page built
     * from shortcodes previously leaked its raw "[featured_hero] … &nbsp;"
     * source into description/og:description on every share card.
     */
    private function plain_text( $text ) {
        $text = strip_shortcodes( (string) $text );
        // strip_shortcodes() only removes REGISTERED shortcodes; drop anything
        // still bracket-shaped (deactivated plugin, typo'd tag) as well.
        $text = preg_replace( '/\[\/?[a-z0-9_-]+[^\]]*\]/i', ' ', $text );
        $text = wp_strip_all_tags( $text );
        $text = str_replace( "\xC2\xA0", ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
        return preg_replace( '/\s+/', ' ', trim( $text ) );
    }

    /**
     * Apply title template with token replacement.
     */
    private function apply_template( $title, $settings, $sep ) {
        $template = '';

        if ( is_front_page() ) {
            $template = $settings['title_template_home'] ?? '';
        } elseif ( is_home() ) {
            $template = $settings['title_template_page'] ?? '{post_title} {sep} {site_title}';
        } elseif ( is_singular( 'post' ) ) {
            $template = $settings['title_template_post'] ?? '';
        } elseif ( is_singular( 'page' ) ) {
            $template = $settings['title_template_page'] ?? '';
        } elseif ( is_archive() ) {
            $template = $settings['title_template_archive'] ?? '';
        }

        if ( ! $template ) {
            return $title;
        }

        $site_name     = get_bloginfo( 'name' );
        $tagline       = get_bloginfo( 'description' );
        $archive_title = is_archive() ? preg_replace( '/^.+?:\s*/u', '', wp_strip_all_tags( get_the_archive_title() ) ) : '';

        $replacements = [
            '{post_title}'    => $title,
            '{site_title}'    => $site_name,
            '{sep}'           => $sep,
            '{tagline}'       => $tagline,
            '{archive_title}' => $archive_title,
        ];

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }
}
