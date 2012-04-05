Make-it-static, a Wordpress plugin
==================================

This plugin is developed so that we can create static HTML version of wordpress posts.

The usage for this plugin is mainly for sites with high traffic number and multiple servers. Having fully static HTML files as oppose to hitting database or hitting cache server should in theory be faster. When installed, when saving a post this plugin will create a physical file in the server directory as configured in the settings.

Features
--------
- Save posts as static HTML files
- Save pages as static HTML files
- Generate an accompanying full table of contents static page when saving pages. Each link levels will have different css classes, useful for styling and javascript usages
- Ability to lock HTML editor, so that the visual editor doesn't remove the post's html markup when switching the tab by accident
- Insert raw html including javascripts (use with caution) without wordpress stripping the tags through the use of [static_code] shortcode. The button will be visible under HTML editor mode.
- Convert any post into Table of content by using [TOC] string, this is also available as a button under HTML editor mode. When a page has this TOC, the static file generated will contain links to the decendents of the current page
- Ability to disable unsupported admin meta boxes and links. This plugin doesn't meta boxes such as tags. Therefore there is an option in the settings to disable these. <br />This will disable: <br />
'commentstatusdiv'<br />
'commentsdiv'<br />
'slugdiv'<br />
'trackbacksdiv'<br />
'postcustom'<br />
'postexcerpt'<br />
'tagsdiv-post_tag'<br />
'postimagediv'<br />
'formatdiv'<br />
'edit slug link'<br />
'quick edit link'<br />
- Curl Callback to configurable URLs after creating static post/page
- Curl Callback to configurable URLs after uploading images to NextGen Gallery
- Autosave is disabled when this plugin is installed

Installation
------------
1. Download/clone the plugin
2. Put the make-it-static folder under wp-content/plugins
3. You should see the make-it-static plugin in your wordpress plugins list
4. Click activate, this will prepare the option entries in the database for the first time

Configuration
-------------
1. Under settings create, you'll be able to see the new Make-it-static option
2. Setup the main static directory. This must be a physical server directory structure, where the static files are going to get stored
3. Enter the static web server address. This is used to generate the links for the pages table of contents
4. Enter the static file URL: This is a url for the static generated files. This is a web accessible address for the main statuc directory above. When populated this is used for the curl callback everytime a post is populated
5. Enter the Table of Contents prefix. Eg. if we set this to help/ therefore the Table of contents will be set to something like: http://www.yoururl.com/help/xxxxx/
6. Setup the paths replacements pair. This is useful to adjust any links to images or other files when saving the contents into a static file. Separate the entries using newlines.
7. Set disable unsupported meta boxes to yes if you don't want to show any meta boxes that doesn't have anything to do with static contents such as tags, etc. 
8. Setup callbacks if required. Any url put in the nextgen Gallery and after creation box will be called using curl after the user action is completed. More information on the settings page itself.
