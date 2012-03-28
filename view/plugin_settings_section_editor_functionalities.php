<?php
/**
 * Generic instruction for the callback setup
 * @author: budiartoa
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
?>

<p>
	Make it static alse features some enhancements to the editor
	<ol>
		<li>
			<strong>Ability to inject raw html codes such as scripts <span style="color:red">Use with caution</span></strong><br />
			Putting any code between shortcode <strong>[static_code] [/static_code]</strong> will cause the section to be saved unescaped hence custom css and js are possible
		</li>
		<li>
			<strong><span style="color:red">[TOC]</span></strong><br />
			Putting this string <strong>[TOC]</strong> anywhere in the editor when authoring a page will replace the contents of the generated static files with the table of contents of items below the current page
		</li>
	</ol>
</p>