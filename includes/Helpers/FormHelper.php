<?php

/**
 * Core function for the plugin.
 *
 * @package EverestForms\Helpers
 * @since 3.2.0
 */

namespace EverestForms\Helpers;

/**
 * FormHelper.
 *
 * @since 3.2.0
 */
class FormHelper
{
	/**
	 * Get all the form category list.
	 *
	 * @since 3.2.0
	 * @param string $key The key.
	 */
	public static function get_all_form_tags($key = 'slug')
	{
		$form_tags = get_terms(
			array(
				'taxonomy'   => \EVF_Post_Types::TAGS_TAXONOMY,
				'hide_empty' => false,
			)
		);

		$form_tags    = is_wp_error($form_tags) ? array() : (array) $form_tags;
		$tags_options = wp_list_pluck($form_tags, 'name', $key);

		return $tags_options;
	}

	/**
	 * Particular form tags.
	 *
	 * @since 3.2.0
	 * @param [type] $form_id The form id.
	 * @param string $key The option key type.
	 */
	public static function get_form_tags($form_id, $key = 'term_id')
	{
		$form_tags = wp_get_post_terms(
			$form_id,
			\EVF_Post_Types::TAGS_TAXONOMY,
			true
		);

		$form_tags    = is_wp_error($form_tags) ? array() : (array) $form_tags;
		$tags_options = wp_list_pluck($form_tags, 'name', $key);

		return $tags_options;
	}

	/**
	 * Get all the form tags based on the forms.
	 *
	 * @param [type] $form_ids The form list.
	 */
	public static function get_selected_forms_tags($form_ids)
	{
		$all_tags = array();
		foreach ($form_ids as $form_id) {

			$tags     = self::get_form_tags($form_id);
			$all_tags = $all_tags + $tags;
		}

		return $all_tags;
	}

	/**
	 * Save CleanTalk settings.
	 *
	 * @since 3.3.0
	 *
	 * @param string $access_key The access key.
	 * @return bool
	 */
	public static function evf_save_clean_talk_settings($access_key)
	{
		$clean_talk_request = array(
			'method_name' => 'notice_paid_till',
			'auth_key'    => sanitize_text_field($access_key),
		);

		$response = wp_remote_post(
			'https://api.cleantalk.org/',
			array(
				'body'    => \http_build_query($clean_talk_request, true),
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			)
		);
		$response = json_decode(wp_remote_retrieve_body($response));
		if ($response->data->moderate == 1 && $response->data->valid == 1 && $response->data->product_id == 1) {
			update_option('everest_forms_recaptcha_cleantalk_access_key', sanitize_text_field($access_key));
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check the file type.
	 *
	 * @param [type] $ext The extension.
	 */
	public static function evf_file_upload_check_file_types($ext)
	{
		$supportedFileTypes = array(
			'pdf',
			'doc',
			'xls',
			'ppt',
			'mp3',
			'mp4',
			'zip',
		);
		$newMsFileTypes     = array('docx', 'xlsx', 'pptx');
		$imageFileTypes     = array('jpg', 'jpeg', 'png', 'gif');

		$fileIcon = null;

		if (in_array($ext, $supportedFileTypes)) {
			$fileIcon = $ext;
		} elseif (in_array($ext, $newMsFileTypes)) {
			$fileIcon = substr($ext, 0, -1);
		} elseif (! in_array($ext, $imageFileTypes)) {
			$fileIcon = 'default';
		}

		return $fileIcon;
	}

	/**
	 * Remove the file.
	 *
	 * @param [type] $file_url The file url.
	 */
	public static function remove_file($file_url)
	{
		$uploads       = wp_upload_dir();
		$base_dir      = realpath($uploads['basedir']);
		$path_from_url = wp_parse_url($file_url, PHP_URL_PATH);

		$uploaded_file = $uploads['basedir'] . preg_replace(
			'/.*uploads/',
			'/everest_forms_uploads',
			$path_from_url
		);

		$normalized_path = wp_normalize_path($uploaded_file);
		$resolved_path   = realpath($normalized_path);
		// Validate path is within allowed directory
		if ($resolved_path && strpos($resolved_path, $base_dir) === 0) {
			if (is_file($resolved_path)) {
				wp_delete_file($resolved_path);
			}
		}

		return;
	}
}
