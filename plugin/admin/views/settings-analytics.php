<?php
/**
 * Analytics tab — AI access metrics.
 *
 * @package Rootz_AI_Discovery
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$summary_30 = Rootz_Metrics::get_summary( 30 );
$summary_7  = Rootz_Metrics::get_summary( 7 );
?>

<div class="rootz-section">
    <h2><?php esc_html_e( 'AI Access Analytics', 'rootz-ai-discovery' ); ?></h2>
    <p><?php esc_html_e( 'Track which AI agents visit your site and what they access.', 'rootz-ai-discovery' ); ?></p>

    <table class="rootz-status-table" style="max-width: 400px;">
        <tr>
            <td><strong><?php esc_html_e( 'Last 24 hours', 'rootz-ai-discovery' ); ?></strong></td>
            <td><?php echo esc_html( $summary_30['last24h'] ); ?> <?php esc_html_e( 'requests', 'rootz-ai-discovery' ); ?></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( 'Last 7 days', 'rootz-ai-discovery' ); ?></strong></td>
            <td><?php echo esc_html( $summary_7['total'] ); ?> <?php esc_html_e( 'requests', 'rootz-ai-discovery' ); ?></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( 'Last 30 days', 'rootz-ai-discovery' ); ?></strong></td>
            <td><?php echo esc_html( $summary_30['total'] ); ?> <?php esc_html_e( 'requests', 'rootz-ai-discovery' ); ?></td>
        </tr>
        <tr>
            <td><strong><?php esc_html_e( 'Avg. response', 'rootz-ai-discovery' ); ?></strong></td>
            <td><?php echo esc_html( $summary_30['avgMs'] ); ?>ms</td>
        </tr>
    </table>
</div>

<?php if ( ! empty( $summary_30['agents'] ) ) : ?>
<div class="rootz-section">
    <h3><?php esc_html_e( 'Top AI Agents (30 days)', 'rootz-ai-discovery' ); ?></h3>
    <table class="widefat striped" style="max-width: 500px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Agent', 'rootz-ai-discovery' ); ?></th>
                <th style="text-align: right;"><?php esc_html_e( 'Requests', 'rootz-ai-discovery' ); ?></th>
                <th style="text-align: right;"><?php esc_html_e( 'Share', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $summary_30['agents'] as $agent ) :
                $share = $summary_30['total'] > 0 ? round( ( $agent->hits / $summary_30['total'] ) * 100, 1 ) : 0;
            ?>
            <tr>
                <td><?php echo esc_html( $agent->ai_provider ); ?></td>
                <td style="text-align: right;"><?php echo esc_html( $agent->hits ); ?></td>
                <td style="text-align: right;"><?php echo esc_html( $share ); ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ( ! empty( $summary_30['endpoints'] ) ) : ?>
<div class="rootz-section">
    <h3><?php esc_html_e( 'Endpoint Breakdown (30 days)', 'rootz-ai-discovery' ); ?></h3>
    <table class="widefat striped" style="max-width: 500px;">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Endpoint', 'rootz-ai-discovery' ); ?></th>
                <th style="text-align: right;"><?php esc_html_e( 'Requests', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $summary_30['endpoints'] as $ep ) : ?>
            <tr>
                <td><code><?php echo esc_html( $ep->endpoint ); ?></code></td>
                <td style="text-align: right;"><?php echo esc_html( $ep->hits ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ( ! empty( $summary_30['recent'] ) ) : ?>
<div class="rootz-section">
    <h3><?php esc_html_e( 'Recent Requests (24 hours)', 'rootz-ai-discovery' ); ?></h3>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Time', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Agent', 'rootz-ai-discovery' ); ?></th>
                <th><?php esc_html_e( 'Endpoint', 'rootz-ai-discovery' ); ?></th>
                <th style="text-align: right;"><?php esc_html_e( 'ms', 'rootz-ai-discovery' ); ?></th>
                <th style="text-align: right;"><?php esc_html_e( 'Status', 'rootz-ai-discovery' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $summary_30['recent'] as $req ) : ?>
            <tr>
                <td><?php echo esc_html( mysql2date( 'H:i:s', $req->created_at ) ); ?></td>
                <td><?php echo esc_html( $req->ai_provider ); ?></td>
                <td><code><?php echo esc_html( $req->endpoint ); ?></code></td>
                <td style="text-align: right;"><?php echo esc_html( $req->response_ms ); ?></td>
                <td style="text-align: right;"><?php echo esc_html( $req->status_code ); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ( empty( $summary_30['agents'] ) && empty( $summary_30['recent'] ) ) : ?>
<div class="rootz-section">
    <p><em><?php esc_html_e( 'No AI access data recorded yet. Metrics will appear here as AI agents visit your site endpoints.', 'rootz-ai-discovery' ); ?></em></p>
    <p><?php esc_html_e( 'Try visiting your endpoints to generate test data:', 'rootz-ai-discovery' ); ?></p>
    <ul>
        <li><a href="<?php echo esc_url( rest_url( 'rootz/v1/ai.json' ) ); ?>" target="_blank"><code>/wp-json/rootz/v1/ai.json</code></a></li>
        <li><a href="<?php echo esc_url( rest_url( 'rootz/v1/status' ) ); ?>" target="_blank"><code>/wp-json/rootz/v1/status</code></a></li>
        <li><a href="<?php echo esc_url( rest_url( 'rootz/v1/search?q=test' ) ); ?>" target="_blank"><code>/wp-json/rootz/v1/search?q=test</code></a></li>
        <li><a href="<?php echo esc_url( rest_url( 'rootz/v1/verify?page=/about/' ) ); ?>" target="_blank"><code>/wp-json/rootz/v1/verify?page=/about/</code></a></li>
    </ul>
</div>
<?php endif; ?>
