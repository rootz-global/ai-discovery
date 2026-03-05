<?php
/**
 * Identity settings tab — v1.2 with digital identity.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form method="post" action="options.php">
    <?php settings_fields( 'rootz_identity' ); ?>

    <h3><?php esc_html_e( 'Organization', 'rootz-ai-discovery' ); ?></h3>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="rootz_organization_name"><?php esc_html_e( 'Organization Name', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'This is how AI agents like ChatGPT, Claude, and Gemini will refer to your organization when users ask about you. It defaults to your WordPress site title, but you can customize it for AI contexts.', 'rootz-ai-discovery' ), 'org-name' ); ?>
            </th>
            <td>
                <input type="text" id="rootz_organization_name" name="rootz_organization_name"
                       value="<?php echo esc_attr( get_option( 'rootz_organization_name', get_bloginfo( 'name' ) ) ); ?>"
                       class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'How your organization appears to AI agents. Defaults to your site title.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_tagline"><?php esc_html_e( 'Tagline', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'A short phrase AI agents use as a quick identifier for your site &mdash; your elevator pitch for machines. Think of it as what appears in a one-line description.', 'rootz-ai-discovery' ), 'tagline' ); ?>
            </th>
            <td>
                <input type="text" id="rootz_tagline" name="rootz_tagline"
                       value="<?php echo esc_attr( get_option( 'rootz_tagline', get_bloginfo( 'description' ) ) ); ?>"
                       class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'A short tagline or slogan. Defaults to your site tagline.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_organization_tagline"><?php esc_html_e( 'Mission', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'A longer description that AI agents use to understand what your organization does. This appears in the <code>organization.mission</code> field of your ai.json manifest.', 'rootz-ai-discovery' ), 'mission' ); ?>
            </th>
            <td>
                <input type="text" id="rootz_organization_tagline" name="rootz_organization_tagline"
                       value="<?php echo esc_attr( get_option( 'rootz_organization_tagline', '' ) ); ?>"
                       class="large-text" />
                <p class="description">
                    <?php esc_html_e( 'A longer description of what your organization does and its mission.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_sector"><?php esc_html_e( 'Sector', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'Helps AI agents categorize your site. When someone asks &ldquo;What technology companies do X?&rdquo;, your site can appear in those results. Separate multiple sectors with commas (e.g. &ldquo;Technology, AI Infrastructure&rdquo;).', 'rootz-ai-discovery' ), 'sector' ); ?>
            </th>
            <td>
                <input type="text" id="rootz_sector" name="rootz_sector"
                       value="<?php echo esc_attr( get_option( 'rootz_sector', '' ) ); ?>"
                       class="regular-text" placeholder="Technology, AI Infrastructure" />
                <p class="description">
                    <?php esc_html_e( 'Industry sector(s). Separate multiple with commas.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_legal_name"><?php esc_html_e( 'Legal Name', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_legal_name" name="rootz_legal_name"
                       value="<?php echo esc_attr( get_option( 'rootz_legal_name', '' ) ); ?>"
                       class="regular-text" />
                <p class="description">
                    <?php esc_html_e( 'Official legal entity name, if different from organization name.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_founded"><?php esc_html_e( 'Founded', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_founded" name="rootz_founded"
                       value="<?php echo esc_attr( get_option( 'rootz_founded', '' ) ); ?>"
                       class="small-text" placeholder="2024" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_headquarters"><?php esc_html_e( 'Headquarters', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_headquarters" name="rootz_headquarters"
                       value="<?php echo esc_attr( get_option( 'rootz_headquarters', '' ) ); ?>"
                       class="regular-text" placeholder="City, State, Country" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_contact_email"><?php esc_html_e( 'Contact Email', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="email" id="rootz_contact_email" name="rootz_contact_email"
                       value="<?php echo esc_attr( get_option( 'rootz_contact_email', '' ) ); ?>"
                       class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_contact_url"><?php esc_html_e( 'Contact URL', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_contact_url" name="rootz_contact_url"
                       value="<?php echo esc_attr( get_option( 'rootz_contact_url', '' ) ); ?>"
                       class="regular-text" placeholder="https://example.com/contact" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_contact_operator"><?php esc_html_e( 'Operator / Contact Person', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_contact_operator" name="rootz_contact_operator"
                       value="<?php echo esc_attr( get_option( 'rootz_contact_operator', '' ) ); ?>"
                       class="regular-text" placeholder="Jane Smith" />
                <p class="description"><?php esc_html_e( 'Person responsible for this site\'s AI Discovery data.', 'rootz-ai-discovery' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_contact_ai_email"><?php esc_html_e( 'AI Support Email', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="email" id="rootz_contact_ai_email" name="rootz_contact_ai_email"
                       value="<?php echo esc_attr( get_option( 'rootz_contact_ai_email', '' ) ); ?>"
                       class="regular-text" placeholder="ai@example.com" />
                <p class="description"><?php esc_html_e( 'Contact for AI agent developers (shown in ai.json).', 'rootz-ai-discovery' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_contact_privacy_email"><?php esc_html_e( 'Privacy Email', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="email" id="rootz_contact_privacy_email" name="rootz_contact_privacy_email"
                       value="<?php echo esc_attr( get_option( 'rootz_contact_privacy_email', '' ) ); ?>"
                       class="regular-text" placeholder="privacy@example.com" />
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'AI Summary', 'rootz-ai-discovery' ); ?></h3>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="rootz_ai_summary"><?php esc_html_e( 'AI Summary', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'Write this as if you were explaining your organization to an AI that needs to answer questions about you. Be factual and specific &mdash; this is your authoritative self-description that AI agents will use as their primary reference.', 'rootz-ai-discovery' ), 'ai-summary' ); ?>
            </th>
            <td>
                <textarea id="rootz_ai_summary" name="rootz_ai_summary" rows="4" class="large-text"><?php echo esc_textarea( get_option( 'rootz_ai_summary', '' ) ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'A 2-3 sentence summary written for AI agents. Describe what your organization does and what information is available on this site.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_core_concepts"><?php esc_html_e( 'Core Concepts', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'Define specialized terms your organization uses so AI agents explain them correctly. If your site uses jargon or domain-specific terminology, list them here. AI agents will use these definitions when answering questions about your site.', 'rootz-ai-discovery' ), 'core-concepts' ); ?>
            </th>
            <td>
                <textarea id="rootz_core_concepts" name="rootz_core_concepts" rows="6" class="large-text"><?php echo esc_textarea( get_option( 'rootz_core_concepts', '' ) ); ?></textarea>
                <p class="description">
                    <?php esc_html_e( 'Key terms and definitions for AI agents. One per line, format: term: definition', 'rootz-ai-discovery' ); ?>
                    <br />
                    <?php esc_html_e( 'Example: Digital Name: A blockchain-anchored identity that links a domain to a wallet address', 'rootz-ai-discovery' ); ?>
                    <br />
                    <?php esc_html_e( 'Leave blank to auto-generate from your categories.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <h3><?php esc_html_e( 'Digital Identity', 'rootz-ai-discovery' ); ?></h3>
    <p class="description" style="margin-bottom: 12px;">
        <?php esc_html_e( 'Optional. Link your site to a blockchain identity for verifiable provenance. Leave blank if you don\'t have a Digital Name.', 'rootz-ai-discovery' ); ?>
    </p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="rootz_digital_name"><?php esc_html_e( 'Digital Name (Wallet Address)', 'rootz-ai-discovery' ); ?></label>
                <?php Rootz_Admin::help_tip( __( 'A blockchain wallet address that cryptographically proves this domain belongs to your organization. Think of it like a verified badge, but based on cryptographic proof instead of a platform&rsquo;s review process. <strong>Leave blank if you don&rsquo;t have one</strong> &mdash; this is an advanced feature.', 'rootz-ai-discovery' ), 'digital-name' ); ?>
            </th>
            <td>
                <input type="text" id="rootz_digital_name" name="rootz_digital_name"
                       value="<?php echo esc_attr( get_option( 'rootz_digital_name', '' ) ); ?>"
                       class="regular-text" placeholder="0x..." />
                <p class="description">
                    <?php esc_html_e( 'Your organization\'s blockchain wallet address. Used in the _signature block of ai.json.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_blockchain"><?php esc_html_e( 'Blockchain Network', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_blockchain" name="rootz_blockchain"
                       value="<?php echo esc_attr( get_option( 'rootz_blockchain', '' ) ); ?>"
                       class="regular-text" placeholder="polygon" />
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="rootz_identity_contract"><?php esc_html_e( 'Identity Contract', 'rootz-ai-discovery' ); ?></label>
            </th>
            <td>
                <input type="text" id="rootz_identity_contract" name="rootz_identity_contract"
                       value="<?php echo esc_attr( get_option( 'rootz_identity_contract', '' ) ); ?>"
                       class="regular-text" placeholder="0x..." />
                <p class="description">
                    <?php esc_html_e( 'On-chain identity contract address, if registered.', 'rootz-ai-discovery' ); ?>
                </p>
            </td>
        </tr>
    </table>

    <div class="rootz-info-box">
        <h3><?php esc_html_e( 'Your AI Discovery Endpoint', 'rootz-ai-discovery' ); ?></h3>
        <p>
            <?php esc_html_e( 'Your site is now discoverable by AI agents at:', 'rootz-ai-discovery' ); ?>
        </p>
        <code style="display: block; padding: 10px; background: #f0f0f1; border-radius: 4px; margin: 8px 0;">
            <?php echo esc_url( home_url( '/.well-known/ai' ) ); ?>
        </code>
        <p style="margin-top: 12px;">
            <?php
            printf(
                /* translators: %s: link to the AI Discovery scanner */
                esc_html__( 'Scan your site at %s to check your score.', 'rootz-ai-discovery' ),
                '<a href="https://rootz.global/ai-discovery" target="_blank" rel="noopener">rootz.global/ai-discovery</a>'
            );
            ?>
        </p>
    </div>

    <?php submit_button(); ?>
</form>
