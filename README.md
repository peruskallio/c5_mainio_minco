# Mainio MinCo for concrete5 CMS #
Mainio MinCo is a concrete5 add-on built by Mainio Tech Ltd. <www.mainiotech.fi>

The original author of this add-on is:
Antti Hukkanen <antti.hukkanen@mainiotech.fi>

The add-on's name stands for Minify and Combine static resources on your site.
This add-on is meant for developers for easy minification and combination
of CSS files in concrete5 themes.

Note: If you don't have any cache library (e.g. apc/memcached) installed and
in use with your concrete5 installation, this add-on will probably lower the
performance of your site rather than giving you any extra performance boost.

For minifying the source files this add-on uses the PHP-based Minify library:
https://github.com/mrclay/minify

# Usage #
After installing this, you can apply the css/js combining and minifying pretty easily
in your themes. The only thing you need to do is to wrap the lines for which you want 
to apply the combining and minification into two helper functions as follows:

```php
<?php MincoBlock::start('layout_resources', 1) ?>
<script type="text/javascript" src="<?php echo View::getInstance()->getThemePath() ?>/js/cufon-yui.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $this->getStyleSheet('style/reset.css') ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->getStyleSheet('style/mystyles.css') ?>" />
<link rel="stylesheet" type="text/css" href="<?php echo $this->getStyleSheet('style/block_overrides.css') ?>" />
<script type="text/javascript" src="<?php echo View::getInstance()->getThemePath() ?>/js/my_awesome_unminified_script.js"></script>
<link rel="stylesheet" type="text/css" href="<?php echo $this->getStyleSheet('typography.css') ?>" />
<?php MincoBlock::end() ?>
```

This would result for example in the following (or similar) output in your html:

```php
<script type="text/javascript" src="/index.php/tools/packages/mainio_minco/min?k=0caf9ce48d3ad9bcac4026d7a4d4b7d7"></script>
<link rel="stylesheet" type="text/css" href="/index.php/tools/packages/mainio_minco/min?k=613d6d8d13122913c2c73d89778511c1" />
```

And these addresses provide your site users a combined and minified version of all the css/js
files specified between the minco block.

The two arguments passed to the minco block starting function are optional but suggested to be used.
The arguments are meant for cache-related functionality and refer to the following items:
* First argument (in the example 'layout_resources'): Unique cache ID for this block
  * Used to save/get this minified block contents from server-side cache
* Second argument (in the example 1): The file version
  * Should be changed if you want to clear out browser cache for combined files in this block
  * This is also appended to the server-side cache ID
  
## Save combined assets to static files ##
If you want to save the combined and minified files into static assets you can do so with 
the Mainio MinCo add-on. However, this is not suggested because e.g. if you want to combine
the header_required assets, they might differ on each page. In these situations, you should
let the Mainio MinCo produce your assets by the default tools url call.

For example in the example presented above, there is no risk of having different files on
different pages loaded inside the Mainio MinCo block, so in these kinds of situations you
might want to save the combined and minified file into static assets files. Before doing
this, you should make sure:
1. That your server process is allowed to write into the template files where you've
   included the Minco::start() and Minco::end() functions
2. You should also have both of these function calls in their own lines without anything
   else on those lines as shown in the example above

After you've made sure both of these requirements are fulfilled, you can let Mainio MinCo
to combine, minify and save the files into static assets files during the next request.
To do this, you need to modify the block ending function to this:

```php
<?php MincoBlock::end(true) ?>
```

## Applying the Mainio MinCo block ##
You can apply the block starting and ending functions to any place in your theme. However,
it is not suggested to wrap the whole contents of your theme into a single minco block 
because this will probably break your side especially if it originally has many css/js 
files.

A single minco block inside the start() and end() functions can contain any number of 
javascript or css files that are located on this specific site or alternatively on 
some external location. The files in external locations are not included in the 
combined css/js files and are left un-touched.

The order of the files resources from the original HTML are the following:
1. Everything that wasn't found to be a css/js file loaded from this site, excluding IE conditional statements
2. Inline javascript that contains concrete-specific global variables starting with var CCM_...
3. All the js files loaded from your site combined into a single file
4. Inline javascript that doesn't contain concrete-specific global variables, e.g. $(document).ready();
5. Inline CSS specific inside the block
6. All the css files loaded from your site combined into a single file


## CDN resources ##
By default, if the Mainio MinCo finds any occurences of js/css files that are defined
to be found from an external CDN location, they are replaced with their specified CDN
location to speed up the site loading for the users.


## Options ##
The add-on is designed to be highly configurable and currently these configurations can be changed
in your config/site.php by defining them to PHP constants:

```php
define('CONF_NAME', 'conf_value');
```

The boolean configurations are the following (true/false):
* MINCO_USE_CDN_RESOURCES: determines whether to use CDN resources, defaults to true
* MINCO_BYPASS_CACHE: Bypasses all server-side cache for all minco related cachable resources, defaults to false
* MINCO_CLIENT_CACHE: Determines whether to tell the client browser to cache the resources, defaults to true
* MINCO_MINIFY_HTML: Determines whether the output HTML is wanted to be minified, defaults to false
* MINCO_MINIFY_INLINE: Determines whether the inline js/css in minco blocks is wanted to be minified, defaults to true

