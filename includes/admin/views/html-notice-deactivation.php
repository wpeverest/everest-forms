<?php
/**
 * Deactivation admin notice
 *
 * Link to settings page.
 *
 * @author      Bas Elbers
 * @category    Admin
 * @package     BE_WooCommerce_PDF_Invoices/Admin
 * @version     1.0.0
 */

global $status, $page, $s;

$reason_deactivation_url = 'http://wcpdfinvoices.com/what-made-you-deactivate';
$deactivate_url = wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . EVF_PLUGIN_BASENAME . '&amp;plugin_status=' . $status . '&amp;paged=' . $page . '&amp;s=' . $s, 'deactivate-plugin_' . EVF_PLUGIN_BASENAME );
?>
<div class="notice inline notice-alt notice-warning">
	<p><?php printf( __( 'Before we deactivate Everest Forms, would you care to <a href="%1$s" target="_blank">let us know why</a> so we can improve it for you? <a href="%2$s">No, deactivate now</a>.', 'everest-forms' ), $reason_deactivation_url, $deactivate_url ); ?></p>
</div>
