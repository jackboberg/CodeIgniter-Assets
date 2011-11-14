<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Assets Config
| -------------------------------------------------------------------------
*/

/**
 * Path to the script directory
 *
 * @var string
 **/
$config['script_dir'] = 'assets/scripts/';

/**
 * Path to the style directory
 *
 * @var string
 **/
$config['style_dir'] = 'assets/styles/';

/**
 * Path to the (writeable) cache directory
 *
 * @var string
 **/
$config['cache_dir'] = 'assets/cache/';

// --------------------------------------------------------------------

/**
 * should CSS files be combined
 *
 * @var bool
 **/
$config['combine_css'] = TRUE;

/**
 * should CSS files be minified
 *
 * @var bool
 **/
$config['minify_css'] = TRUE;

// --------------------------------------------------------------------

/**
 * should JS files be combined
 *
 * @var bool
 **/
$config['combine_js'] = TRUE;

/**
 * should JS files be minified
 *
 * @var bool
 **/
$config['minify_js'] = TRUE;

// --------------------------------------------------------------------

/**
 * should we check file modification dates when trying to load from cache
 *
 * this should be set to FALSE when in production, it will enable a 
 * store to be built for fast file lookups
 *
 * @var bool
 **/
$config['auto_update'] = FALSE;


/* End of file assets.php */
/* Location: ./config/assets.php */
