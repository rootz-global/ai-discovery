<?php
/**
 * Generates llms.txt from WordPress site structure.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rootz_Llms_Txt {

    /**
     * Generate llms.txt content.
     *
     * @return string The llms.txt content.
     */
    public function generate() {
        $cached = get_transient( 'rootz_llms_txt_cache' );
        if ( false !== $cached ) {
            return $cached;
        }

        $site_name = get_option( 'rootz_organization_name', get_bloginfo( 'name' ) );
        $tagline   = get_option( 'rootz_organization_tagline', get_bloginfo( 'description' ) );

        $lines = array();
        $lines[] = '# ' . $site_name;
        if ( ! empty( $tagline ) ) {
            $lines[] = '';
            $lines[] = '> ' . $tagline;
        }
        $lines[] = '';

        // AI Discovery endpoint.
        $lines[] = 'This site implements the AI Discovery Standard.';
        $lines[] = 'Machine-readable identity: ' . home_url( '/.well-known/ai' );
        $lines[] = '';

        // Pages.
        $pages = get_pages( array(
            'sort_column' => 'menu_order,post_title',
            'post_status' => 'publish',
            'number'      => 30,
        ) );

        if ( ! empty( $pages ) ) {
            $lines[] = '## Pages';
            $lines[] = '';
            foreach ( $pages as $page ) {
                $url = get_permalink( $page );
                $lines[] = '- [' . $page->post_title . '](' . $url . ')';
            }
            $lines[] = '';
        }

        // Recent posts.
        $posts = get_posts( array(
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );

        if ( ! empty( $posts ) ) {
            $lines[] = '## Recent Posts';
            $lines[] = '';
            foreach ( $posts as $post ) {
                $url = get_permalink( $post );
                $lines[] = '- [' . $post->post_title . '](' . $url . ')';
            }
            $lines[] = '';
        }

        // License.
        $license = get_option( 'rootz_content_license', 'all-rights-reserved' );
        $lines[] = '## Content License';
        $lines[] = '';
        $lines[] = 'Default license: ' . $license;
        if ( '1' === get_option( 'rootz_allow_quoting', '1' ) ) {
            $lines[] = 'AI agents may quote and summarize content with attribution.';
        }
        if ( '0' === get_option( 'rootz_allow_training', '0' ) ) {
            $lines[] = 'AI training on this content is not permitted.';
        }

        $content = implode( "\n", $lines ) . "\n";

        set_transient( 'rootz_llms_txt_cache', $content, HOUR_IN_SECONDS );

        return $content;
    }
}
