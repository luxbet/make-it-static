<?php
/**
 * Generic instruction for the callback setup
 * User: budiartoa
 * Date: 16/03/12
 * Time: 11:45 AM
 * @copyright Copyright © Luxbet Pty Ltd. All rights reserved. http://www.luxbet.com/
 * @license http://www.opensource.org/licenses/BSD-3-Clause
 */
?>

<p>
	The URL that the plugin will call via CURL after all the static creations are done.
	<strong>Leave blank for none </strong>
</p>
<p>Separate with semicolon ";" to specify more than one address</p>
<p>This will cause the plugin to submit a POST request to the address</p>
<p>The following information will be sent as POST values
<ol>
	<li>filename => complete filename of the static file</li>
	<li>url => url to reach the static file as stated on the web server address config</li>
	<li>page_type => type of the page whether it's a post or page</li>
</ol>
</p>