<?php
/**
 * Content endpoint — serves structured site content at /.well-known/ai/content.
 *
 * Supports segmented access: /content/pages, /content/posts, /content/media.
 * Based on AI Discovery Standard v1.2 content tier.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rootz_Content_Endpoint {

    /**
     * Generate all content (combined endpoint).
     *
     * @return array Full content response.
     */
    public function generate_all() {
        $cached = get_transient( 'rootz_content_cache' );
        if ( false !== $cached ) {
            return $cached;
        }

        $data = array(
            'specVersion'  => ROOTZ_AI_DISCOVERY_SPEC,
            'standard'     => 'ai-content',
            'generated'    => gmdate( 'c' ),
            'organization' => array(
                'name'   => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
                'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
            ),
            'content'      => array(),
        );

        if ( '1' === get_option( 'rootz_content_include_pages', '1' ) ) {
            $data['content']['pages'] = $this->get_pages();
        }

        if ( '1' === get_option( 'rootz_content_include_posts', '1' ) ) {
            $data['content']['posts'] = $this->get_posts();
        }

        if ( '1' === get_option( 'rootz_content_include_custom_types', '0' ) ) {
            $data['content']['custom'] = $this->get_custom_types();
        }

        if ( '1' === get_option( 'rootz_content_include_media', '0' ) ) {
            $data['content']['media'] = $this->get_media();
        }

        set_transient( 'rootz_content_cache', $data, HOUR_IN_SECONDS );
        return $data;
    }

    /**
     * Generate a single segment.
     *
     * @param string $segment Segment name (pages, posts, media, or custom type slug).
     * @return array Segment response.
     */
    public function generate_segment( $segment ) {
        $data = array(
            'specVersion'  => ROOTZ_AI_DISCOVERY_SPEC,
            'standard'     => 'ai-content',
            'segment'      => $segment,
            'generated'    => gmdate( 'c' ),
            'organization' => array(
                'name'   => get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ),
                'domain' => wp_parse_url( home_url(), PHP_URL_HOST ),
            ),
            'content'      => array(),
        );

        switch ( $segment ) {
            case 'pages':
                $data['content'] = $this->get_pages();
                break;
            case 'posts':
                $data['content'] = $this->get_posts();
                break;
            case 'media':
                $data['content'] = $this->get_media();
                break;
            default:
                // Custom post type.
                $data['content'] = $this->get_custom_type( $segment );
                break;
        }

        return $data;
    }

    /**
     * Get published pages.
     */
    private function get_pages() {
        $wp_pages = get_pages( array(
            'post_status' => 'publish',
            'sort_column' => 'post_date',
            'sort_order'  => 'DESC',
        ) );

        $include_full = '1' === get_option( 'rootz_content_include_full_text', '0' );
        $result        = array();

        foreach ( $wp_pages as $page ) {
            $item = array(
                'id'            => $page->ID,
                'title'         => $page->post_title,
                'slug'          => $page->post_name,
                'url'           => get_permalink( $page->ID ),
                'published'     => get_the_date( 'c', $page->ID ),
                'modified'      => get_the_modified_date( 'c', $page->ID ),
                'assertionType' => 'factual',
                'excerpt'       => $page->post_excerpt ? $page->post_excerpt : wp_trim_words( wp_strip_all_tags( $page->post_content ), 50 ),
            );

            if ( $include_full ) {
                $item['content']    = apply_filters( 'the_content', $page->post_content );
                $item['contentRaw'] = $page->post_content;
                $item['wordCount']  = str_word_count( wp_strip_all_tags( $page->post_content ) );
            }

            if ( has_post_thumbnail( $page->ID ) ) {
                $thumb_url = wp_get_attachment_image_url( get_post_thumbnail_id( $page->ID ), 'full' );
                if ( $thumb_url ) {
                    $item['featuredImage'] = $thumb_url;
                }
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Get published posts.
     */
    private function get_posts() {
        $limit = (int) get_option( 'rootz_content_posts_limit', 50 );
        $posts = get_posts( array(
            'numberposts' => $limit > 0 ? $limit : 50,
            'post_status' => 'publish',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );

        $include_full = '1' === get_option( 'rootz_content_include_full_text', '0' );
        $result        = array();

        foreach ( $posts as $post ) {
            $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
            $tags       = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );

            $item = array(
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'slug'          => $post->post_name,
                'url'           => get_permalink( $post->ID ),
                'published'     => get_the_date( 'c', $post->ID ),
                'modified'      => get_the_modified_date( 'c', $post->ID ),
                'assertionType' => 'editorial',
                'category'      => ! empty( $categories ) ? $categories[0] : 'uncategorized',
                'tags'          => is_array( $tags ) ? $tags : array(),
                'excerpt'       => $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 50 ),
            );

            if ( $include_full ) {
                $item['content']    = apply_filters( 'the_content', $post->post_content );
                $item['contentRaw'] = $post->post_content;
                $item['wordCount']  = str_word_count( wp_strip_all_tags( $post->post_content ) );
            }

            if ( has_post_thumbnail( $post->ID ) ) {
                $thumb_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'full' );
                if ( $thumb_url ) {
                    $item['featuredImage'] = $thumb_url;
                }
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Get all custom post types.
     */
    private function get_custom_types() {
        $types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
        $include_media = '1' === get_option( 'rootz_content_include_media', '0' );
        $include_full  = '1' === get_option( 'rootz_content_include_full_text', '0' );
        $result         = array();

        foreach ( $types as $type ) {
            $type_posts = get_posts( array(
                'post_type'   => $type->name,
                'numberposts' => 100,
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ) );

            if ( empty( $type_posts ) ) {
                continue;
            }

            $type_data = array(
                'type'  => $type->name,
                'label' => $type->labels->name,
                'items' => array(),
            );

            foreach ( $type_posts as $custom_post ) {
                $item = array(
                    'id'            => $custom_post->ID,
                    'title'         => $custom_post->post_title,
                    'slug'          => $custom_post->post_name,
                    'url'           => get_permalink( $custom_post->ID ),
                    'published'     => get_the_date( 'c', $custom_post->ID ),
                    'modified'      => get_the_modified_date( 'c', $custom_post->ID ),
                    'assertionType' => 'creative-work',
                    'excerpt'       => $custom_post->post_excerpt ? $custom_post->post_excerpt : wp_trim_words( wp_strip_all_tags( $custom_post->post_content ), 50 ),
                );

                if ( $include_full ) {
                    $item['content']    = apply_filters( 'the_content', $custom_post->post_content );
                    $item['contentRaw'] = $custom_post->post_content;
                }

                if ( has_post_thumbnail( $custom_post->ID ) ) {
                    $thumb_url = wp_get_attachment_image_url( get_post_thumbnail_id( $custom_post->ID ), 'full' );
                    if ( $thumb_url ) {
                        $item['featuredImage'] = $thumb_url;
                    }
                }

                // Attached images for portfolios/galleries.
                if ( $include_media ) {
                    $attachments = get_attached_media( 'image', $custom_post->ID );
                    if ( ! empty( $attachments ) ) {
                        $item['images'] = array();
                        foreach ( $attachments as $att ) {
                            $img = $this->format_image( $att );
                            if ( $img ) {
                                $item['images'][] = $img;
                            }
                        }
                    }
                }

                $type_data['items'][] = $item;
            }

            $result[] = $type_data;
        }

        return $result;
    }

    /**
     * Get a single custom post type by slug.
     */
    private function get_custom_type( $slug ) {
        $types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );

        $matched = null;
        foreach ( $types as $type ) {
            if ( $type->name === $slug || sanitize_title( $type->labels->name ) === $slug ) {
                $matched = $type->name;
                break;
            }
        }

        if ( ! $matched ) {
            return array();
        }

        $posts = get_posts( array(
            'post_type'   => $matched,
            'numberposts' => 100,
            'post_status' => 'publish',
        ) );

        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id'            => $post->ID,
                'title'         => $post->post_title,
                'slug'          => $post->post_name,
                'url'           => get_permalink( $post->ID ),
                'published'     => get_the_date( 'c', $post->ID ),
                'assertionType' => 'creative-work',
                'excerpt'       => $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 50 ),
            );
        }

        return $result;
    }

    /**
     * Get media library images.
     */
    private function get_media() {
        $limit = (int) get_option( 'rootz_content_media_limit', 100 );
        $media = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'numberposts'    => $limit > 0 ? $limit : 100,
            'post_status'    => 'inherit',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        $result = array();
        foreach ( $media as $image ) {
            $img = $this->format_image( $image );
            if ( $img ) {
                // Add parent post relationship.
                if ( $image->post_parent ) {
                    $parent = get_post( $image->post_parent );
                    if ( $parent ) {
                        $img['attachedTo'] = array(
                            'id'    => $parent->ID,
                            'title' => $parent->post_title,
                            'url'   => get_permalink( $parent->ID ),
                        );
                    }
                }
                $result[] = $img;
            }
        }

        return $result;
    }

    /**
     * Format an attachment post into structured image data.
     */
    private function format_image( $attachment ) {
        $data = array(
            'id'          => $attachment->ID,
            'url'         => wp_get_attachment_url( $attachment->ID ),
            'title'       => $attachment->post_title,
            'caption'     => $attachment->post_excerpt,
            'description' => $attachment->post_content,
            'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
            'uploaded'    => get_the_date( 'c', $attachment->ID ),
        );

        $metadata = wp_get_attachment_metadata( $attachment->ID );
        if ( $metadata ) {
            if ( isset( $metadata['width'] ) ) {
                $data['width'] = $metadata['width'];
            }
            if ( isset( $metadata['height'] ) ) {
                $data['height'] = $metadata['height'];
            }
            $data['mime'] = get_post_mime_type( $attachment->ID );

            // EXIF data.
            if ( ! empty( $metadata['image_meta'] ) ) {
                $exif = $metadata['image_meta'];
                $exif_out = array();
                if ( ! empty( $exif['camera'] ) ) $exif_out['camera'] = $exif['camera'];
                if ( ! empty( $exif['focal_length'] ) ) $exif_out['focalLength'] = $exif['focal_length'];
                if ( ! empty( $exif['aperture'] ) ) $exif_out['aperture'] = $exif['aperture'];
                if ( ! empty( $exif['shutter_speed'] ) ) $exif_out['shutterSpeed'] = $exif['shutter_speed'];
                if ( ! empty( $exif['iso'] ) ) $exif_out['iso'] = $exif['iso'];
                if ( ! empty( $exif_out ) ) {
                    $data['exif'] = $exif_out;
                }
            }
        }

        return $data;
    }
}
