<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Assets
 *
 * @package     Assets
 * @subpackage  Libraries
 * @category    Asset Management
 * @author      Jack Boberg
 * @link        https://github.com/jackboberg/CodeIgniter-Assets
 * @license		http://creativecommons.org/licenses/BSD/
 */


class Assets {

    private $ci;

    protected $script_dir   = 'assets/scripts/';
    protected $style_dir    = 'assets/styles/';
    protected $cache_dir    = 'assets/cache/';

    protected $combine_css  = TRUE;
    protected $minify_css   = TRUE;

    protected $combine_js   = TRUE;
    protected $minify_js    = TRUE;

    private $store = array();
    private $groups = array();

    // --------------------------------------------------------------------

    /**
     * Constructor
     *
     * @access  public
     * @param   array   $config     variables to override in library
     *
     * @return  void
     */
    public function __construct($config = array())
	{
        $this->ci = get_instance();
		log_message('debug', 'Assets Library initialized.');

        if (count($config) > 0)
        {
            $this->initialize($config);
        }
        $this->ci->load->helper(array('file','url'));
    }

    // --------------------------------------------------------------------

    /**
     * Initialize the configuration options
     *
     * @access  public
     * @param   array   $config     variables to override in library
     *
     * @return  void
     */
    public function initialize($config = array())
    {
        foreach ($config as $key => $val)
        {
            if (method_exists($this, 'set_'.$key))
            {
                $this->{'set_'.$key}($val);
            }
            else if (isset($this->$key))
            {
                $this->$key = $val;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add an asset to the store
     *
     * @access  public
     * @param   mixed   $assets (string)    path to asset
     *                          (array)     multiple assets
     *                                      single asset with minified version
     * @param   string  $group              name of asset group
     *
     * @return void
     **/
    public function add($assets, $group = NULL)
    {
        // convert strings to array for simplicty
        if ( ! is_array($assets))
        {
            $assets = array($assets);
        }
        // ensure the group is a string
        if ( ! is_string($group))
        {
            $group = 'main';
        }
        // create the group if needed
        if ( ! isset($this->store[$group]))
        {
            $this->store[$group] = array(
                'css'   => array(),
                'js'    => array()
            );
        }
        $group =& $this->store[$group];
        // let's get to adding!
        foreach ($assets as $key => $value)
        {
            $asset = array();
            // did the user provide a minified version?
            if (is_int($key))
            {
                $asset['path'] = $value;
            }
            else
            {
                $asset['path'] = $key;
                $asset['min'] = $value;
            }
            // what kind of asset is this?
            $type = (substr($asset['path'], -3) == 'css') ? 'css' : 'js';
            // ensure the file is not already present
            $hash = md5($asset['path']);
            if (in_array($hash, $group[$type]))
            {
                continue;
            }
            // add it to the store!
            $group[$type][$hash] = $asset;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Combine existing groups into new group
     *
     * @access  public
     * @param   string  $group      name of new group
     * @param   array   $groups     names of groups to combine
     *
     * @return void
     **/
    public function group($group, $groups)
    {
        // create the group if needed
        if ( ! isset($this->groups[$group]))
        {
            $this->groups[$group] = array();
        }
        $group =& $this->groups[$group];
        // add a reference to the existing groups
        // we combine assets on output
        foreach ($groups as $g)
        {
            // don't duplicate
            if ( ! in_array($g, $group))
            {
                $group[] = $g;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get all assets stored for this group
     *
     * @access  public
     * @param   string  $group  name of group
     *
     * @return  array
     **/
    public function get_group_assets($group)
    {
        $css = array();
        $js = array();
        // first look in the store
        if (isset($this->store[$group]))
        {
            $css = $this->store[$group]['css'];
            $js = $this->store[$group]['js'];
        }
        // is there a meta-group by the same name?
        if (isset($this->groups[$group]))
        {
            // get the assets for each group
            foreach ($this->groups[$group] as $g)
            {
                $assets = $this->get_group_assets($g);
                foreach (array('css', 'js') as $type)
                {
                    foreach ($assets[$type] as $hash => $a)
                    {
                        // no duplicates
                        if ( ! isset(${$type}[$hash]))
                        {
                            ${$type}[$hash] = $a;
                        }
                    }
                }
            }
        }
        return array('css'=>$css,'js'=>$js);
    }

    // --------------------------------------------------------------------

    /**
     * get links to stored styles for this group
     *
     * @access  public
     * @param   string  $group      name of the group
     * @param   array   $config     optional settings
     *
     * @return  string
     **/
    public function get_styles($group = NULL, $config = array())
    {
        return $this->get_assets($group, $config, 'css');
    }

    // --------------------------------------------------------------------

    /**
     * get links to stored scripts for this group
     *
     * @access  public
     * @param   string  $group      name of the group
     * @param   array   $config     optional settings
     *
     * @return  string
     **/
    public function get_scripts($group = NULL, $config = array())
    {
        return $this->get_assets($group, $config, 'js');
    }

    // --------------------------------------------------------------------

    /**
     * get links to stored assets for this group, of specified type
     *
     * @access  public
     * @param   string  $group      name of the group
     * @param   array   $config     optional settings
     * @param   string  $type       asset type
     *
     * @return  string
     **/
    public function get_assets($group = NULL, $config = array(), $type = NULL)
    {
        if (is_null($group))
        {
            $group = 'main';
        }
        $output = '';
        if (is_null($type))
        {
            $output .= $this->get_assets($group, $config, 'css');
            $output .= $this->get_assets($group, $config, 'js');
            return $output;
        }
        // do we have assets of this type?
        $assets = $this->get_group_assets($group);
        if (empty($assets[$type]))
        {
            return $output;
        }
        // setup config options
        extract($this->get_config_options($config));
        // get the output
        switch ($type)
        {
            case 'css':
                $output .= "\n\t<!-- CSS Assets -->\n\t";
                // is there a specified media type?
                $media = isset($config['media'])
                    ? $config['media']
                    : 'all'
                    ;
                $output .= $this->get_links('css', $assets['css'], $combine_css, $minify_css, $media);
                break;
            case 'js':
                $output .= "\n\t<!-- JS Assets -->\n\t";
                $output .= $this->get_links('js', $assets['js'], $combine_js, $minify_js);
                break;
        }
        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * get HTML for assets
     *
     * @access  private
     * @param   string  $type       asset type
     * @param   array   $assets     assets to process
     * @param   bool    $combine    toggle combining assets
     * @param   bool    $minify     toggle minifying assets
     * @param   string  $media      CSS media attribute
     *
     * @return  string
     **/
    private function get_links($type, $assets, $combine, $minify, $media = NULL)
    {
        if ( ! $combine && ! $minify)
        {
            return $this->get_raw_links($type, $assets, $media);
        }
        elseif ($combine && ! $minify)
        {
            return $this->get_combined_links($type, $assets, $media);
        }
        elseif ($minify && ! $combine)
        {
            return $this->get_minified_links($type, $assets, $media);
        }
        else
        {
            return $this->get_combined_minified_links($type, $assets, $media);
        }
    }

	// ------------------------------------------------------------------------
	
	/**
	 * get uncombined/unminified links
	 *
	 * @access  private
	 * @param	string	$type       asset type
	 * @param	array	$assets     array of assets
     * @param	string	$media      CSS media attribute
     *
	 * @return	string
	 **/
	private function get_raw_links($type, $assets, $media)
	{
		$output = '';
		foreach ($assets as $hash => $asset)
		{
			$output .= $this->tag($type, $asset['path'], FALSE, $media);
		}
		return $output;
    }

    // --------------------------------------------------------------------

    /**
     * undocumented function
     *
     * @return  void
     **/
    private function get_combined_links()
    {
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * undocumented function
     *
     * @return  void
     **/
    private function get_minified_links()
    {
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * undocumented function
     *
     * @return  void
     **/
    private function get_combined_minified_links()
    {
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * determine minify/combine preferences
     *
     * @access  private
     * @param   array   $config     config preferences
     *
     * @return  array
     **/
    private function get_config_options($config)
    {
        $options = array(
             'combine_css'  => $this->combine_css,
             'minify_css'   => $this->minify_css,
             'combine_js'   => $this->combine_js,
             'minify_js'    => $this->minify_js,
        );
        // override explicit settings
        foreach ($options as $key => $val)
        {
            if (isset($config[$key]))
            {
                $options[$key] = (bool) $config[$key];
            }
        }
        // catch simple settings
        foreach (array('combine', 'minify') as $o)
        {
            if (isset($config[$o]))
            {
                $options[$o.'_css'] = $options[$o.'_js'] = (bool) $config[$o];
            }
        }
        return $options;
    }

    // --------------------------------------------------------------------

	/**
	 * get HTML tag for asset
	 *
	 * @access	private
	 * @param	string	$type   asset type
	 * @param	string	$path   path to asset
	 * @param	bool	$cache  toggle for using cache directory
     * @param	string	$media  CSS media attribute
     *
     * @return	string
	 **/
	private function tag($type, $path, $cache = FALSE, $media = NULL)
	{
        $output = '';
        // is this a local path?
        if ( ! filter_var($path, FILTER_VALIDATE_URL))
        {
            if ($cache)
            {
                $path = $this->cache_dir . $path;
            }
            elseif ($type == 'css')
            {
                $path = site_url($this->style_dir . $path);
            }
            else
            {
                $path = site_url($this->script_dir . $path);
            }
        }
		switch($type)
		{
			case 'css':
				$output .= '<link type="text/css" rel="stylesheet" href="'
					. $path
					. '" media="' . $media
					. '" />' . "\r\n";
				break;
			case 'js':
				$output .= '<script type="text/javascript" src="'
					. $path
					. '"></script>' . "\r\n";
				break;
		}
		return $output;
	}	

    // --------------------------------------------------------------------

}
/* End of file Assets.php */
/* Location: ./libraries/Assets.php */
