<?php
/**
 * @author: budiartoa
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
class MakeItStaticDisplayOptions {
	private $view_dir;
	private $settings_group_name;

	public function __construct($settings_group_name) {
		$this->view_dir = plugin_dir_path(__FILE__) . "../view/"; //instantiate the view directory for later use
		$this->settings_group_name = $settings_group_name;

		//init all the requried fields here we need to do this as a hook after the admin finished initializing
		add_action('admin_init', array($this, 'display_options_init'));
	}

	public function display_options_init() {
		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_directory",
			"Main static directory setup",
			"display_options_section_directory",
			"make_it_static_fs_directory",
			"fs_static_directory",
			"Physical static directory"
		);


		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_webserver",
			"Main static web server address",
			"display_options_section_webserver",
			"make_it_static_ws_directory",
			"ws_address",
			"Web server address (customer facing address)"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_webserver",
			"Main static web server address",
			"display_options_section_webserver",
			"make_it_static_ws_url",
			"ws_static_url",
			"Static file URL (web reachable url)"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_toc",
			"Pages TOC (Table of Contents) setup",
			"display_options_section_toc",
			"make_it_static_toc_link_prefix",
			"toc_link_prefix",
			"TOC link prefix"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_path_replacement",
			"Path Replacements",
			"display_options_section_path",
			"make_it_static_original_path",
			"original_paths",
			"Original paths <br />(separate with newline)",
			"textarea"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_path_replacement",
			"Path Replacements",
			"display_options_section_path",
			"make_it_static_target_path",
			"target_paths",
			"Target paths <br />(separate with newline;)",
			"textarea"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_callback",
			"After Creation Callback URL",
			"display_options_section_callback",
			"make_it_static_callback_url",
			"callback_url",
			"Callback URL",
			"textarea"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_nggallery_callback",
			"NgGallery After Upload Image Callback",
			"display_options_section_nggallery_callback",
			"make_it_static_nggallery_callback_url",
			"nggallery_callback_url",
			"Next Gen Gallery Callback URL",
			"textarea"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_admin_meta_boxes",
			"Disable unsupported admin meta boxes",
			"display_options_section_meta_boxes",
			"disable_unsupported_meta_boxes",
			"disable_unsupported_meta_boxes",
			"",
			"yes_no"
		);

		$this->create_text_option(
 			"dummy_validation_function",
			"make_it_static_section_permalinks",
			"Use categories as permalinks",
			"display_options_section_meta_boxes",
			"categories_as_path",
			"categories_as_path",
			"",
			"yes_no"
		);

		$this->create_text_option(
			"dummy_validation_function",
			"make_it_static_section_msg_editor",
			"Additional editor functionalities",
			"display_options_section_editor_functionalities",
			false,
			"",
			""
		);

		$this->create_text_option(
			"remove_orphaned_static_files",
			"make_it_static_section_maintenance",
			"Remove orphaned static files",
			"display_options_section_maintenance",
			"remove_orphaned_static_files",
			"remove_orphaned_static_files",
			"Removed orphaned static files",
			"yes_no"
		);
	}

	/**
	 * creates text option box or just generic messaage
	 * @param $validation_callback
	 * @param $section_id
	 * @param $section_title
	 * @param $section_description_callback
	 * @param $current_settings_field_id
	 * @param $current_settings_field_name
	 * @param $title
	 * @param $type - input or textarea
	 */
	public function create_text_option($validation_callback, $section_id, $section_title, $section_description_callback, $current_settings_field_id,  $current_settings_field_name, $title, $type='input') {
		//before we begin we need to register the settings, the option name is the table field, wordpress save it this way
		//since we want to save all the options in json format in one field, we constant this
		register_setting($this->settings_group_name, MakeItStatic::CONFIG_TABLE_FIELD, array($this, $validation_callback));

		//this settings section call back the display controller's display_options_section_directory function which calls the appropriate view
		add_settings_section($section_id, $section_title, array($this, $section_description_callback), 'make_it_static_plugin');

		//setup the actual input field, this is for the static file system directory in the publishing server
		//if no input field then just display the message
		if ($current_settings_field_id) {
			add_settings_field($current_settings_field_id, $title, array($this,'display_input_field'), 'make_it_static_plugin', $section_id, array("field_name" => $current_settings_field_name, "field_size" => $current_settings_field_id, "field_size" => 80, "type" => $type));
		}
	}

	/**
	 * displays option description
	 */
	public function display_options() {
		//include the template file
		$settings_group_name = $this->settings_group_name;
		include_once($this->view_dir . "plugin_settings.php");
	}

	/**
	 * directory section information
	 */
	public function display_options_section_directory() {
		include_once($this->view_dir . "plugin_settings_section_directory.php");
	}

	/**
	 * webserver section information
	 */
	public function display_options_section_webserver() {
		include_once($this->view_dir . "plugin_settings_section_webserver.php");
	}

	/**
	 * table of content section information
	 */
	public function display_options_section_toc() {
		include_once($this->view_dir . "plugin_settings_section_toc.php");
	}

	/**
	 * path replacements information
	 */
	public function display_options_section_path() {
		include_once($this->view_dir . "plugin_settings_section_path.php");
	}

	/**
	 * callbacks information
	 */
	public function display_options_section_callback() {
		include_once($this->view_dir . "plugin_settings_section_callback.php");
	}

	/**
	 * information about extra wysiwyg information
	 */
	public function display_options_section_editor_functionalities() {
		include_once($this->view_dir . "plugin_settings_section_editor_functionalities.php");
	}

	/**
	 * information about extra wysiwyg information
	 */
	public function display_options_section_maintenance() {
		include_once($this->view_dir . "plugin_settings_section_maintenance.php");
	}

	/**
	 * information on nggallery callback feature
	 */
	public function display_options_section_nggallery_callback() {
		include_once($this->view_dir . "plugin_settings_section_nggallery_callback.php");
	}

	/**
	 * Displays the input field taking into the field argument as an associative array of
	 * field_id
	 * field_name
	 * field_size
	 * type - either input or textarea
	 * @param array $field_args
	 */
	public function display_input_field($field_args) {

		$current_settings_field_id = $field_args["field_id"];
		$current_settings_field_name = $field_args["field_name"];
		$current_settings_field_size = $field_args["field_size"];
		$current_settings_type = $field_args["type"];
		include($this->view_dir . "plugin_settings_input_field.php");
	}

	/**
	 * callbacks information
	 */
	public function display_options_section_meta_boxes() {
		return;
	}

	/**
	 * Planned input validation. empty for now.
	 * This is so that we have a callback for the options and later on when we want to
	 * add proper validations we will be able to do it quite easily in here
	 * @param $input
	 * @return mixed
	 */
	public function dummy_validation_function($input) {
		return $input;
	}

	/**
	 * so when this si called this will remove all the static posts that doesn't exist anymore
	 * ALSO this will reset the options to no again
	 * @param $input
	 * @return mixed
	 */
	public function remove_orphaned_static_files($input) {

		if ($input['remove_orphaned_static_files'] == 'y') {
			//clean up stuff
			include_once('static_generator.php');
			$static_generator_controller = new StaticGenerator();
			$static_generator_controller->clean_up_static_files(0, "posts", "");

			$input['remove_orphaned_static_files'] = 'n'; //reset to no
		}

		return $input;
	}
}