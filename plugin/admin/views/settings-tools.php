<?php
/**
 * Tools & Preview settings tab — v1.2 with full endpoint hierarchy.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_content_enabled   = '1' === get_option( 'rootz_enable_content', '0' );
$rootz_knowledge_enabled = '1' === get_option( 'rootz_enable_knowledge', '1' );
$rootz_feed_enabled      = '1' === get_option( 'rootz_enable_feed', '1' );
?>
<form method="post" action="options.php">
	<?php settings_fields( 'rootz_tools' ); ?>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'WebMCP Tools', 'rootz-ai-discovery' ); ?>
				<?php Rootz_Admin::help_tip( __( 'WebMCP is a new browser standard (Chrome 146+) that lets AI assistants built into the browser discover and use tools on web pages &mdash; like searching your site or verifying content. This loads a small JavaScript file on your frontend pages. REST API tools work regardless of this setting.', 'rootz-ai-discovery' ), 'webmcp' ); ?>
			</th>
			<td>
				<label>
					<input type="checkbox" name="rootz_webmcp_enabled" value="1"
							<?php checked( '1', get_option( 'rootz_webmcp_enabled', '1' ) ); ?> />
					<?php esc_html_e( 'Enable WebMCP tools on frontend pages', 'rootz-ai-discovery' ); ?>
				</label>
				<p class="description">
					<?php esc_html_e( 'Loads a small JavaScript file that registers your site\'s tools with browser-based AI assistants (Chrome 146+).', 'rootz-ai-discovery' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Tier Endpoints', 'rootz-ai-discovery' ); ?>
				<?php Rootz_Admin::help_tip( __( '<strong>Discovery</strong> (<code>/.well-known/ai</code>) is always on &mdash; it provides your identity, policies, and capabilities.<br><strong>Knowledge</strong> adds an auto-generated encyclopedia from your About page and categories.<br><strong>Feed</strong> provides recent posts in an AI-optimized format.<br><strong>Content</strong> exposes full structured content through sub-endpoints (pages, posts, media).', 'rootz-ai-discovery' ), 'tiers' ); ?>
			</th>
			<td>
				<fieldset>
					<label>
						<input type="checkbox" name="rootz_enable_knowledge" value="1"
								<?php checked( '1', get_option( 'rootz_enable_knowledge', '1' ) ); ?> />
						<?php esc_html_e( 'Enable knowledge endpoint (/.well-known/ai/knowledge)', 'rootz-ai-discovery' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="rootz_enable_feed" value="1"
								<?php checked( '1', get_option( 'rootz_enable_feed', '1' ) ); ?> />
						<?php esc_html_e( 'Enable feed endpoint (/.well-known/ai/feed)', 'rootz-ai-discovery' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="rootz_enable_content" value="1"
								<?php checked( '1', get_option( 'rootz_enable_content', '0' ) ); ?> />
						<?php esc_html_e( 'Enable content endpoint (/.well-known/ai/content)', 'rootz-ai-discovery' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'The discovery endpoint (/.well-known/ai) is always active. Toggle additional tiers here.', 'rootz-ai-discovery' ); ?>
					</p>
				</fieldset>
			</td>
		</tr>
	</table>

	<?php submit_button(); ?>
</form>

<h2><?php esc_html_e( 'Endpoints', 'rootz-ai-discovery' ); ?></h2>

<table class="widefat striped" style="max-width: 800px;">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Endpoint', 'rootz-ai-discovery' ); ?></th>
			<th><?php esc_html_e( 'Tier', 'rootz-ai-discovery' ); ?></th>
			<th><?php esc_html_e( 'Purpose', 'rootz-ai-discovery' ); ?></th>
			<th><?php esc_html_e( 'Status', 'rootz-ai-discovery' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><code><a href="<?php echo esc_url( home_url( '/.well-known/ai' ) ); ?>" target="_blank" rel="noopener">/.well-known/ai</a></code></td>
			<td><?php esc_html_e( '1 — Discovery', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Identity, capabilities, policies', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( home_url( '/.well-known/ai/knowledge' ) ); ?>" target="_blank" rel="noopener">/.well-known/ai/knowledge</a></code></td>
			<td><?php esc_html_e( '2 — Extended', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Auto-generated knowledge base', 'rootz-ai-discovery' ); ?></td>
			<td>
				<?php if ( $rootz_knowledge_enabled ) : ?>
					<span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span>
				<?php else : ?>
					<span style="color: #996800;">&#9679; <?php esc_html_e( 'Disabled', 'rootz-ai-discovery' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( home_url( '/.well-known/ai/feed' ) ); ?>" target="_blank" rel="noopener">/.well-known/ai/feed</a></code></td>
			<td><?php esc_html_e( '2 — Extended', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'AI-optimized content feed', 'rootz-ai-discovery' ); ?></td>
			<td>
				<?php if ( $rootz_feed_enabled ) : ?>
					<span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span>
				<?php else : ?>
					<span style="color: #996800;">&#9679; <?php esc_html_e( 'Disabled', 'rootz-ai-discovery' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( home_url( '/.well-known/ai/content' ) ); ?>" target="_blank" rel="noopener">/.well-known/ai/content</a></code></td>
			<td><?php esc_html_e( '4 — Content', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Structured site content', 'rootz-ai-discovery' ); ?></td>
			<td>
				<?php if ( $rootz_content_enabled ) : ?>
					<span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span>
				<?php else : ?>
					<span style="color: #996800;">&#9679; <?php esc_html_e( 'Disabled', 'rootz-ai-discovery' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/policies' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/policies</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Machine-readable content policies', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/tools' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/tools</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Available tool manifest (searchable actions)', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/search?q=test' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/search</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Search site content by keyword', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/verify?page=/about/' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/verify</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Verify page hash against signed manifest', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/status' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/status</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'Self-scoring site status for management console', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( rest_url( 'rootz/v1/context' ) ); ?>" target="_blank" rel="noopener">/wp-json/rootz/v1/context</a></code></td>
			<td><?php esc_html_e( 'REST', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'AI assistant setup context (markdown)', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
		<tr>
			<td><code><a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener">/llms.txt</a></code></td>
			<td><?php esc_html_e( 'Legacy', 'rootz-ai-discovery' ); ?></td>
			<td><?php esc_html_e( 'LLM discovery file', 'rootz-ai-discovery' ); ?></td>
			<td><span style="color: #00a32a;">&#10003; <?php esc_html_e( 'Active', 'rootz-ai-discovery' ); ?></span></td>
		</tr>
	</tbody>
</table>

<h2 style="margin-top: 24px;"><?php esc_html_e( 'ai.json Preview', 'rootz-ai-discovery' ); ?></h2>
<?php
$rootz_generator = new Rootz_Ai_Json();
delete_transient( 'rootz_ai_json_cache' );
$rootz_preview = $rootz_generator->generate();
?>
<pre style="background: #1e1e1e; color: #d4d4d4; padding: 16px; border-radius: 6px; overflow-x: auto; max-width: 800px; font-size: 13px; line-height: 1.5;">
<?php
echo esc_html( wp_json_encode( $rootz_preview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
?>
</pre>
