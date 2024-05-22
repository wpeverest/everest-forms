<?php
/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/emails/footer-default.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.everestforms.net/
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

$background_color = '#e9eaec';
$font_family      = "'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif";
?>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</table>
					<!--[if gte mso 9]>
					</td>
					</tr>
					</table>
					<![endif]-->
					<!-- // END TEMPLATE -->
					</td>
				</tr>
			</table>
			<div class="everest-forms-footer-logo">
				<?php
				/**
				 * Modifies the footer logo for the summary report email.
				 *
				 * @since 2.0.9.
				 */
				$footer_image_url = apply_filters( 'everest_forms_report_footer_logo', plugin_dir_url( EVF_PLUGIN_FILE ) . 'assets/images/Everest-forms-horizontal-Logo.png' );
				?>
			<img src="<?php echo esc_url( $footer_image_url ); ?>" alt="<?php esc_attr_e( 'Repoting  footer logo', 'everest-forms' ); ?>" style="width:20%">
			</div>

			<?php
			// The URL will be updated once the documentation is completed.
			$footer = sprintf(
				'%1$s %2$s',
				'<div class="everest-forms-email-summary-footer-text" style="margin-top: 3rem; margin-bottom: 6rem; line-height: 1.5rem; color:#8f8f8f"><span class="everest-forms-summary-email-footer-message">' . esc_html__( 'This email was auto-generated and sent from', 'everest-forms' ) . '</span>',
				'<a href="' . esc_url( home_url() ) . '" style="color:#5317aa;">' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ) ) ) . '</a> .<br/>
				<span style="style="#8f8f8f">' . esc_html__( 'Learn ' ) . '</span>' .
				'<a href="' . esc_url( home_url() ) . '" style="color:#aeaeae;">' . esc_html__( 'how to disable.' ) . '</a>' .
				'</div>',
			);
			echo wp_kses_post( apply_filters( 'everest_forms_summary_email_footer_text', $footer ) );
			?>
		</center>
	</body>
</html>
