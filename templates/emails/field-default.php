<?php
/**
 * Email form field entry.
 *
 * This is used with the {all_fields} smart tag.
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/emails/field-default.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpeverest.com/docs/everest-forms/template-structure/
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

?>
<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top:1px solid #dddddd; display:block;min-width: 100%;border-collapse: collapse;width:100%;">
	<tbody>
		<tr><td style="color:#333333;padding-top: 20px;padding-bottom: 3px;"><strong>{field_name}</strong></td></tr>
		<tr><td style="color:#555555;padding-top: 3px;padding-bottom: 20px;">{field_value}</td></tr>
	</tbody>
</table>
