<?php
/**
 * Score Preview — renders the local AI Readiness estimate.
 *
 * The checks themselves live in Rootz_Score so the menu badge, the activation
 * notice and this view all report the same number. Each check explains WHY it
 * matters: this is a teaching document as much as a diagnostic.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rootz_result = Rootz_Score::calculate();
$rootz_checks = $rootz_result['checks'];
$rootz_score  = $rootz_result['score'];
$rootz_max    = $rootz_result['max'];
$rootz_pct    = $rootz_result['pct'];
$rootz_grade  = $rootz_result['grade'];
$rootz_color  = $rootz_result['color'];
?>

<div class="rootz-viewer-card" style="border-left: 4px solid <?php echo esc_attr( $rootz_color ); ?>; background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);">
	<div class="rootz-viewer-card-header">
		<h3>
			<span class="rootz-viewer-icon">&#127942;</span>
			<?php esc_html_e( 'AI Readiness Score', 'rootz-ai-discovery' ); ?>
		</h3>
	</div>
	<p class="rootz-viewer-explain">
		<?php esc_html_e( 'How well-prepared is your site for the AI era? Each measure below reduces AI processing costs, improves accuracy, and builds trust. A higher score means AI can serve your visitors better — with less energy and more confidence.', 'rootz-ai-discovery' ); ?>
	</p>

	<div style="display: flex; align-items: center; gap: 24px; margin: 16px 0;">
		<div style="display: flex; align-items: center; justify-content: center; width: 80px; height: 80px; border-radius: 12px; background: <?php echo esc_attr( $rootz_color ); ?>; color: #fff; font-size: 2.5rem; font-weight: 800;">
			<?php echo esc_html( $rootz_grade ); ?>
		</div>
		<div>
			<div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
				<?php echo esc_html( $rootz_score ); ?> / <?php echo esc_html( $rootz_max ); ?>
			</div>
			<div style="font-size: 0.9rem; color: #64748b;">
				<?php echo esc_html( round( $rootz_pct ) ); ?>% &mdash; <?php esc_html_e( 'estimated from local settings', 'rootz-ai-discovery' ); ?>
			</div>
		</div>
	</div>

	<ul style="list-style: none; padding: 0; margin: 16px 0 0;">
		<?php
		foreach ( $rootz_checks as $rootz_check ) :
			$rootz_is_pass = ! empty( $rootz_check['pass'] );
			$rootz_is_hint = ! $rootz_is_pass && ! empty( $rootz_check['hint'] );
			if ( $rootz_is_pass ) {
				$rootz_icon = '&#10003;';
				$rootz_bg   = '#16a34a';
			} elseif ( $rootz_is_hint ) {
				$rootz_icon = '&#8505;';
				$rootz_bg   = '#2563eb';
			} else {
				$rootz_icon = '&#10007;';
				$rootz_bg   = '#dc2626';
			}
			?>
		<li style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
			<span style="flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: <?php echo esc_attr( $rootz_bg ); ?>; color: #fff; font-size: 12px; font-weight: bold; margin-top: 1px;">
				<?php echo wp_kses( $rootz_icon, array() ); ?>
			</span>
			<div style="flex: 1;">
				<strong><?php echo esc_html( $rootz_check['label'] ); ?></strong>
				<?php if ( ! empty( $rootz_check['detail'] ) ) : ?>
					<span style="color: #64748b; font-size: 0.9rem;"> &mdash; <?php echo esc_html( $rootz_check['detail'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $rootz_check['points'] ) ) : ?>
					<span style="display: inline-block; margin-left: 6px; padding: 1px 7px; border-radius: 10px; background: #fef3c7; color: #92400e; font-size: 0.75rem; font-weight: 700;">
						<?php
						/* translators: %d: number of points this check is worth. */
						echo esc_html( sprintf( __( '+%d pts', 'rootz-ai-discovery' ), $rootz_check['points'] ) );
						?>
					</span>
				<?php endif; ?>
				<?php if ( ! empty( $rootz_check['why'] ) ) : ?>
					<div style="margin-top: 4px; color: #475569; font-size: 0.85rem; line-height: 1.5;">
						<?php echo esc_html( $rootz_check['why'] ); ?>
						<?php if ( ! empty( $rootz_check['learn'] ) ) : ?>
							<a href="<?php echo esc_url( $rootz_check['learn'] ); ?>" target="_blank" rel="noopener" style="white-space: nowrap;"><?php esc_html_e( 'Learn more', 'rootz-ai-discovery' ); ?> &rarr;</a>
						<?php endif; ?>
						<?php if ( ! empty( $rootz_check['link'] ) ) : ?>
							<a href="<?php echo esc_url( Rootz_Admin::page_url( $rootz_check['link'] ) ); ?>" style="white-space: nowrap;"><?php esc_html_e( 'Fix this', 'rootz-ai-discovery' ); ?> &rarr;</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>

	<div style="margin-top: 20px; padding: 16px; background: #f0f6fc; border-radius: 8px; border: 1px solid #bfdbfe;">
		<strong style="color: #1e40af;"><?php esc_html_e( 'Why does this matter?', 'rootz-ai-discovery' ); ?></strong>
		<p style="margin: 8px 0 0; color: #1e3a5f; font-size: 0.9rem; line-height: 1.6;">
			<?php esc_html_e( 'Every time an AI answers a question about your organization, it either reads structured data you published (fast, accurate, low energy) or scrapes your website and guesses (slow, error-prone, high energy). A high AI Readiness score means your site serves AI efficiently — reducing compute costs across the entire AI ecosystem while ensuring accuracy. Signed, structured content is the foundation of a trustworthy AI-readable web.', 'rootz-ai-discovery' ); ?>
		</p>
	</div>

	<p style="margin-top: 16px;">
		<a href="https://rootz.global/ai-discovery" target="_blank" rel="noopener" class="button button-primary">
			<?php esc_html_e( 'Get Official Score at rootz.global', 'rootz-ai-discovery' ); ?>
		</a>
		<a href="https://rootz.global/standard" target="_blank" rel="noopener" class="button" style="margin-left: 8px;">
			<?php esc_html_e( 'Read the Standard', 'rootz-ai-discovery' ); ?>
		</a>
	</p>
	<p style="margin-top: 8px; color: #64748b; font-size: 0.85rem;">
		<?php esc_html_e( 'This estimate is read from your local settings. The official score is measured from outside your site, so it can also see things only an outside observer can — such as whether your server is reachable to an AI agent at all.', 'rootz-ai-discovery' ); ?>
	</p>
</div>
