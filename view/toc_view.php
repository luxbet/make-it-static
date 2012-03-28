<?php
/**
 * View file for TOC rows. The indentation is to be done via CSS instead of hardcoded
 * This is to be included by TOC generator controller to provide the HTML of each of the rows.
 *
 * The idea is when style changes needs to be applied, we can do it here without modifying the logics too much
 * @author: budiartoa
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */

?>

<div class="toc">
	<span class="toc level_<?=$toc_level?> path<?=$current_path?>" rel="children_of<?=$parent_path?>">
		<a href="<?=$link?>"><?=$title?></a>
	</span>
</div>