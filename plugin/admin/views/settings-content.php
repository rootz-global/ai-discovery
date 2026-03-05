<?php
/**
 * Content settings tab — v1.2 content endpoint configuration.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form method="post" action="options.php">
    <?php settings_fields( 'rootz_content' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <?php esc_html_e( 'Content Endpoint', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( 'This creates a structured API that lets AI agents read your content directly, instead of scraping your HTML. It&rsquo;s like RSS, but designed for AI. <strong>Disabled by default</strong> because it exposes more of your content than the basic discovery endpoint.', 'rootz-ai-discovery' ), 'content-endpoint' ); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="rootz_enable_content" value="1"
                           <?php checked( '1', get_option( 'rootz_enable_content', '0' ) ); ?> />
                    <?php esc_html_e( 'Enable content endpoint at /.well-known/ai/content', 'rootz-ai-discovery' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Serves structured site content (pages, posts, media) so AI agents can understand your site without scraping HTML. Disabled by default — enable when ready.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Include Content Types', 'rootz-ai-discovery' ); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="rootz_content_include_pages" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_pages', '1' ) ); ?> />
                        <?php esc_html_e( 'Pages', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_posts" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_posts', '1' ) ); ?> />
                        <?php esc_html_e( 'Posts', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_custom_types" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_custom_types', '0' ) ); ?> />
                        <?php esc_html_e( 'Custom post types (portfolios, products, etc.)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_media" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_media', '0' ) ); ?> />
                        <?php esc_html_e( 'Media library images (with EXIF data)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Select which content types to expose through the content endpoint.', 'rootz-ai-discovery' ); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'Full Text', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>Caution:</strong> Enabling full text means AI agents receive your complete page and post content, not just excerpts. This is useful for AI assistants that need deep knowledge of your site, but exposes more content. Your license and policy settings still apply.', 'rootz-ai-discovery' ), 'full-text' ); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="rootz_content_include_full_text" value="1"
                           <?php checked( '1', get_option( 'rootz_content_include_full_text', '0' ) ); ?> />
                    <?php esc_html_e( 'Include full post/page content (not just excerpts)', 'rootz-ai-discovery' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'When disabled, only titles, URLs, and 50-word excerpts are served. Enable for AI agents that need full content access.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_content_posts_limit"><?php esc_html_e( 'Posts Limit', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="number" id="rootz_content_posts_limit" name="rootz_content_posts_limit"
                       value="<?php echo esc_attr( get_option( 'rootz_content_posts_limit', 50 ) ); ?>"
                       class="small-text" min="1" max="500" />
                <p class="description">
                    <?php esc_html_e( 'Maximum number of posts to include. Default: 50.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_content_media_limit"><?php esc_html_e( 'Media Limit', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="number" id="rootz_content_media_limit" name="rootz_content_media_limit"
                       value="<?php echo esc_attr( get_option( 'rootz_content_media_limit', 100 ) ); ?>"
                       class="small-text" min="1" max="500" />
                <p class="description">
                    <?php esc_html_e( 'Maximum number of media items to include. Default: 100.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <div class="rootz-info-box">
        <h3><?php esc_html_e( 'Content Endpoint URLs', 'rootz-ai-discovery' ); ?></h3>
        <p><?php esc_html_e( 'When enabled, AI agents can access your content at these structured endpoints:', 'rootz-ai-discovery' ); ?></p>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><code><?php echo esc_url( home_url( '/.well-known/ai/content' ) ); ?></code> — <?php esc_html_e( 'All content', 'rootz-ai-discovery' ); ?></li>
            <li><code><?php echo esc_url( home_url( '/.well-known/ai/content/pages' ) ); ?></code> — <?php esc_html_e( 'Pages only', 'rootz-ai-discovery' ); ?></li>
            <li><code><?php echo esc_url( home_url( '/.well-known/ai/content/posts' ) ); ?></code> — <?php esc_html_e( 'Posts only', 'rootz-ai-discovery' ); ?></li>
            <li><code><?php echo esc_url( home_url( '/.well-known/ai/content/media' ) ); ?></code> — <?php esc_html_e( 'Media only', 'rootz-ai-discovery' ); ?></li>
        </ul>
        <p>
            <?php esc_html_e( 'Each content item includes an assertion type (factual, editorial, creative-work) so AI agents know how to interpret the information.', 'rootz-ai-discovery' ); ?>
        </p>
    </div>

    <?php submit_button(); ?>
</form>
