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
		$filename = implode(self::FILENAME_SEPARATOR, $categories_array);
		$filename .= self::FILENAME_SEPARATOR . sanitize_title_with_dashes($current_post->post_title);

		if ($filename) {
			$filename .= ".html"; //yes prefix with html
			$this->write_to_static_directory($current_post->post_content, $filename);
		}
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
		$file_contents = $current_page->post_content;

		$this->write_to_static_directory($file_contents, $filename, 'pages');

		//let's generate the TOC for the static directory
		$toc_generator = new MakeItStaticTOC();
		$toc_html = $toc_generator->generate_toc();

		//now write the static contents
		$this->write_to_static_directory($toc_html, 'toc.html', 'pages', false);
	}

	/**
	 * writes physical file into the static directory
	 * this function requires filename as it doesn't construct filenames
	 * @param $content
	 * @param $filename
	 * @param $subdirectory - optional - this is to specify subdirectory after the defined static content FS
	 */
	public function write_to_static_directory($content, $filename, $subdirectory='', $use_nl2br = true) {
		//ok we need to strip the shortcodes!
		$content = str_replace('<p>[static_code]', '', $content); //ok just to be sure we strip a variety with <p> as tiny MCE tends to add this
		$content = str_replace('</p>[static_code]', '', $content);
		$content = str_replace('[static_code]', '', $content);
		$content = str_replace('[/static_code]', '', $content);

		if ($use_nl2br) {
			$content = nl2br($content);
		}
		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		//get the set static directory first
		$static_target_directory = $options["fs_static_directory"];

		$original_paths = explode(";", $options["original_paths"]);
		$target_paths = explode(";", $options["target_paths"]);
		if (count($original_paths) && (count($original_paths) == count($target_paths))) {
			foreach ($original_paths as $key => $original_path) {
				$target_path = $target_paths[$key];
				if ($target_path) {
					//only replace if we have a pair
					$content = str_replace($original_path, $target_path, $content);
				}
			}
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