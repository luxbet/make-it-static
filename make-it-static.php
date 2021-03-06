<?php
/*
Plugin Name: make-it-static.php
Plugin URI: http://www.luxbet.com/
Description: A plugin to disperse blog posts as static contents to different servers
Version: 1.1
Author: Arvy Budiarto
Author URI: http://www.luxbet.com
*/

/**
 * Class MakeItStatic
 * Mostly contains static functions that calls the appropriate controller when more complex things need to be instantiated
 * User: budiartoa
 * Date: 15/03/12
 * Time: 3:37 PM
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
include_once ("controller/display_options.php");
include_once ("controller/static_generator.php");
include_once ("controller/sidebar_options.php");

class MakeItStatic {
	const CONFIG_TABLE_FIELD = "make_it_static_config";
	const CONFIG_TABLE_FIELD_HTML_LOCK = "make_it_static_config_html_lock";
	const CONFIG_PAGE_NAME = "make_it_static_plugin";
	const ERROR_NO_PERMISSION = 88;
	const ERROR_NO_HANDLE = 89;

	public $error_code = "";

	/**
	 * @var int - variable to save post id before saving, this is required
	 * */
	private $pre_saved_post_id = 0;

	/**
	 * @var string - variable to save the prefix before saving, this is required
	 */
	private $pre_saved_post_prefix = "";

	/**
	 * @var int - variable to save the category of the post before saving
	 */
	private $pre_saved_post_category = 0;

	/**
	 * instantiate all the hooks
	 * not much logic here just the wordpress events hook.
	 */
	public function __construct() {
		register_activation_hook(__FILE__, array($this, 'make_it_static_install'));
		register_deactivation_hook(__FILE__, array($this, 'make_it_static_uninstall'));

		//only generate this if this is admin area
		if (is_admin()) {
			add_action('admin_menu', array($this, 'generate_admin_menu'));
		}

		//now, we have to tell Wordpress to actually static this properly
		//add the hook to post save!
		//add_action('publish_post', array($this, 'convert_post_to_static'));
		//add_action('publish_page', array($this, 'convert_page_to_static'));
		add_action( 'save_post', array($this, 'convert_post_to_static_array') );

		//add filter hook for before saving the page, we need to delete the static files in case use changed the category
		//wp_insert_post_data
		add_filter('wp_insert_post_data', array($this, 'save_current_post_data'), '99', 2);

		//to clean up previous category after inserion
		//add_action('wp_insert_post', array($this, 'clean_previous_category'));

		//append the after-save messages list with our own error messages
		add_filter('post_updated_messages', array($this, 'custom_message_filter'));

		remove_filter('the_content', 'wpautop');
		remove_filter('the_content', 'wptexturize');

		add_filter('the_content', array($this, 'preserve_codes'), 99);

		//we need an add_action for NGGallery so we can sync static servers
		add_action('ngg_added_new_image', array($this, 'nggallery_image_upload_hook'));

		add_action('admin_init', array($this, 'add_html_lock' ));

		//we need to hide quick edit as we don't support this
		add_filter('post_row_actions', array($this, 'hide_quick_edit'), 10, 1);
		add_filter('page_row_actions', array($this, 'hide_quick_edit'), 10, 1);

		//apply the filter for shortlinks to help the user understand where they should look
		//only if using categories as path is enabled
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		$categories_as_path = $options["categories_as_path"];
		if ($categories_as_path == 'y') {
			add_filter('get_sample_permalink_html', array($this, 'modify_shortlinks'), 11, 4);
		}

		//remove meta boxes as we don't need this
		add_action('admin_head', array($this, 'remove_unsupported_meta_boxes'));

		//disable autosave as we don't really need this with static contents
		add_action('wp_print_scripts', function(){
			wp_deregister_script('autosave');
		});

		//register our quick tags
		add_action('admin_print_footer_scripts',  array($this, 'register_quicktags'));

		//add filter to save our html lock
		add_filter('wp_insert_post_data', array($this, 'save_html_lock_state'), '99', 2);

		//to be save, default editor should be html
		add_filter('wp_default_editor', function() {
			return 'html';
		});
	}

	/**
	 * Saves the current's post prefix and category id before any database update
	 * This is used to clean up the current directory, in case the post is trashed or moved to another directory
	 * @param $data
	 * @param $post_arr
	 * @return mixed
	 */
	public function save_current_post_data($data, $post_arr) {
		$static_generator = new StaticGenerator();

		$this->pre_saved_post_prefix = $static_generator->generate_post_prefix($post_arr["ID"]);

		//we also need to get the category to construct the filename
		$current_post_categories = get_the_category($post_arr["ID"]);

		//now, we assume the one category restriction is on
		$current_post_category = $current_post_categories[0];

		$this->pre_saved_post_category = $current_post_category->cat_ID;

		//save current post id, Arthur Kusumadjaja, 22 September 2014
		$this->pre_saved_post_id = $post_arr['ID'];
		$static_generator->clean_up_static_posts($this->pre_saved_post_id);

		return $data;
	}

	/**
	 * Clean up the previous category based on the last saved post prefix and post category
	 */
	public function clean_previous_category() {
		//now clean up the category
		$static_generator = new StaticGenerator();
		$static_generator->clean_up_static_post_category(false, $this->pre_saved_post_prefix, $this->pre_saved_post_category);
	}

	/**
	 * Save the locked stathe of the html editor
	 * @param $data
	 * @param $postarr
	 * @return mixed
	 */
	public function save_html_lock_state($data, $postarr) {
		$static_generator = new StaticGenerator();
		$static_generator->save_html_lock_state($postarr['ID']);
		return $data;
	}

	/**
	 * An option to lock HTML post since switching back to visual editor strips out a lot of HTML
	 * This is to avoid user switching back and forth by accident
	 */
	public function add_html_lock($post_object) {

		$post_id = null;

		//we need to make sure we save the state of the editor too
		if ($post_object) {
			$post_id = $post_object->ID;
		}

		//make sure we know what post is this
		$display_sidebar_options = new MakeItStaticSidebar();
		if ($post_id) {
			$display_sidebar_options->set_post_id($post_id);
		}

		//trigger the filters
		$display_sidebar_options->setup_filters($post_id);

		add_meta_box(
			'make_it_static_html_toggle',
			__( 'Lock HTML Editor', 'make_it_static_html_toggle' ),
			function($post_object) {
				$post_id = $post_object->ID;
				$display_sidebar_options = new MakeItStaticSidebar();

				if ($post_id) {
					$display_sidebar_options->set_post_id($post_id);
				}

				$display_sidebar_options->display_html_lock();
			},
			'post',
			'side',
			'high'
		);
	}

	/**
	 * Modifies the displayed shortlinks so it now shows the expected make-it-static display
	 * @param $return
	 * @param $id
	 * @return string
	 */
	public function modify_shortlinks($return, $id) {
		$static_generator = new StaticGenerator();
		$shortlink = $static_generator->generate_shortlink($id);
		return "<a href='$shortlink' target='new_wordpress_preview_window'>$shortlink</a>";
	}

	/**
	 * @static
	 * creates an option entry in wordpress
	 */
	public static function make_it_static_install() {
		add_option(self::CONFIG_TABLE_FIELD);
		add_option(self::CONFIG_TABLE_FIELD_HTML_LOCK);
	}

	/**
	 * @static
	 * remove the option entry from wordpress
	 */
	public static function make_it_static_uninstall() {
		//delete_option(self::CONFIG_TABLE_FIELD);
	}

	/**
	 * @static
	 * add the settings menu options so admin can setup the static directories
	 */
	public function generate_admin_menu() {
		//instantiate the option display controller
		$make_it_static_display_options = new MakeItStaticDisplayOptions('make_it_static_options_group');

		//upon clicking the settings menu, the callback display_options from make it static controller will be fired
		add_options_page("Make-it-static!", "Make-it-static", "administrator", "make-it-static", array($make_it_static_display_options, 'display_options'));
	}

	/**
	 * Convert the new post data display into static physical file
	 * This is to be called as a hook
	 * @param $post_id
	 */
	public function convert_post_to_static($post_id) {
		$static_generator = new StaticGenerator();
		$static_generator->generate_static_post($post_id);
		$error_code = $static_generator->get_error_code();

		if ($error_code) {
			$this->error_code = $error_code;
			add_filter('redirect_post_location', array($this, 'custom_error_message_redirect'));
		}

	}

	/**
	 * Convert new post data display into static physical file based on selected categories
	 * @param $post_id
	 * Arthur Kusumadjaja, 23 September 2014
	 */
	public function convert_post_to_static_array($post_id) {
		$static_generator = new StaticGenerator();

		// generate static files
		$static_generator->generate_files($post_id);

		$error_code = $static_generator->get_error_code();

		if ($error_code) {
			$this->error_code = $error_code;
			add_filter('redirect_post_location', array($this, 'custom_error_message_redirect'));
		}
	}

	/**
	 * Converts the page data into static physical file
	 * This is to be called as a hook
	 * @param $page_id
	 */
	public function convert_page_to_static($page_id) {
		$static_generator = new StaticGenerator();
		$static_generator->generate_static_page($page_id);
		$error_code = $static_generator->get_error_code();

		if ($error_code) {
			$this->error_code = $error_code;
			add_filter('redirect_post_location', array($this, 'custom_error_message_redirect'));
		}
	}

	/**
	 * Override default wordpress redirection based on our error code
	 * @param $location
	 * @return string
	 */
	public function custom_error_message_redirect($location) {

		remove_filter('redirect_post_location', __FUNCTION__, $this->error_code);
		$location = add_query_arg('message', $this->error_code, $location);
		return $location;
	}

	/**
	 * This function defines our custom error messages for the makeit static
	 * @param $messages
	 * @return
	 */
	public function custom_message_filter($messages) {
		$messages['post'][self::ERROR_NO_PERMISSION] = 'Changes saved to database, however file cannot be generated, make sure you set the correct permission for static directory, check the make-it-static options';
		$messages['post'][self::ERROR_NO_HANDLE] = 'Changes saved to database, however file handle cannot be created,  make sure you set the correct permission for static directory, check the make-it-static options';

		$messages['page'][self::ERROR_NO_PERMISSION] = 'Changes saved to database, however file cannot be generated, make sure you set the correct permission for static directory, check the make-it-static options';
		$messages['page'][self::ERROR_NO_HANDLE] = 'Changes saved to database, however file handle cannot be created,  make sure you set the correct permission for static directory, check the make-it-static options';

		return $messages;
	}

	/**
	 * prevent escaping any characters within this raw code
	 * @param $content
	 * @return string
	 */
	public function preserve_codes($content) {
		$new_content = '';
		$pattern_full = '{(\[static_code\].*?\[/static_code\])}is';
		$pattern_contents = '{\[static_code\](.*?)\[/static_code\]}is';
		$pieces = preg_split($pattern_full, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($pieces as $piece) {
			if (preg_match($pattern_contents, $piece, $matches)) {
				$new_content .= html_entity_decode($matches[1]);
			} else {
				$new_content .= wptexturize(wpautop($piece));
			}
		}

		return $new_content;
	}

	/**
	 * call the callback after nggallery image upload
	 * @param $image_info
	 */
	public function nggallery_image_upload_hook($image_info) {
		//get the gallery path gallerypath
		$nggdb = new nggdb();
		$current_gallery = $nggdb->find_gallery($image_info['galleryID']);
		$current_filename = $image_info["filename"];
		//now get the absolute path
		$ws_image_path = $options = get_option('siteurl') . "/" . $current_gallery->path . "/" . $current_filename;

		$static_generator = new StaticGenerator();
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		$callback_urls = $options["nggallery_callback_url"];
		$static_generator->callback_file($ws_image_path, $callback_urls, $current_filename, 'nggallery_image');
	}

	/**
	 * remove quick edit link as we don't support this
	 * @param $actions
	 * @return mixed
	 */
	public function hide_quick_edit($actions) {
		unset($actions['inline hide-if-no-js']);
		unset($actions['view']);
		unset($actions['edit-slug-box']);
		return $actions;
	}


	/**
	 * remove unsupported meta boxes since make-it-static doesn't use this
	 * but only do this if this is configured in the plugin settings
	 */
	public function remove_unsupported_meta_boxes() {
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		$disable_meta_boxes = $options["disable_unsupported_meta_boxes"];

		if ($disable_meta_boxes == "y") {
			$meta_boxes_to_disable = array(
				'commentstatusdiv',
				'commentsdiv',
				'slugdiv',
				'trackbacksdiv',
				'postcustom',
				'postexcerpt',
				'tagsdiv-post_tag',
				'postimagediv',
				'formatdiv'
			);

			foreach ($meta_boxes_to_disable as $meta_box_name) {
				remove_meta_box($meta_box_name,'post','normal');
				remove_meta_box($meta_box_name,'page','normal');
				remove_meta_box($meta_box_name,'post','side');
				remove_meta_box($meta_box_name,'page','side');
				remove_meta_box($meta_box_name,'post','advanced');
				remove_meta_box($meta_box_name,'page','advanced');
			}
		}
	}

	/**
	 * create shortcode buttons because we don't want to remember the shortcodes...
	 */
	public function register_quicktags() {
		echo "
			<script type='text/javascript'>
				 QTags.addButton( 'static_code', 'Static Code', '[static_code]', '[/static_code]' );
				 QTags.addButton( 'decendents_toc', 'Save as decendents TOC', '[TOC]', '' );

				 jQuery(document).ready(function() {
						jQuery('#qt_content_decendents_toc').live('click', function() {
							alert('If [TOC] tag is inserted, the static generated content of this page will ONLY contain ' +
								 'the table of contents for the children of the current page. ' +
								   '\\n\\nNOTE: this only works for pages DO NOT INSERT for posts')
				 		}
				 	);
				 });
			</script>
		";
	}
}

$make_it_static = new MakeItStatic();

?>