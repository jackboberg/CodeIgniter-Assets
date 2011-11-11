Codeigniter Assets
==================

This package helps manage your CSS and JS files. You can configure the library to 
combine, minify, and cache assets. Cached assets are automatically regenerated if 
any file has been edited since the last cache file was generated.


## USAGE

Load the library as normal:

	$this->load->library('assets');

    // optionally pass a config array
	$this->load->library('assets', $config);

All the public methods have aliased helper functions to make working with the 
library easier in view files.

    $this->load->helper('assets');


### Adding Assets

Adding assets can be done one at a time, or by passing an array. You can mix your 
JS and CSS assets together, the library will manage them seperately for you.

    $this->assets->add('layout.css');

    $assets = array(
        'jquery.js',
        'jquery-ui.js',
        'jquery-ui.css'
    );
    $this->assets->add($assets);

    // or use the helper alias
    add_assets($assets);

You can optionally define a pre-minimized asset.

    $asset = array(
        'other.css',
        'other.js',
        'file.js' => 'file.min.js'
    );
    add_assets($assets);


### Using groups

Assets can be grouped, they will be combined only with assets from their group and 
saved as a seperate cache.

    $this->assets->add($assets, $group_name);

Groups can be combined, allowing you to create a new group from existing groups.

    $groups = array(
        'typograghy',
        'print',
        'ui'
    );
    $this->assets->group('admin', $groups);

    // or use the helper alias
    group_assets('admin', $groups);

### Outputing Links

You can output all links, or just one asset type. The library will return strings 
of HTML tags to be echoed in your view files.

    echo $this->assets->get_assets();
    // or use the helper alias
    echo get_assets();

    // just the CSS
    echo $this->assets->get_styles();
    echo get_styles();

    // just the JS
    echo $this->assets->get_scripts();
    echo get_scripts();

You can output only assets from a specified group.

    echo get_assets($group_name);

    // only JS in the group
    echo get_scripts($group_name);


#### Configuring Output

All of the output helpers accept a second *$config* parameter. If not specified, 
the library will use the global values in the library.

    $config = array(
        'media'     => $media_type,
        'combine'   => TRUE,
        'minify'    => TRUE
    );
    echo get_styles($group_name, $config);

    // or you can be more specific
    $config = array(
        'combine_css'  => TRUE,
        'minify_css'   => FALSE,
        'combine_js'   => TRUE,
        'minify_js'    => TRUE,
    );
    echo get_styles($group_name, $config);


* **media**: CSS only. eg: 'print', 'screen', 'all and (min-width:500px)'
* **combine**: should we combine both asset types in this group
* **minify**: should we minify both asset types in this group
