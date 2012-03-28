<?php
/**
 * TOC generator
 * Generates the TOC based on the Wordpress pages. URL is tailored for Luxbet
 *
 * @author: budiartoa
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

class MakeItStaticTOC {

	private $root_parent_page_id = 0;
	private $exclusions = array();
	private $toc_structure = array();
	private $toc_rows = array(); //rows to contain the TOC html
	private $view_dir = array();

	const DIRECTORY_SEPARATOR = "/";
	const TOC_FILENAME = "toc.php";

	public function __construct() {
		$this->view_dir = plugin_dir_path(__FILE__) . "../view/"; //instantiate the view directory for later use
	}

	public function add_exclusions(int $page_id) {
		//TBA
	}

	public function remove_exclusions(int $page_id) {
		//TBA
	}

	public function clear_exclusions() {
		//TBA
	}

	/**
	 * Generates toc as html string
	 * @return string
	 */
	public function generate_toc() {

		$options = get_option(MakeItStatic::CONFIG_TABLE_FIELD);
		//get the web server address first so we know what to use for the link
		$ws_target_address = $options["ws_address"];

		$ws_target_address = rtrim($ws_target_address, "/ "); //trim incase we have the trailing slash

		$toc_link_prefix = "";
		if ($options["toc_link_prefix"]) {
			$toc_link_prefix = $options["toc_link_prefix"];
			$toc_link_prefix = self::DIRECTORY_SEPARATOR . trim($toc_link_prefix, "/ /\n");
		}

		//start building the TOC this is a recursive function that determines the level of each of the links
		$this->build_toc(0, 0, 0);

		$toc_html = "";

		//now generate the static string
		if (count($this->toc_rows)) {
			foreach ($this->toc_rows as &$toc_row) {
				$toc_level = $toc_row['level'];
				if (!$toc_level) { //skip level 0
					continue;
				}
				$title = $toc_row['page_data']->post_title;
				$id = $toc_row['page_data']->ID;
				$link = "";

				//so get the hierarchy and generate the post name here so the front controller can later tokenize the following.
				$page_ancestors = get_ancestors($id, 'page');
				$page_ancestors = array_reverse($page_ancestors);
				foreach ($page_ancestors as $page_ancestor) {
					$current_page_ancestor = get_page($page_ancestor);
					$link .= self::DIRECTORY_SEPARATOR . $current_page_ancestor->post_name;
				}

				$link .= self::DIRECTORY_SEPARATOR . $toc_row['page_data']->post_name . self::DIRECTORY_SEPARATOR;

				$link = $ws_target_address . $toc_link_prefix . $link;
				ob_start();
				include($this->view_dir . 'toc_view.php');
				$contents = ob_get_contents();
				ob_clean();
				$toc_html .= $contents;
			}
		}

		return $toc_html;
	}

	/**
	 * Builds TOC and save the data into toc_rows
	 * This is a recursive function which traverse the page's children starting from parent 0
	 *
	 * @param $parent_id
	 * @param string $page
	 * @param $toc_level
	 * @return mixed
	 */
	private function build_toc($parent_id, $page = '', $toc_level) {
		$this->toc_rows[] = array("level" => $toc_level, "page_data" => $page);

		$all_pages = get_pages( //get pages will return empty array if no child present
			array(
				'child_of' => $parent_id,
				'sort_order' => 'menu_order',
				'depth' => 1, //direct decendents only
				'hierarchical' => false,
				'parent' => $parent_id
			)
		);


		if (!count($all_pages)) {
			return $page->ID;
		}

		foreach ($all_pages as $page) {
			$next_toc_level = $toc_level + 1;
			$this->build_toc($page->ID, $page, $next_toc_level); //recurse for the children
		}

	}

}