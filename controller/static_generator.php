<?php
/**
 * @author: budiartoa
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

include_once('toc_generator.php');

class StaticGenerator {

	const FILENAME_SEPARATOR = "#@#"; //hopefully #@#is unique enough that no one would use this as the post name, we need this later for Wagerplayer to derrive the file from the URL

	private $error_code = false; //collection of WP_Error objects

	public function __construct() {

	}

	/**
	 * returns the error code of the last error encountered
	 * @return bool
	 */
	public function get_error_code() {
		if (!$this->error_code) {
			return false;
		}
		return $this->error_code;
	}

	/**
	 * Save the error code
	 * @param $error
	 */
	private function register_error($error) {
		$this->error_code = $error;
	}

	/**
	 * Generates static post
	 * This turns post data based on the post_id into physical file
	 * The file name generation also take into account the post category hierarchy, using one-category only plugin
	 * we are able to ensure that the first category is indeed the youngest child of the categories
	 * @param $post_id
	 */
	public function generate_static_post($post_id) {
		$current_post = get_post($post_id);

		$filename_prefix = $this->generate_post_prefix($post_id);

		$filename = $filename_prefix . self::FILENAME_SEPARATOR . sanitize_title_with_dashes($current_post->post_title);

		if ($filename) {
			$filename .= ".html"; //yes prefix with html
			$this->write_to_static_directory($current_post->post_content, $filename, '', true);
		}

		//yes also save the state for the editor, so we know which editor to kick in
		$make_it_static_html_lock = $_POST["make_it_static_html_lock"];
		$lock_options = get_option(MakeItStatic::CONFIG_TABLE_FIELD_HTML_LOCK);
		$lock_options[$post_id]  = $make_it_static_html_lock;
		update_option(MakeItStatic::CONFIG_TABLE_FIELD_HTML_LOCK, $lock_options);

		//we also need to get the category to construct the filename
		$current_post_categories = get_the_category($post_id);

		//now, we assume the one category restriction is on
		$current_post_category = $current_post_categories[0];
		$this->clean_up_static_files($current_post_category->cat_ID, "posts", $filename_prefix);
	}

	/**
	 * Create static file of the page with the page id
	 * This also rebuilds the static index file
	 * @param $page_id
	 */
	public function generate_static_page($page_id) {
		$current_page = get_page($page_id);
		$page_ancestors = get_ancestors($page_id, 'page');

		$filename = "";
		$page_ancestors = array_reverse($page_ancestors); //reverse this as we want the parent to children
		//so get the hierarchy and generate the post name here so the front controller can later tokenize the following.
		foreach ($page_ancestors as $page_ancestor) {
			$current_page_ancestor = get_page($page_ancestor);
			$filename .= $current_page_ancestor->post_name . self::FILENAME_SEPARATOR;
		}

		$filename .= $current_page->post_name . ".html";
		//append post header as h1
		$file_contents = "<h1>" . $current_page->post_title . "</h1>";

		//let's generate the TOC for the static directory
		$toc_generator = new MakeItStaticTOC();

		//if the page content has TOC (starting from that page) then display the toc instead of blank page
		//ok update this so that TOC can be replaced by the table of content instead of killing the entire page contents
		$file_contents .= $current_page->post_content;
		if (substr_count($current_page->post_content, "[TOC]")) {
			//we actually need to convert the file contents with nl2br but we don't need this for the TOC
			$file_contents = str_replace("[TOC]", $toc_generator->generate_toc($page_id), nl2br($file_contents));
			$nl2br  = false;
		} else {
			$nl2br  = true;
		}

		$this->write_to_static_directory($file_contents, $filename, 'pages', $nl2br);

		$toc_generator = new MakeItStaticTOC();
		$toc_html = $toc_generator->generate_toc();

		//now write the static contents
		$this->write_to_static_directory($toc_html, 'toc.html', 'pages', false);
	}

	/**
	 * creates post prefix string based on the category names
	 * @param $post_id
	 * @return string
	 */
	public function generate_post_prefix($post_id) {
		//we also need to get the category to construct the filename
		$current_post_categories = get_the_category($post_id);

		//now, we assume the one category restriction is on
		$current_post_category = $current_post_categories[0];

		//now get the parents
		$current_category_path = get_category_parents($current_post_category->cat_ID, false, self::FILENAME_SEPARATOR, true);

		//we need to reverse this as wordpress
		$categories_array = explode(self::FILENAME_SEPARATOR, $current_category_path);

		//unset the last element as we don't need this
		unset($categories_array[count($categories_array) - 1]);

		//now we generate the file name
		$filename_prefix = implode(self::FILENAME_SEPARATOR, $categories_array);

		return $filename_prefix;
	}

	/**
	 * @param $current_parent_id
	 * @param $type - page or post
	 * @param $filename_prefix - prefix of the filename which is the parent directories concatenated saves us looping again
	 * @param string $subdirectory - optional
	 */
	public function clean_up_static_files($current_parent_id, $type, $filename_prefix, $subdirectory = "") {
		//need to clean up the directory after writing static contents
		$regenerate_prefix = false;
		if ($filename_prefix == "") {
			//empty we must generate prefix for each of the posts, this also means that
			//this is a maintenance run which scans all dir. This won't run per post
			$regenerate_prefix = true;
		}
		$expected_filenames = array();
		if ($type == "posts") {
			//get all the posts
			if ($current_parent_id) {
				$all_posts_in_category = get_posts(array("category" => $current_parent_id, 'numberposts' => -1));
			} else {
				$all_posts_in_category = get_posts(array('numberposts' => -1));
			}

			foreach ($all_posts_in_category as $post_in_category) {
				if ($regenerate_prefix) {
					$current_filename_prefix = $this->generate_post_prefix($post_in_category->ID);
				} else {
					$current_filename_prefix = $filename_prefix;
				}
				$filename = $current_filename_prefix . self::FILENAME_SEPARATOR . sanitize_title_with_dashes($post_in_category->post_title);
				$expected_filenames[] = $filename . ".html";
			}
		} else { //pages
			//TODO: we need to handle pages too, but at this point this is not required yet
		}

		if (!count($expected_filenames)) {
			return false; //no expected filenames
		}

		//let's get the current expected files from the

		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		$static_target_directory = $options["fs_static_directory"];

		if ($subdirectory) {
			$static_target_directory .= $subdirectory . "/";
		}

		$directory_handle = opendir($static_target_directory);

		while (false !== ($current_filename = readdir($directory_handle))) {

			if ($current_filename != "." && $current_filename != ".." ) {

				//if we specify prefix and it doesn't match with our current item, don't do anything
				if ($filename_prefix && !substr_count($current_filename, $filename_prefix)) {
					continue;
				}

				if (!in_array($current_filename, $expected_filenames)) {
					//not in array therefore we must remove!!!
					unlink($static_target_directory . $current_filename);
				}
			}
		}
	}

	/**
	 * writes physical file into the static directory
	 * this function requires filename as it doesn't construct filenames
	 * @param $content
	 * @param $filename
	 * @param $subdirectory - optional - this is to specify subdirectory after the defined static content FS
	 * @param $use_nl2br - optional
	 */
	public function write_to_static_directory($content, $filename, $subdirectory='', $use_nl2br = true) {
		//we need to strip the shortcodes!
		//ok now we need to avoid things inside static codes from being nl2br'ed
		//this means extracting those inside the static code shortcode and putting it back after nl2br
		$regex_rule = "/\[static_code\](.+?)\[\/static_code\]/is";
		preg_match_all($regex_rule, $content, $matches);
		$matches_with_tag = $matches[0];
		$matches_content = $matches[1];

		$placeholder_array = array();
		$match_count = 0;
		if (count($matches_with_tag)) {
			foreach ($matches_with_tag as $match_with_tag) {
				$placeholder_array[] = "__placeholder_$match_count";
				$match_count++;
			}

			$content = str_replace($matches_with_tag, $placeholder_array, $content);
		}

		//conver newlines to br
		if ($use_nl2br) {
			$content = nl2br($content);
		}

		//now put the escaped contents back
		$match_count = 0;
		if (count($matches_with_tag)) {

			$content = str_replace($placeholder_array, $matches_content, $content);

		}

		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		//get the set static directory first
		$static_target_directory = $options["fs_static_directory"];

		$original_paths = preg_split("/[\r\n]+/", $options["original_paths"], -1, PREG_SPLIT_NO_EMPTY);
		$target_paths = preg_split("/[\r\n]+/", $options["target_paths"], -1, PREG_SPLIT_NO_EMPTY);
		if (count($original_paths) && (count($original_paths) == count($target_paths))) {

			$content = str_replace($original_paths, $target_paths, $content);
		}

		$target_fs_filename = $static_target_directory . $filename;
		$ws_filepath = $options["ws_static_url"] . $filename;//the web accessible path

		$content_type = 'post';
		if ($subdirectory) {
			$content_type = 'page';
			//redefine the fs filename as we need to insert the subdirectory
			$target_fs_filename = $static_target_directory . $subdirectory . "/";
			if (!is_dir($target_fs_filename)) {
				//create the directory
				mkdir($target_fs_filename);
			}
			$target_fs_filename .= $filename; //complete the path with filename as we want to use this of writing
			$ws_filepath = $options["ws_static_url"] . $subdirectory . "/" . $filename;//the web accessible path
		}


		//now attempt to write into the static directory

		$file_handle = fopen($target_fs_filename, 'w');

		if (!$file_handle) {
			$this->register_error(MakeItStatic::ERROR_NO_HANDLE);
		}

		if (fwrite($file_handle, $content) === FALSE) {
			///return error code here
			$this->register_error(MakeItStatic::ERROR_NO_PERMISSION);
		}

		$callback_urls = $options["callback_url"];
		//now we need to do the callback!
		//we assume that the subdirectory is different content type as this is what we use to seggregate pages and posts
		$this->callback_file($ws_filepath, $callback_urls, $filename, $content_type);


	}

	public function get_post_language() {

	}

	/**
	 * Takes care of the callback via curl
	 * by default it submits the file_url, filename, contetn_type
	 * @param $ws_filepath
	 * @param $callback_urls
	 * @param $filename
	 * @param $content_type
	 */
	public function callback_file($ws_filepath, $callback_urls, $filename, $content_type) {
		if ($callback_urls) {
			$callback_urls_array = explode(";", $callback_urls);

			foreach ($callback_urls_array as $callback_url) {
				$callback_url = trim($callback_url);
				$curl_connection = curl_init($callback_url);
				curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl_connection, CURLOPT_POSTFIELDS, "file_url=$ws_filepath&filename=$filename&content_type=$content_type");
				curl_exec($curl_connection);
				curl_close($curl_connection);
			}
		}
	}

}
?>