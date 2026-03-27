<?php
/**
 * The deactivate feedback model class for ThemeGrill SDK
 *
 * @package     ThemeGrillSDK
 * @subpackage  Feedback
 * @copyright   Copyright (c) 2025, ThemeGrill
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0.0
 */

namespace ThemeGrillSDK\Modules;

use ThemeGrillSDK\Common\AbstractModule;
use ThemeGrillSDK\Loader;
use ThemeGrillSDK\Product;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Uninstall feedback module for ThemeGrill SDK.
 */
class UninstallFeedback extends AbstractModule {
	/**
	 * How many seconds before the deactivation window is triggered for themes?
	 *
	 * @var int Number of days.
	 */
	const AUTO_TRIGGER_DEACTIVATE_WINDOW_SECONDS = 3;
	/**
	 * How many days before the deactivation window pops up again for the theme?
	 *
	 * @var int Number of days.
	 */
	const PAUSE_DEACTIVATE_WINDOW_DAYS = 100;
	/**
	 * Where to send the data.
	 *
	 * @var string Endpoint url.
	 */
	const FEEDBACK_ENDPOINT = 'https://api.themeGrill.com/tracking/uninstall';

	/**
	 * Default options for plugins.
	 *
	 * @var array $options_plugin The main options list for plugins.
	 */
	private $options_plugin = array(
		'id3' => array(
			'id'   => 3,
			'type' => 'text',

		),
		'id4' => array(
			'type' => 'textarea',
			'id'   => 4,
		),
		'id5' => array(
			'id'   => 5,
			'type' => 'textarea',
		),
		'id6' => array(
			'type' => 'textarea',
			'id'   => 6,
		),
	);
	/**
	 * Default options for theme.
	 *
	 * @var array $options_theme The main options list for themes.
	 */
	private $options_theme = array(
		'id7'  => array(
			'id' => 7,
		),
		'id8'  => array(
			'type' => 'text',
			'id'   => 8,
		),
		'id9'  => array(
			'id'   => 9,
			'type' => 'text',
		),
		'id10' => array(
			'title' => '',
			'id'    => 10,
		),
	);
	/**
	 * Default other option.
	 *
	 * @var array $other The other option
	 */
	private $other = array(
		'id999' => array(
			'id'   => 999,
			'type' => 'textarea',
		),
	);

	/**
	 * Loads the additional resources
	 */
	public function load_resources() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, array( 'theme-install', 'plugins' ), true ) ) {
			return;
		}

		$this->add_feedback_popup_style();

		if ( $this->product->get_type() === 'theme' ) {
			$this->add_theme_feedback_drawer_js();
			$this->render_theme_feedback_popup();

			return;
		}
		$this->add_plugin_feedback_popup_js();
		$this->render_plugin_feedback_popup();
	}

	/**
	 * Render theme feedback drawer.
	 */
	private function render_theme_feedback_popup() {
		$heading              = str_replace( '{theme}', $this->product->get_name(), Loader::$labels['uninstall']['heading_theme'] );
		$button_submit        = apply_filters( $this->product->get_key() . '_feedback_deactivate_button_submit', Loader::$labels['uninstall']['submit'] );
		$options              = $this->options_theme;
		$options              = $this->randomize_options( apply_filters( $this->product->get_key() . '_feedback_deactivate_options', $options ) );
		$info_disclosure_link = '<a href="#" class="info-disclosure-link">' . apply_filters( $this->product->get_slug() . '_themeGrill_sdk_info_collect_cta', Loader::$labels['uninstall']['cta_info'] ) . '</a>';

		$options += $this->other;

		?>
		<div class="tgsdk-theme-uninstall-feedback-drawer tgsdk-feedback">
			<div class="popup--header">
				<h5><?php echo wp_kses( $heading, array( 'span' => true ) ); ?> </h5>
				<button class="toggle"><span>&times;</span></button>
			</div><!--/.popup--header-->
			<div class="popup--body">
				<?php $this->render_options_list( $options ); ?>
			</div><!--/.popup--body-->
			<div class="popup--footer">
				<div class="actions">
					<?php
					echo wp_kses_post( $info_disclosure_link );
					echo wp_kses_post( $this->get_disclosure_labels() );
					echo '<div class="buttons">';
					echo get_submit_button( //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Function has an internal sanitization.
						$button_submit,
						'secondary',
						$this->product->get_key() . 'tgsdk-deactivate-yes',
						false,
						array(
							'data-after-text' => $button_submit,
							'disabled'        => true,
						)
					);
					echo '</div>';
					?>
				</div><!--/.actions-->
			</div><!--/.popup--footer-->
		</div>
		<?php
	}

	/**
	 * Add feedback styles.
	 */
	private function add_feedback_popup_style() {
		?>
		<style>
			.tgsdk-feedback {
				background: #fff;
				max-width: 400px;
				z-index: 10000;
				box-shadow: 0 0 15px -5px rgba(0, 0, 0, .5);
				transition: all .3s ease-out;
			}


			.tgsdk-feedback .popup--header {
				position: relative;
				background-color: #2271b1;
			}

			.tgsdk-feedback .popup--header h5 {
				margin: 0;
				font-size: 16px;
				padding: 15px;
				color: #fff;
				font-weight: 600;
				text-align: center;
				letter-spacing: .3px;
			}

			.tgsdk-feedback .popup--body {
				padding: 15px;
			}

			.tgsdk-feedback .popup--form {
				margin: 0;
				font-size: 13px;
			}

			.tgsdk-feedback .popup--form input[type="radio"] {
			<?php echo is_rtl() ? 'margin: 0 0 0 10px;' : 'margin: 0 10px 0 0;'; ?>
			}

			.tgsdk-feedback .popup--form input[type="radio"]:checked ~ textarea {
				display: block;
			}

			.tgsdk-feedback .popup--form textarea {
				width: 100%;
				margin: 10px 0 0;
				display: none;
				max-height: 150px;
			}

			.tgsdk-feedback li {
				display: flex;
				align-items: center;
				margin-bottom: 15px;
				flex-wrap: wrap;
			}

			.tgsdk-feedback li label {
				max-width: 90%;
			}

			.tgsdk-feedback li:last-child {
				margin-bottom: 0;
			}

			.tgsdk-feedback .popup--footer {
				padding: 0 15px 15px;
			}

			.tgsdk-feedback .actions {
				display: flex;
				flex-wrap: wrap;
			}

			.info-disclosure-link {
				width: 100%;
				margin-bottom: 15px;
			}

			.tgsdk-feedback .info-disclosure-content {
				max-height: 0;
				overflow: hidden;
				width: 100%;
				transition: .3s ease;
			}

			.tgsdk-feedback .info-disclosure-content.active {
				max-height: 300px;
			}

			.tgsdk-feedback .info-disclosure-content p {
				margin: 0;
			}

			.tgsdk-feedback .info-disclosure-content ul {
				margin: 10px 0;
				border-radius: 3px;
			}

			.tgsdk-feedback .info-disclosure-content ul li {
				display: flex;
				align-items: center;
				justify-content: space-between;
				margin-bottom: 0;
				padding: 5px 0;
				border-bottom: 1px solid #ccc;
			}

			.tgsdk-feedback .buttons {
				display: flex;
				width: 100%;
			}

			.tgsdk-feedback .buttons input:last-child {
			<?php echo is_rtl() ? 'margin-right: auto;' : 'margin-left: auto;'; ?>
			}

			.tgsdk-theme-uninstall-feedback-drawer {
				border-top-left-radius: 5px;
				position: fixed;
				top: 100%;
				right: 15px;
			}

			.tgsdk-theme-uninstall-feedback-drawer.active {
				transform: translateY(-100%);
			}

			.tgsdk-theme-uninstall-feedback-drawer .popup--header {
				border-top-left-radius: 5px;
			}

			.tgsdk-theme-uninstall-feedback-drawer .popup--header .toggle {
				position: absolute;
				padding: 3px 0;
				width: 30px;
				top: -26px;
				right: 0;
				cursor: pointer;
				border-top-left-radius: 5px;
				border-top-right-radius: 5px;
				font-size: 20px;
				background-color: #2271b1;
				color: #fff;
				border: none;
				line-height: 20px;
			}

			.tgsdk-theme-uninstall-feedback-drawer .toggle span {
				margin: 0;
				display: inline-block;
			}

			.tgsdk-theme-uninstall-feedback-drawer:not(.active) .toggle span {
				transform: rotate(45deg);
			}

			.tgsdk-theme-uninstall-feedback-drawer .popup--header .toggle:hover {
				background-color: #1880a5;
			}


			.tgsdk-plugin-uninstall-feedback-popup .popup--header:before {
				content: "";
				display: block;
				position: absolute;
				top: 50%;
				transform: translateY(-50%);
			<?php
			echo is_rtl() ?
			'right: -10px;
			border-top: 20px solid transparent;
			border-left: 20px solid #2271b1;
			border-bottom: 20px solid transparent;' :
			'left: -10px;
			border-top: 20px solid transparent;
			border-right: 20px solid #2271b1;
			border-bottom: 20px solid transparent;';
			?>
			}

			.tgsdk-plugin-uninstall-feedback-popup {
				display: none;
				position: absolute;
				white-space: normal;
				width: 400px;
			<?php echo is_rtl() ? 'right: calc( 100% + 15px );' : 'left: calc( 100% + 15px );'; ?> top: -15px;
			}

			.tgsdk-plugin-uninstall-feedback-popup.sending-feedback .popup--body i {
				animation: rotation 2s infinite linear;
				display: block;
				float: none;
				align-items: center;
				width: 100%;
				margin: 0 auto;
				height: 100%;
				background: transparent;
				padding: 0;
			}

			.tgsdk-plugin-uninstall-feedback-popup.sending-feedback .popup--body i:before {
				padding: 0;
				background: transparent;
				box-shadow: none;
				color: #b4b9be
			}


			.tgsdk-plugin-uninstall-feedback-popup.active {
				display: block;
			}

			tr[data-plugin^="<?php echo esc_attr( $this->product->get_slug() ); ?>"] .deactivate {
				position: relative;
			}

			body.tgsdk-feedback-open .tgsdk-feedback-overlay {
				content: "";
				display: block;
				background-color: rgba(0, 0, 0, 0.5);
				top: 0;
				bottom: 0;
				right: 0;
				left: 0;
				z-index: 10000;
				position: fixed;
			}

			@media (max-width: 768px) {
				.tgsdk-plugin-uninstall-feedback-popup {
					position: fixed;
					max-width: 100%;
					margin: 0 auto;
					left: 50%;
					top: 50px;
					transform: translateX(-50%);
				}

				.tgsdk-plugin-uninstall-feedback-popup .popup--header:before {
					display: none;
				}
			}
		</style>
		<?php
	}

	/**
	 * Theme feedback drawer JS.
	 */
	private function add_theme_feedback_drawer_js() {
		$key = $this->product->get_key();
		?>
		<script type="text/javascript" id="tgsdk-deactivate-js">
			(function ($) {
				$(document).ready(function () {
					setTimeout(function () {
						$('.tgsdk-theme-uninstall-feedback-drawer').addClass('active');
					}, <?php echo absint( self::AUTO_TRIGGER_DEACTIVATE_WINDOW_SECONDS * 1000 ); ?> );

					$('.tgsdk-theme-uninstall-feedback-drawer .toggle').on('click', function (e) {
						e.preventDefault();
						$('.tgsdk-theme-uninstall-feedback-drawer').toggleClass('active');
					});

					$('.info-disclosure-link').on('click', function (e) {
						e.preventDefault();
						$('.info-disclosure-content').toggleClass('active');
					});

					$('.tgsdk-theme-uninstall-feedback-drawer input[type="radio"]').on('change', function () {
						var radio = $(this);
						if (radio.parent().find('textarea').length > 0 &&
							radio.parent().find('textarea').val().length === 0) {
							$('#<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').attr('disabled', 'disabled');
							radio.parent().find('textarea').on('keyup', function (e) {
								if ($(this).val().length === 0) {
									$('#<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').attr('disabled', 'disabled');
								} else {
									$('#<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').removeAttr('disabled');
								}
							});
						} else {
							$('#<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').removeAttr('disabled');
						}
					});

					$('#<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').on('click', function (e) {
						e.preventDefault();
						e.stopPropagation();

						var selectedOption = $(
							'.tgsdk-theme-uninstall-feedback-drawer input[name="tgsdk-deactivate-option"]:checked');
						$.post(ajaxurl, {
							'action': '<?php echo esc_attr( $key ) . '_uninstall_feedback'; ?>',
							'nonce': '<?php echo esc_attr( wp_create_nonce( (string) __CLASS__ ) ); ?>',
							'id': selectedOption.parent().attr('tgsdk-option-id'),
							'msg': selectedOption.parent().find('textarea').val(),
							'type': 'theme',
							'key': '<?php echo esc_attr( $key ); ?>'
						});
						$('.tgsdk-theme-uninstall-feedback-drawer').fadeOut();
					});
				});
			})(jQuery);

		</script>
		<?php
		do_action( $this->product->get_key() . '_uninstall_feedback_after_js' );
	}

	/**
	 * Render the options list.
	 *
	 * @param array $options the options for the feedback form.
	 */
	private function render_options_list( $options ) {
		$key            = $this->product->get_key();
		$inputs_row_map = [
			'text'     => 1,
			'textarea' => 2,
		];
		?>
		<ul class="popup--form">
			<?php
			foreach ( $options as $idx => $attributes ) {
				$title       = Loader::$labels['uninstall']['options'][ $idx ]['title'];
				$placeholder = array_key_exists( 'placeholder', Loader::$labels['uninstall']['options'][ $idx ] ) ? Loader::$labels['uninstall']['options'][ $idx ]['placeholder'] : '';
				?>
				<li tgsdk-option-id="<?php echo esc_attr( $attributes['id'] ); ?>">
					<input type="radio" name="tgsdk-deactivate-option"
							id="<?php echo esc_attr( $key . $attributes['id'] ); ?>">
					<label for="<?php echo esc_attr( $key . $attributes['id'] ); ?>">
						<?php echo esc_attr( str_replace( '{theme}', $this->product->get_name(), $title ) ); ?>
					</label>
					<?php
					if ( array_key_exists( 'type', $attributes ) ) {

						echo '<textarea width="100%" rows="' . esc_attr( $inputs_row_map[ $attributes['type'] ] ) . '" name="comments" placeholder="' . esc_attr( $placeholder ) . '"></textarea>';
					}
					?>
				</li>
			<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Render plugin feedback popup.
	 */
	private function render_plugin_feedback_popup() {
		$button_cancel        = apply_filters( $this->product->get_key() . '_feedback_deactivate_button_cancel', Loader::$labels['uninstall']['button_cancel'] );
		$button_submit        = apply_filters( $this->product->get_key() . '_feedback_deactivate_button_submit', Loader::$labels['uninstall']['button_submit'] );
		$options              = $this->randomize_options( apply_filters( $this->product->get_key() . '_feedback_deactivate_options', $this->options_plugin ) );
		$info_disclosure_link = '<a href="#" class="info-disclosure-link">' . apply_filters( $this->product->get_slug() . '_themeGrill_sdk_info_collect_cta', Loader::$labels['uninstall']['cta_info'] ) . '</a>';

		$options += $this->other;
		?>
		<div class="tgsdk-plugin-uninstall-feedback-popup tgsdk-feedback"
			id="<?php echo esc_attr( $this->product->get_slug() . '_uninstall_feedback_popup' ); ?>">
			<div class="popup--header">
				<h5><?php echo wp_kses( Loader::$labels['uninstall']['heading_plugin'], array( 'span' => true ) ); ?> </h5>
			</div><!--/.popup--header-->
			<div class="popup--body">
				<?php $this->render_options_list( $options ); ?>
			</div><!--/.popup--body-->
			<div class="popup--footer">
				<div class="actions">
					<?php
					echo wp_kses_post( $info_disclosure_link );
					echo wp_kses_post( $this->get_disclosure_labels() );
					echo '<div class="buttons">';
					echo get_submit_button( //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Function internals are escaped.
						$button_cancel,
						'secondary',
						$this->product->get_key() . 'tgsdk-deactivate-no',
						false
					);
					echo get_submit_button( //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, Function internals are escaped.
						$button_submit,
						'primary',
						$this->product->get_key() . 'tgsdk-deactivate-yes',
						false,
						array(
							'data-after-text' => $button_submit,
							'disabled'        => true,
						)
					);
					echo '</div>';
					?>
				</div><!--/.actions-->
			</div><!--/.popup--footer-->
		</div>

		<?php
	}

	/**
	 * Add plugin feedback popup JS
	 */
	private function add_plugin_feedback_popup_js() {
		$popup_id = '#' . $this->product->get_slug() . '_uninstall_feedback_popup';
		$key      = $this->product->get_key();
		?>
		<script type="text/javascript" id="tgsdk-deactivate-js">
			(function ($) {
				$(document).ready(function () {
					var targetElement = 'tr[data-plugin^="<?php echo esc_attr( $this->product->get_slug() ); ?>/"] span.deactivate a';
					var redirectUrl = $(targetElement).attr('href');
					if ($('.tgsdk-feedback-overlay').length === 0) {
						$('body').prepend('<div class="tgsdk-feedback-overlay"></div>');
					}
					$('<?php echo esc_attr( $popup_id ); ?> ').appendTo($(targetElement).parent());

					$(targetElement).on('click', function (e) {
						e.preventDefault();
						$('<?php echo esc_attr( $popup_id ); ?> ').addClass('active');
						$('body').addClass('tgsdk-feedback-open');
						$('.tgsdk-feedback-overlay').on('click', function () {
							$('<?php echo esc_attr( $popup_id ); ?> ').removeClass('active');
							$('body').removeClass('tgsdk-feedback-open');
						});
					});

					$('<?php echo esc_attr( $popup_id ); ?> .info-disclosure-link').on('click', function (e) {
						e.preventDefault();
						$(this).parent().find('.info-disclosure-content').toggleClass('active');
					});

					$('<?php echo esc_attr( $popup_id ); ?> input[type="radio"]').on('change', function () {
						var radio = $(this);
						if (radio.parent().find('textarea').length > 0 &&
							radio.parent().find('textarea').val().length === 0) {
							$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').attr('disabled', 'disabled');
							radio.parent().find('textarea').on('keyup', function (e) {
								if ($(this).val().length === 0) {
									$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').attr('disabled', 'disabled');
								} else {
									$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').removeAttr('disabled');
								}
							});
						} else {
							$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').removeAttr('disabled');
						}
					});

					$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-no').on('click', function (e) {
						e.preventDefault();
						e.stopPropagation();
						$(targetElement).unbind('click');
						$('body').removeClass('tgsdk-feedback-open');
						$('<?php echo esc_attr( $popup_id ); ?>').remove();
						if (redirectUrl !== '') {
							location.href = redirectUrl;
						}
					});

					$('<?php echo esc_attr( $popup_id ); ?> #<?php echo esc_attr( $key ); ?>tgsdk-deactivate-yes').on('click', function (e) {
						e.preventDefault();
						e.stopPropagation();
						$(targetElement).unbind('click');
						var selectedOption = $(
							'<?php echo esc_attr( $popup_id ); ?> input[name="tgsdk-deactivate-option"]:checked');
						var data = {
							'action': '<?php echo esc_attr( $key ) . '_uninstall_feedback'; ?>',
							'nonce': '<?php echo esc_attr( wp_create_nonce( (string) __CLASS__ ) ); ?>',
							'id': selectedOption.parent().attr('tgsdk-option-id'),
							'msg': selectedOption.parent().find('textarea').val(),
							'type': 'plugin',
							'key': '<?php echo esc_attr( $key ); ?>'
						};
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: data,
							complete() {
								$('body').removeClass('tgsdk-feedback-open');
								$('<?php echo esc_attr( $popup_id ); ?>').remove();
								if (redirectUrl !== '') {
									location.href = redirectUrl;
								}
							},
							beforeSend() {
								$('<?php echo esc_attr( $popup_id ); ?>').addClass('sending-feedback');
								$('<?php echo esc_attr( $popup_id ); ?> .popup--footer').remove();
								$('<?php echo esc_attr( $popup_id ); ?> .popup--body').html('<i class="dashicons dashicons-update-alt"></i>');
							}
						});
					});
				});
			})(jQuery);

		</script>
		<?php
		do_action( $this->product->get_key() . '_uninstall_feedback_after_js' );
	}

	/**
	 * Get the disclosure labels markup.
	 *
	 * @return string
	 */
	private function get_disclosure_labels() {
		$disclosure_new_labels = apply_filters( $this->product->get_slug() . '_themeGrill_sdk_disclosure_content_labels', [], $this->product );
		$disclosure_labels     = array_merge(
			[
				'title' => Loader::$labels['uninstall']['disclosure']['title'],
				'items' => [
					sprintf( Loader::$labels['uninstall']['disclosure']['version'], '<strong>', ucwords( $this->product->get_type() ), '</strong>', '<code>', $this->product->get_version(), '</code>' ),
					sprintf( Loader::$labels['uninstall']['disclosure']['website'], '<strong>', '</strong>', '<code>', get_site_url(), '</code>' ),
					sprintf( Loader::$labels['uninstall']['disclosure']['usage'], '<strong>', '</strong>', '<code>', ( time() - $this->product->get_install_time() ), 's</code>' ),
					sprintf( Loader::$labels['uninstall']['disclosure']['reason'], '<strong>', '</strong>', '<i>', '</i>' ),
				],
			],
			$disclosure_new_labels
		);

		$info_disclosure_content = '<div class="info-disclosure-content"><p>' . wp_kses_post( $disclosure_labels['title'] ) . '</p><ul>';
		foreach ( $disclosure_labels['items'] as $disclosure_item ) {
			$info_disclosure_content .= sprintf( '<li>%s</li>', wp_kses_post( $disclosure_item ) );
		}
		$info_disclosure_content .= '</ul></div>';

		return $info_disclosure_content;
	}

	/**
	 * Randomizes the options array.
	 *
	 * @param array $options The options array.
	 */
	public function randomize_options( $options ) {
		$new  = array();
		$keys = array_keys( $options );
		shuffle( $keys );

		foreach ( $keys as $key ) {
			$new[ $key ] = $options[ $key ];
		}

		return $new;
	}

	/**
	 * Called when the deactivate button is clicked.
	 */
	public function post_deactivate() {
		check_ajax_referer( (string) __CLASS__, 'nonce' );

		$this->post_deactivate_or_cancel();

		if ( empty( $_POST['id'] ) ) {

			wp_send_json( [] );

			return;
		}
		$this->call_api(
			array(
				'type'    => 'deactivate',
				'id'      => sanitize_key( $_POST['id'] ),
				'comment' => isset( $_POST['msg'] ) ? sanitize_textarea_field( $_POST['msg'] ) : '',
			)
		);
		wp_send_json( [] );
	}

	/**
	 * Called when the deactivate/cancel button is clicked.
	 */
	private function post_deactivate_or_cancel() {
		if ( ! isset( $_POST['type'] ) || ! isset( $_POST['key'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing, Nonce already present in caller function.
			return;
		}
		if ( 'theme' !== $_POST['type'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing, Nonce already present in caller function.
			return;
		}

		set_transient( 'tg_sdk_pause_' . sanitize_text_field( $_POST['key'] ), true, self::PAUSE_DEACTIVATE_WINDOW_DAYS * DAY_IN_SECONDS );//phpcs:ignore WordPress.Security.NonceVerification.Missing, Nonce already present in caller function.
	}

	/**
	 * Calls the API
	 *
	 * @param array $attributes The attributes of the post body.
	 *
	 * @return bool Is the request successful?
	 */
	protected function call_api( $attributes ) {
		$slug                      = $this->product->get_slug();
		$version                   = $this->product->get_version();
		$attributes['slug']        = $slug;
		$attributes['version']     = $version;
		$attributes['url']         = get_site_url();
		$attributes['active_time'] = ( time() - $this->product->get_install_time() );

		$response = wp_remote_post(
			self::FEEDBACK_ENDPOINT,
			array(
				'body'    => wp_json_encode( $attributes ),
				'headers' => array(
					'Content-Type' => 'application/json',
					'User-Agent'   => 'ThemeGrillSDK',
				),
			)
		);

		return is_wp_error( $response );
	}

	/**
	 * Should we load this object?.
	 *
	 * @param Product $product Product object.
	 *
	 * @return bool Should we load the module?
	 */
	public function can_load( $product ) {
		if ( $this->is_from_partner( $product ) ) {
			return false;
		}
		if ( $product->is_theme() && ( false !== get_transient( 'tg_sdk_pause_' . $product->get_key(), false ) ) ) {
			return false;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}
		global $pagenow;

		if ( ! isset( $pagenow ) || empty( $pagenow ) ) {
			return false;
		}

		if ( $product->is_plugin() && 'plugins.php' !== $pagenow ) {
			return false;

		}
		if ( $product->is_theme() && 'theme-install.php' !== $pagenow ) {
			return false;
		}

		return true;
	}

	/**
	 * Loads module hooks.
	 *
	 * @param Product $product Product details.
	 *
	 * @return Uninstall_Feedback Current module instance.
	 */
	public function load( $product ) {

		if ( apply_filters( $product->get_key() . '_hide_uninstall_feedback', false ) ) {
			return;
		}

		$this->product = $product;
		add_action( 'admin_head', array( $this, 'load_resources' ) );
		add_action( 'wp_ajax_' . $this->product->get_key() . '_uninstall_feedback', array( $this, 'post_deactivate' ) );

		return $this;
	}
}
