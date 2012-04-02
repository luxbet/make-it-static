<?php
/**
 * MakeItStaticSidebar
 * This is the controller to prevent switching back and forth when html editor is being locked
 * @author: budiartoa
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

class MakeItStaticSidebar {

	private $view_dir;
	private $post_id = NULL;

	/**
	 * Main constructor
	 * this instantiate the default view directory
	 */
	public function __construct() {
		$this->view_dir = plugin_dir_path(__FILE__) . "../view/"; //instantiate the view directory for later use
	}

	/**
	 * sets the post id for processing
	 * @param $post_id
	 */
	public function set_post_id($post_id) {
		$this->post_id = $post_id;
	}

	/**
	 * displays the lock dropdown as well as the javascript for this matter
	 */
	public function display_html_lock() {
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD_HTML_LOCK);

		//this variable will be used by the view file
		$default_html_lock = false;
		if ($options && $options[$this->post_id] == '1') {
			$default_html_lock = true;
		}

		include_once($this->view_dir . "editor_settings_html_lock.php");
	}

	/**
	 * setup default filter based on the post id, this will trigger the hook to save html lock options
	 * @param string $post_id
	 */
	public function setup_filters($post_id = '') {
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD_HTML_LOCK);
		$default_lock = false;
		if ($post_id && $options && $options[$post_id]) {
			add_filter( 'wp_default_editor', create_function('', 'return "html";') );
		}
	}

}