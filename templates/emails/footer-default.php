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
 * @see     https://docs.wpeverest.com/docs/everest-forms/template-structure/
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

$background_color = '#e9eaec';
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
						<tr>
							<td valign="top" id="templateFooter" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;background-color: <?php echo $background_color; ?>;border-top: 0;border-bottom: 0;padding-top: 12px;padding-bottom: 12px;">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" class="mcnTextBlock" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
									<tbody class="mcnTextBlockOuter">
										<tr>
											<td valign="top" class="mcnTextBlockInner" style="mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;">
												<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="min-width: 100%;border-collapse: collapse;mso-table-lspace: 0pt;mso-table-rspace: 0pt;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;" class="mcnTextContentContainer">
													<tbody>
														<tr>
															<td valign="top" class="mcnTextContent" style="padding-top: 9px;padding-right: 18px;padding-bottom: 9px;padding-left: 18px;mso-line-height-rule: exactly;-ms-text-size-adjust: 100%;-webkit-text-size-adjust: 100%;word-break: break-word;color: #aaa;font-family: Helvetica;font-size: 12px;line-height: 150%;text-align: center;">
																<!-- Footer content -->
																<?php
																/* translators: %s - link to a site. */
																$footer = sprintf( esc_html__( 'Sent from %s', 'everest-forms' ), '<a href="' . esc_url( home_url() ) . '" style="color:#bbbbbb;">' . wp_specialchars_decode( get_bloginfo( 'name' ) ) . '</a>' );
																echo apply_filters( 'everest_forms_email_footer_text', $footer );
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
		</center>
	</body>
</html>
