<?php
/**
 * plugin_settings.php
 * This file contains mostly the HTML to display the plugin settings.
 * All variables to be displayed should be defined in the main MakeItStatic class
 *
 * @author: budiartoa
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
?>

<script type="text/javascript">
	//set the default lock state
	var default_html_lock = <?php echo $default_html_lock == true ? 'true' : 'false';?>;
	if (default_html_lock) {
		jQuery(document).ready(function() {
			//remove the default onClick so that visual editor can't be triggered at all!
			jQuery('#content-tmce').removeAttr("onClick");
			jQuery('#content-tmce').click(function() {
				alert("HTML source editor lock is active, visual editing is not possible. " +
					"\n\nWARNING: switching to visual editing will truncate HTML tags " +
					"\n\nResave with HTML lock set to No to enable visual editing");
			});
		});
	}
</script>

<select name="make_it_static_html_lock">
	<option value="1" <?php echo $default_html_lock == true ? 'selected=selected' : '';?>>Yes</option>
	<option value="0" <?php echo $default_html_lock == false ? 'selected=selected' : '';?>>No</option>
</select>