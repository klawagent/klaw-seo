<?php
/**
 * Klaw SEO — Schema / JSON-LD
 *
 * Outputs structured data in JSON-LD format:
 * - LocalBusiness (from settings)
 * - Event (from configurable post type + field mapping)
 * - BreadcrumbList (auto from page hierarchy)
 * - FAQPage (from _klaw_seo_faq post meta)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Klaw_SEO_Schema {

    /**
     * Constructor — hook into wp_head at priority 2.
     */
    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 2 );
    }

    /**
     * Main output handler.
     */
    public function output() {
        $schemas = [];

        $website = $this->website();
        if ( $website ) {
            $schemas[] = $website;
        }

        $blog_posting = $this->blog_posting();
        if ( $blog_posting ) {
            $schemas[] = $blog_posting;
        }

        $event = $this->event();
        if ( $event ) {
            $schemas[] = $event;
        }

        $item_list = $this->item_list();
        if ( $item_list ) {
            $schemas[] = $item_list;
        }

        $breadcrumb = $this->breadcrumb();
        if ( $breadcrumb ) {
            $schemas[] = $breadcrumb;
        }

        $faq = $this->faq();
        if ( $faq ) {
            $schemas[] = $faq;
        }

        $local = $this->local_business();
        if ( $local ) {
            $schemas[] = $local;
        }

        foreach ( $schemas as $schema ) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo "\n</script>\n";
        }
    }

    /**
     * Build LocalBusiness schema from plugin settings.
     *
     * @return array|null
     */
    private function local_business() {
        $s = get_option( 'klaw_seo_settings', [] );

        if ( empty( $s['business_name'] ) ) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => $s['business_type'] ?? 'LocalBusiness',
            'name'     => $s['business_name'],
        ];

        // Address.
        $address = [];
        if ( ! empty( $s['business_street'] ) ) {
            $address['streetAddress'] = $s['business_street'];
        }
        if ( ! empty( $s['business_city'] ) ) {
            $address['addressLocality'] = $s['business_city'];
        }
        if ( ! empty( $s['business_state'] ) ) {
            $address['addressRegion'] = $s['business_state'];
        }
        if ( ! empty( $s['business_zip'] ) ) {
            $address['postalCode'] = $s['business_zip'];
        }
        if ( ! empty( $s['business_country'] ) ) {
            $address['addressCountry'] = $s['business_country'];
        }
        if ( $address ) {
            $address['@type'] = 'PostalAddress';
            $schema['address'] = $address;
        }

        // Geo.
        if ( ! empty( $s['business_lat'] ) && ! empty( $s['business_lng'] ) ) {
            $schema['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $s['business_lat'],
                'longitude' => (float) $s['business_lng'],
            ];
        }

        // Contact.
        if ( ! empty( $s['business_phone'] ) ) {
            $schema['telephone'] = $s['business_phone'];
        }
        if ( ! empty( $s['business_email'] ) ) {
            $schema['email'] = $s['business_email'];
        }

        // Price range.
        if ( ! empty( $s['business_price_range'] ) ) {
            $schema['priceRange'] = $s['business_price_range'];
        }

        // URL.
        $schema['url'] = home_url( '/' );

        if ( ! empty( $s['business_gbp_url'] ) ) {
            $schema['hasMap'] = $s['business_gbp_url'];
        }

        // Opening hours.
        if ( ! empty( $s['business_hours'] ) ) {
            $hours = $this->parse_hours( $s['business_hours'] );
            if ( $hours ) {
                $schema['openingHoursSpecification'] = $hours;
            }
        }

        // Social profiles.
        $social = [];
        foreach ( [ 'social_facebook', 'social_instagram', 'social_twitter', 'social_linkedin', 'social_pinterest', 'social_youtube' ] as $key ) {
            if ( ! empty( $s[ $key ] ) ) {
                $social[] = $s[ $key ];
            }
        }
        if ( $social ) {
            $schema['sameAs'] = $social;
        }

        return $schema;
    }

    /**
     * Parse operating hours text into schema format.
     *
     * @param  string $text Hours textarea content.
     * @return array
     */
    private function parse_hours( $text ) {
        $day_map = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
        ];

        $specs = [];
        $lines = array_filter( array_map( 'trim', explode( "\n", $text ) ) );

        foreach ( $lines as $line ) {
            // Expected format: Day HH:MM-HH:MM
            $parts = preg_split( '/\s+/', $line, 2 );
            if ( count( $parts ) < 2 ) {
                continue;
            }

            $day_key  = strtolower( $parts[0] );
            $time_str = $parts[1];

            if ( ! isset( $day_map[ $day_key ] ) ) {
                continue;
            }

            if ( strtolower( $time_str ) === 'closed' ) {
                continue;
            }

            $times = explode( '-', $time_str );
            if ( count( $times ) !== 2 ) {
                continue;
            }

            $specs[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => $day_map[ $day_key ],
                'opens'     => trim( $times[0] ),
                'closes'    => trim( $times[1] ),
            ];
        }

        return $specs;
    }

    /**
     * Build Event schema for the configured post type using mapped custom fields.
     *
     * @return array|null
     */
    private function event() {
        if ( ! is_singular() ) {
            return null;
        }

        $s       = get_option( 'klaw_seo_settings', [] );
        $evt_pt  = $s['event_post_type'] ?? '';

        if ( ! $evt_pt || get_post_type() !== $evt_pt ) {
            return null;
        }

        $post_id = get_the_ID();
        $post    = get_post( $post_id );

        // Read Klaw Events meta fields directly.
        $event_date = get_post_meta( $post_id, '_klaw_event_date', true );
        $time_start = get_post_meta( $post_id, '_klaw_event_time_start', true );
        $time_end   = get_post_meta( $post_id, '_klaw_event_time_end', true );
        $desc       = get_post_meta( $post_id, '_klaw_event_description', true );

        // Venue: pull from event meta, fall back to settings value.
        $venue_key = $s['event_venue_field'] ?? '';
        $venue     = get_post_meta( $post_id, '_klaw_event_venue', true );
        if ( ! $venue && $venue_key ) {
            $venue = $venue_key;
        }

        if ( ! $desc ) {
            $desc = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }

        // Build ISO 8601 start/end dates.
        $start_date = '';
        $end_date   = '';
        if ( $event_date ) {
            if ( $time_start ) {
                $start_date = $event_date . 'T' . $time_start . ':00';
            } else {
                $start_date = $event_date;
            }
            if ( $time_end ) {
                $end_date = $event_date . 'T' . $time_end . ':00';
            }
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Event',
            'name'        => get_the_title( $post_id ),
            'url'         => get_permalink( $post_id ),
            'description' => $desc,
        ];

        if ( $start_date ) {
            $schema['startDate'] = $start_date;
        }
        if ( $end_date ) {
            $schema['endDate'] = $end_date;
        }

        if ( $venue ) {
            $location = [
                '@type' => 'Place',
                'name'  => $venue,
            ];
            $street = $s['business_street'] ?? '';
            if ( $street ) {
                $location['address'] = [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => $street,
                    'addressLocality' => $s['business_city'] ?? '',
                    'addressRegion'   => $s['business_state'] ?? '',
                    'postalCode'      => $s['business_zip'] ?? '',
                    'addressCountry'  => $s['business_country'] ?? 'US',
                ];
            }
            $schema['location'] = $location;
        }

        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        // Event status — default to scheduled.
        $schema['eventStatus']    = 'https://schema.org/EventScheduled';
        $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

        // Organizer — default to site.
        $schema['organizer'] = [
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        ];

        // Performer — default to event title.
        $schema['performer'] = [
            '@type' => 'PerformingGroup',
            'name'  => get_the_title( $post_id ),
        ];

        return $schema;
    }

    /**
     * Build BreadcrumbList schema from page/post hierarchy.
     *
     * @return array|null
     */
    private function breadcrumb() {
        if ( is_front_page() || is_admin() ) {
            return null;
        }

        $items = [];
        $pos   = 1;

        // Home.
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => get_bloginfo( 'name' ),
            'item'     => home_url( '/' ),
        ];

        if ( is_singular() ) {
            $post = get_post();

            // Add taxonomy breadcrumb for posts.
            if ( $post->post_type === 'post' ) {
                $categories = get_the_category( $post->ID );
                if ( $categories ) {
                    $cat = $categories[0];
                    // Add parent categories.
                    $ancestors = get_ancestors( $cat->term_id, 'category' );
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $anc_id ) {
                        $anc = get_term( $anc_id, 'category' );
                        if ( $anc && ! is_wp_error( $anc ) ) {
                            $link = get_term_link( $anc );
                            if ( ! is_wp_error( $link ) ) {
                                $items[] = [
                                    '@type'    => 'ListItem',
                                    'position' => $pos++,
                                    'name'     => $anc->name,
                                    'item'     => $link,
                                ];
                            }
                        }
                    }
                    $cat_link = get_term_link( $cat );
                    if ( ! is_wp_error( $cat_link ) ) {
                        $items[] = [
                            '@type'    => 'ListItem',
                            'position' => $pos++,
                            'name'     => $cat->name,
                            'item'     => $cat_link,
                        ];
                    }
                }
            }

            // Add parent pages for hierarchical types.
            if ( is_post_type_hierarchical( $post->post_type ) && $post->post_parent ) {
                $ancestors = get_post_ancestors( $post->ID );
                $ancestors = array_reverse( $ancestors );
                foreach ( $ancestors as $anc_id ) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $pos++,
                        'name'     => get_the_title( $anc_id ),
                        'item'     => get_permalink( $anc_id ),
                    ];
                }
            }

            // Current page.
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $pos,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            ];
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                // Parent terms.
                if ( $term->parent ) {
                    $ancestors = get_ancestors( $term->term_id, $term->taxonomy );
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $anc_id ) {
                        $anc = get_term( $anc_id, $term->taxonomy );
                        if ( $anc && ! is_wp_error( $anc ) ) {
                            $link = get_term_link( $anc );
                            if ( ! is_wp_error( $link ) ) {
                                $items[] = [
                                    '@type'    => 'ListItem',
                                    'position' => $pos++,
                                    'name'     => $anc->name,
                                    'item'     => $link,
                                ];
                            }
                        }
                    }
                }
                $term_link = get_term_link( $term );
                if ( ! is_wp_error( $term_link ) ) {
                    $items[] = [
                        '@type'    => 'ListItem',
                        'position' => $pos,
                        'name'     => $term->name,
                        'item'     => $term_link,
                    ];
                }
            }
        } elseif ( is_post_type_archive() ) {
            $pt = get_post_type_object( get_query_var( 'post_type' ) );
            if ( $pt ) {
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'name'     => $pt->labels->name,
                    'item'     => get_post_type_archive_link( $pt->name ),
                ];
            }
        }

        if ( count( $items ) < 2 ) {
            return null;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Build FAQPage schema from post meta.
     *
     * @return array|null
     */
    private function faq() {
        if ( ! is_singular() ) {
            return null;
        }

        $faq = get_post_meta( get_the_ID(), '_klaw_seo_faq', true );
        if ( ! is_array( $faq ) || empty( $faq ) ) {
            return null;
        }

        $entities = [];
        foreach ( $faq as $item ) {
            $q = $item['question'] ?? '';
            $a = $item['answer'] ?? '';
            if ( ! $q || ! $a ) {
                continue;
            }
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $q,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $a,
                ],
            ];
        }

        if ( empty( $entities ) ) {
            return null;
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * Build WebSite schema with SearchAction on the homepage.
     *
     * @return array|null
     */
    private function website() {
        if ( ! is_front_page() ) {
            return null;
        }

        $s = get_option( 'klaw_seo_settings', [] );
        if ( isset( $s['schema_website_enabled'] ) && ! $s['schema_website_enabled'] ) {
            return null;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => get_bloginfo( 'name' ),
            'url'             => home_url( '/' ),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => home_url( '/?s={search_term_string}' ),
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Build BlogPosting schema on single post pages.
     *
     * @return array|null
     */
    private function blog_posting() {
        if ( ! is_singular() ) {
            return null;
        }

        $s = get_option( 'klaw_seo_settings', [] );
        if ( isset( $s['schema_blog_posting_enabled'] ) && ! $s['schema_blog_posting_enabled'] ) {
            return null;
        }

        // Default to post only; filter for extensibility.
        $post_types = apply_filters( 'klaw_seo_blog_posting_post_types', [ 'post' ] );
        if ( ! in_array( get_post_type(), $post_types, true ) ) {
            return null;
        }

        $post_id = get_the_ID();
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return null;
        }

        $desc = get_post_meta( $post_id, '_klaw_seo_description', true );
        if ( ! $desc ) {
            $desc = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '...' );
        }

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => get_the_title( $post_id ),
            'description'      => $desc,
            'datePublished'    => mysql2date( 'c', $post->post_date_gmt, false ),
            'dateModified'     => mysql2date( 'c', $post->post_modified_gmt, false ),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => get_permalink( $post_id ),
            ],
        ];

        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post_id, 'large' );
        }

        // Author.
        $author_id = (int) $post->post_author;
        if ( $author_id ) {
            $schema['author'] = [
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $author_id ),
                'url'   => get_author_posts_url( $author_id ),
            ];
        }

        // Publisher — site name + Site Icon as logo if present.
        $publisher = [
            '@type' => 'Organization',
            'name'  => get_bloginfo( 'name' ),
        ];
        $site_icon = get_site_icon_url( 512 );
        if ( $site_icon ) {
            $publisher['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $site_icon,
            ];
        }
        $schema['publisher'] = $publisher;

        return $schema;
    }

    /**
     * Build ItemList schema on post type archive pages.
     *
     * Enabled per post type via settings: schema_item_list_{post_type}.
     * For events, each item includes startDate, url, and location.
     *
     * @return array|null
     */
    private function item_list() {
        if ( ! is_post_type_archive() ) {
            return null;
        }

        $post_type = get_query_var( 'post_type' );
        if ( is_array( $post_type ) ) {
            $post_type = reset( $post_type );
        }
        if ( ! $post_type ) {
            return null;
        }

        $s          = get_option( 'klaw_seo_settings', [] );
        $toggle_key = 'schema_item_list_' . $post_type;
        if ( empty( $s[ $toggle_key ] ) ) {
            return null;
        }

        $posts = get_posts( [
            'post_type'              => $post_type,
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ] );

        if ( ! $posts ) {
            return null;
        }

        $is_event = ( $post_type === 'event' );
        $elements = [];
        $position = 1;

        foreach ( $posts as $p ) {
            $item = [
                '@type' => $is_event ? 'Event' : 'Thing',
                'name'  => $p->post_title,
                'url'   => get_permalink( $p->ID ),
            ];

            if ( $is_event ) {
                $event_date = get_post_meta( $p->ID, '_klaw_event_date', true );
                $time_start = get_post_meta( $p->ID, '_klaw_event_time_start', true );
                if ( $event_date ) {
                    $item['startDate'] = $time_start
                        ? $event_date . 'T' . $time_start . ':00'
                        : $event_date;
                }
                $venue = get_post_meta( $p->ID, '_klaw_event_venue', true );
                if ( ! $venue ) {
                    $venue = $s['business_name'] ?? '';
                }
                if ( $venue ) {
                    $item['location'] = [
                        '@type' => 'Place',
                        'name'  => $venue,
                    ];
                }
            }

            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'item'     => $item,
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $elements,
        ];
    }
}
