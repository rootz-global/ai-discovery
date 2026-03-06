<?php
/**
 * Policies settings tab.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form method="post" action="options.php">
    <?php settings_fields( 'rootz_policies' ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="rootz_content_license"><?php esc_html_e( 'Default Content License', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( '<strong>All Rights Reserved</strong>: AI agents should only link to your content, not reproduce it.<br><strong>CC-BY</strong>: AI can quote with attribution.<br><strong>CC-BY-NC</strong>: Attribution required, no commercial use.<br><strong>CC0</strong>: Public domain, no restrictions.<br><br>This is a machine-readable declaration that well-behaved AI agents will respect &mdash; like robots.txt for content licensing.', 'rootz-ai-discovery' ), 'license' ); ?>
            </th>
            <td>
                <?php $current_license = get_option( 'rootz_content_license', 'all-rights-reserved' ); ?>
                <select id="rootz_content_license" name="rootz_content_license">
                    <option value="all-rights-reserved" <?php selected( $current_license, 'all-rights-reserved' ); ?>><?php esc_html_e( 'All Rights Reserved', 'rootz-ai-discovery' ); ?></option>
                    <option value="cc-by-4.0" <?php selected( $current_license, 'cc-by-4.0' ); ?>><?php esc_html_e( 'CC-BY 4.0 (Attribution)', 'rootz-ai-discovery' ); ?></option>
                    <option value="cc-by-sa-4.0" <?php selected( $current_license, 'cc-by-sa-4.0' ); ?>><?php esc_html_e( 'CC-BY-SA 4.0 (Attribution + ShareAlike)', 'rootz-ai-discovery' ); ?></option>
                    <option value="cc-by-nc-4.0" <?php selected( $current_license, 'cc-by-nc-4.0' ); ?>><?php esc_html_e( 'CC-BY-NC 4.0 (Attribution + NonCommercial)', 'rootz-ai-discovery' ); ?></option>
                    <option value="cc-by-nc-sa-4.0" <?php selected( $current_license, 'cc-by-nc-sa-4.0' ); ?>><?php esc_html_e( 'CC-BY-NC-SA 4.0 (Attribution + NonCommercial + ShareAlike)', 'rootz-ai-discovery' ); ?></option>
                    <option value="cc0" <?php selected( $current_license, 'cc0' ); ?>><?php esc_html_e( 'CC0 (Public Domain)', 'rootz-ai-discovery' ); ?></option>
                </select>
                <p class="description">
                    <?php esc_html_e( 'Tells AI agents what they can do with your content. This is a machine-readable declaration, not a legal override of your existing terms.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <?php esc_html_e( 'AI Permissions', 'rootz-ai-discovery' ); ?>
                <?php Rootz_Admin::help_tip( __( '<strong>Allow quoting</strong>: When enabled, AI agents may quote and summarize your content in their responses to users. When disabled, agents should only link to your pages.<br><br><strong>Allow training</strong>: When enabled, AI model providers are told they have permission to include your content in training data. <strong>Most site owners leave this disabled.</strong>', 'rootz-ai-discovery' ), 'ai-permissions' ); ?>
            </th>
            <td>
                <fieldset>
                    <label>
                        <input type="checkbox" name="rootz_allow_quoting" value="1"
                               <?php checked( '1', get_option( 'rootz_allow_quoting', '1' ) ); ?> />
                        <?php esc_html_e( 'Allow AI agents to quote and summarize content', 'rootz-ai-discovery' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="rootz_allow_training" value="1"
                               <?php checked( '1', get_option( 'rootz_allow_training', '0' ) ); ?> />
                        <?php esc_html_e( 'Allow AI training on this content', 'rootz-ai-discovery' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'These preferences are communicated to AI agents as structured policy data. Well-behaved agents will respect them.', 'rootz-ai-discovery' ); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
    </table>

    <div class="rootz-info-box">
        <h3><?php esc_html_e( 'How Machine-Readable Policies Work', 'rootz-ai-discovery' ); ?></h3>
        <p>
            <?php esc_html_e( 'Today, AI agents have to guess your content policies by reading legal pages written for humans. This plugin converts your choices into structured JSON that AI agents can parse instantly — like robots.txt, but for content licensing.', 'rootz-ai-discovery' ); ?>
        </p>
        <p>
            <strong><?php esc_html_e( 'Your policies endpoint:', 'rootz-ai-discovery' ); ?></strong><br />
            <code style="background: #f0f0f1; padding: 4px 8px; border-radius: 3px;"><?php echo esc_url( rest_url( 'rootz/v1/policies' ) ); ?></code>
        </p>
    </div>

    <?php submit_button(); ?>
</form>

<?php
$discovered = Rootz_Rest_Api::discover_policy_pages();
if ( ! empty( $discovered ) ) :
?>
<div class="rootz-section" style="margin-top: 24px;">
    <h2><?php esc_html_e( 'Discovered Policy Pages', 'rootz-ai-discovery' ); ?></h2>
    <p class="description"><?php esc_html_e( 'These pages were auto-detected from your site and included in the AI manifest. AI agents can read them to understand your policies.', 'rootz-ai-discovery' ); ?></p>

    <table class="widefat striped" style="max-width: 700px; margin-top: 12px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Policy Type', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Page', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'URL', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $discovered as $key => $info ) : ?>
            <tr>
                <td><strong><?php echo esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $key ) ) ); ?></strong></td>
                <td><?php echo esc_html( $info['title'] ?? $key ); ?></td>
                <td><a href="<?php echo esc_url( $info['url'] ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( wp_parse_url( $info['url'], PHP_URL_PATH ) ); ?></code></a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else : ?>
<div class="rootz-section" style="margin-top: 24px;">
    <h2><?php esc_html_e( 'Discovered Policy Pages', 'rootz-ai-discovery' ); ?></h2>
    <p><em><?php esc_html_e( 'No policy pages detected. Create pages with slugs like "privacy-policy", "terms-of-service", "cookie-policy", or "ai-policy" and they will be auto-discovered.', 'rootz-ai-discovery' ); ?></em></p>
</div>
<?php endif; ?>

<div class="rootz-section" style="margin-top: 24px;">
    <h2><?php esc_html_e( 'Policies JSON Preview', 'rootz-ai-discovery' ); ?></h2>
    <p class="description"><?php esc_html_e( 'This is what AI agents see when they request your policies endpoint.', 'rootz-ai-discovery' ); ?></p>
    <?php
    $generator = new Rootz_Ai_Json();
    $ai_json   = $generator->generate();
    $policies  = isset( $ai_json['policies'] ) ? $ai_json['policies'] : array();
    ?>
    <pre style="background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 6px; overflow-x: auto; max-width: 700px; font-size: 13px; line-height: 1.5;"><?php
    echo esc_html( wp_json_encode( $policies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
    ?></pre>
</div>
