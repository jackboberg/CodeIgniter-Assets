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

You can optionally define a pre-minimized asset.

    $asset = array(
        'other.css',
        'other.js',
        'file.js' => 'file.min.js'
    );
    $this->assets->add($assets);


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

### Outputing Links

In your views, a helper method allows you to retireve your packaged links.

    // just the CSS
    echo get_styles();

    // just the JS
    echo get_scripts();

    // BOTH!
    echo get_assets();

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

* **media**: CSS only. eg: 'print', 'screen', 'all and (min-width:500px)'
* **combine**: should we combine the files in this group
* **minify**: should we minify the files in this group
