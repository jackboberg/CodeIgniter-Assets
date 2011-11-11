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
    public function get_styles($group = 'main', $config = array())
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
    public function get_scripts($group = 'main', $config = array())
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
    public function get_assets($group = 'main', $config = array(), $type = 'both')
    {
        // setup config options
        extract($this->get_config_options($config));
        // $combine_css
        // $minify_css
        // $combine_js
        // $minify_js
        
        // get the assets
        $assets = $this->get_group_assets($group);
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

}
/* End of file Assets.php */
/* Location: ./libraries/Assets.php */
