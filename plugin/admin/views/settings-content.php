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
            <th scope="row">
                <?php esc_html_e( 'Include Content Types', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>What are content types?</strong> WordPress organizes your site into different types of content:<br><br><strong>Pages</strong> — Static pages like About, Contact, Services. These are your core site content.<br><br><strong>Posts</strong> — Blog posts, news articles, updates. These are date-based content.<br><br><strong>Custom post types</strong> — If you use WooCommerce (products), a portfolio plugin, testimonials, or similar — those create custom types. Only check this if you have them and want AI to see them.<br><br><strong>Media</strong> — Images from your media library, including camera data (EXIF). Useful if your images carry important information (e.g., photography sites, product galleries).', 'rootz-ai-discovery' ), 'content-types' ); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="rootz_content_include_pages" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_pages', '1' ) ); ?> />
                        <?php esc_html_e( 'Pages (About, Contact, Services, etc.)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_posts" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_posts', '1' ) ); ?> />
                        <?php esc_html_e( 'Posts (blog articles, news, updates)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_custom_types" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_custom_types', '0' ) ); ?> />
                        <?php esc_html_e( 'Custom post types (WooCommerce products, portfolios, etc.)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_content_include_media" value="1"
                               <?php checked( '1', get_option( 'rootz_content_include_media', '0' ) ); ?> />
                        <?php esc_html_e( 'Media library images (with EXIF/camera data)', 'rootz-ai-discovery' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Choose what AI agents can read through the content API. Pages and Posts are recommended for most sites.', 'rootz-ai-discovery' ); ?>
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
                <?php Rootz_Admin::help_tip( __( '<strong>How many posts should I include?</strong> This controls how many blog posts/articles the content endpoint serves to AI agents. The most recent posts are included first.<br><br><strong>Small blog (under 50 posts):</strong> Set to your total post count.<br><strong>Medium blog (50-200 posts):</strong> Default of 50 is good — covers recent content.<br><strong>Large blog (200+ posts):</strong> Keep at 50-100 unless AI agents need your full archive.<br><br>More posts = larger API response = slower for AI agents to process.', 'rootz-ai-discovery' ), 'content-posts-limit' ); ?>
            </th>
            <td>
                <input type="number" id="rootz_content_posts_limit" name="rootz_content_posts_limit"
                       value="<?php echo esc_attr( get_option( 'rootz_content_posts_limit', 50 ) ); ?>"
                       class="small-text" min="1" max="500" />
                <p class="description">
                    <?php esc_html_e( 'Most recent posts to serve through the content API. Default: 50.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_content_media_limit"><?php esc_html_e( 'Media Limit', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( '<strong>What does this control?</strong> If you checked "Media library images" above, this limits how many images are included in the content API response. Each image entry includes the URL, dimensions, alt text, and EXIF data (camera, date taken, GPS if available).<br><br><strong>When to increase:</strong> Photography sites, product catalogs, or galleries where images are the primary content.<br><strong>When to decrease:</strong> If your media library is mostly decorative images that don\'t carry useful information for AI.', 'rootz-ai-discovery' ), 'content-media-limit' ); ?>
            </th>
            <td>
                <input type="number" id="rootz_content_media_limit" name="rootz_content_media_limit"
                       value="<?php echo esc_attr( get_option( 'rootz_content_media_limit', 100 ) ); ?>"
                       class="small-text" min="1" max="500" />
                <p class="description">
                    <?php esc_html_e( 'Media items to include (URLs, alt text, EXIF data). Default: 100. Only used if Media is checked above.', 'rootz-ai-discovery' ); ?>
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

    <h2><?php esc_html_e( 'llms.txt Generation', 'rootz-ai-discovery' ); ?></h2>
    <p class="description" style="margin-bottom: 15px;">
        <?php esc_html_e( 'The llms.txt standard (llmstxt.org) provides a concise overview of your site for AI agents with limited context windows. Think of it like a README for AI — a single plain-text file that tells ChatGPT, Claude, Perplexity, and other AI assistants what your site is about. Our implementation adds signed attestation — the one thing no other llms.txt plugin does.', 'rootz-ai-discovery' ); ?>
    </p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <?php esc_html_e( 'llms.txt', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>What is llms.txt?</strong> It\'s a plain-text file at <code>yoursite.com/llms.txt</code> that AI agents look for automatically — similar to how search engines look for <code>robots.txt</code>. It contains your site name, a short description, and links to your most important pages.<br><br><strong>Should I enable it?</strong> Yes, for most sites. It\'s safe — it only contains your page titles, URLs, and short excerpts (information that\'s already public). It helps AI assistants give accurate answers about your business instead of guessing.<br><br><strong>What makes ours special?</strong> We cryptographically sign the file with your plugin wallet, so AI agents can verify the information actually came from you, not an impersonator.', 'rootz-ai-discovery' ), 'llms-txt' ); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="rootz_enable_llms_txt" value="1"
                           <?php checked( '1', get_option( 'rootz_enable_llms_txt', '1' ) ); ?> />
                    <?php esc_html_e( 'Enable llms.txt generation at /llms.txt', 'rootz-ai-discovery' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Generates a concise, signed overview of your site. Enabled by default. Safe for all sites — only exposes page titles, URLs, and brief excerpts.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'llms-full.txt', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>What is llms-full.txt?</strong> While <code>llms.txt</code> gives AI a table of contents with links, <code>llms-full.txt</code> includes the complete text of every page and post — like giving AI the entire book, not just the index.<br><br><strong>When would I use this?</strong> If you want AI assistants (like Claude or ChatGPT) to have deep, detailed knowledge of your site\'s content in a single request. Useful for customer support bots, research agents, or RAG (retrieval-augmented generation) pipelines.<br><br><strong>Why is it opt-in?</strong> Because it exposes your full page and post content as plain text. If your content is already public on your website, this is just a more convenient format for AI. But if you have premium or gated content, leave this off.', 'rootz-ai-discovery' ), 'llms-full' ); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="rootz_enable_llms_full" value="1"
                           <?php checked( '1', get_option( 'rootz_enable_llms_full', '0' ) ); ?> />
                    <?php esc_html_e( 'Enable llms-full.txt generation at /llms-full.txt', 'rootz-ai-discovery' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Includes complete page and post content inline as markdown. Opt-in — only enable if you want AI to have your full text, not just summaries.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'Excerpts', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>What are excerpts?</strong> A one-sentence preview shown after each link in <code>llms.txt</code>. For example:<br><br><code>- [About Us](https://example.com/about): We are a cybersecurity company founded in 2015.</code><br><br><strong>Should I include them?</strong> Yes, for most sites. Excerpts help AI understand what each page is about before deciding whether to visit it. This saves the AI time and gives more accurate results. Disable only if you want a minimal, links-only file.', 'rootz-ai-discovery' ), 'llms-excerpts' ); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="rootz_llms_include_excerpts" value="1"
                           <?php checked( '1', get_option( 'rootz_llms_include_excerpts', '1' ) ); ?> />
                    <?php esc_html_e( 'Include one-sentence excerpts after each link in llms.txt', 'rootz-ai-discovery' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Adds a brief description after each page/post link so AI knows what the page covers without visiting it.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'Limits', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>Why set limits?</strong> AI agents have a maximum amount of text they can process at once (called a "context window"). A file that\'s too large gets cut off or ignored. These limits control how many items are included.<br><br><strong>Posts limit (llms.txt):</strong> How many recent blog posts to list. Default 10 is good for most blogs. A news site might want 25-50. Only titles and links are included (small).<br><br><strong>Posts limit (llms-full.txt):</strong> How many posts to include with <em>full text</em>. This gets large fast — 50 posts might be 100KB+ of text. Start with 20-50 and increase if needed.<br><br><strong>Pages limit:</strong> How many WordPress pages (About, Contact, Services, etc.) to include. Default 30 covers most sites. Pages are listed by menu order, then alphabetically.', 'rootz-ai-discovery' ), 'llms-limits' ); ?>
            </th>
            <td>
                <label>
                    <?php esc_html_e( 'Posts in llms.txt:', 'rootz-ai-discovery' ); ?>
                    <input type="number" name="rootz_llms_posts_limit"
                           value="<?php echo esc_attr( get_option( 'rootz_llms_posts_limit', 10 ) ); ?>"
                           class="small-text" min="1" max="100" />
                </label>
                <p class="description">
                    <?php esc_html_e( 'Recent blog posts to list with links. Default: 10. Only titles and URLs — lightweight.', 'rootz-ai-discovery' ); ?>
                </p>
                <br />
                <label>
                    <?php esc_html_e( 'Posts in llms-full.txt:', 'rootz-ai-discovery' ); ?>
                    <input type="number" name="rootz_llms_full_posts_limit"
                           value="<?php echo esc_attr( get_option( 'rootz_llms_full_posts_limit', 50 ) ); ?>"
                           class="small-text" min="1" max="500" />
                </label>
                <p class="description">
                    <?php esc_html_e( 'Posts to include with full text. Gets large — 50 posts may be 100KB+. Start with 20-50.', 'rootz-ai-discovery' ); ?>
                </p>
                <br />
                <label>
                    <?php esc_html_e( 'Pages:', 'rootz-ai-discovery' ); ?>
                    <input type="number" name="rootz_llms_pages_limit"
                           value="<?php echo esc_attr( get_option( 'rootz_llms_pages_limit', 30 ) ); ?>"
                           class="small-text" min="1" max="200" />
                </label>
                <p class="description">
                    <?php esc_html_e( 'WordPress pages to include (About, Contact, Services, etc.). Default: 30. Covers most sites.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <div class="rootz-info-box">
        <h3><?php esc_html_e( 'llms.txt URLs', 'rootz-ai-discovery' ); ?></h3>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><code><?php echo esc_url( home_url( '/llms.txt' ) ); ?></code> — <?php esc_html_e( 'Concise overview (signed)', 'rootz-ai-discovery' ); ?></li>
            <li><code><?php echo esc_url( home_url( '/llms-full.txt' ) ); ?></code> — <?php esc_html_e( 'Full content variant (signed, opt-in)', 'rootz-ai-discovery' ); ?></li>
        </ul>
        <p>
            <?php esc_html_e( 'Both files include a cryptographic signature from your plugin wallet, proving content authenticity and origin. Click the links above to preview what AI agents will see.', 'rootz-ai-discovery' ); ?>
        </p>
    </div>

    <?php submit_button(); ?>
</form>
