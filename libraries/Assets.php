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
            return $this->get_combined_link($type, $assets, $media);
        }
        elseif ($minify && ! $combine)
        {
            return $this->get_minified_links($type, $assets, $media);
        }
        else
        {
            return $this->get_combined_minified_link($type, $assets, $media);
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
     * get combined link
     *
	 * @access  private
	 * @param	string	$type       asset type
	 * @param	array	$assets     array of assets
     * @param	string	$media      CSS media attribute
     *
	 * @return	string
     **/
    private function get_combined_link($type, $assets, $media)
    {
        // check for cached file
        $filename = $this->get_cache_filename($type, $assets, 'combine');
        if ( ! is_file(APPPATH . $this->cache_dir . $filename))
        {
            // build filedata
            $filedata = '';
            foreach ($assets as $a)
            {
                if (filter_var($a['path'], FILTER_VALIDATE_URL))
                {
                    // use modified read_file for remote files
                    $filedata .= $this->read_file($a['path']);
                }
                else
                {
                    // for local files use the system read_file
                    switch ($type)
                    {
                        case 'css':
                            $path = $this->style_dir . $a['path'];
                            break;
                        default:
                            $path = $this->script_dir . $a['path'];
                            break;
                    }
                    $filedata .= read_file($path);
                }
            }
            // write to cache
            if ( ! write_file($this->cache_dir . $filename, $filedata))
            {
                return FALSE;
            }
        }
        return $this->tag($type, $filename, TRUE, $media);
    }

    // --------------------------------------------------------------------

    /**
     * get minified links
     *
	 * @access  private
	 * @param	string	$type       asset type
	 * @param	array	$assets     array of assets
     * @param	string	$media      CSS media attribute
     *
	 * @return	string
     **/
    private function get_minified_links($type, $assets, $media)
    {
        $output = '';
        foreach ($assets as $a)
        {
            // is these a pre-minified version available
            if ( ! isset($a['min']))
            {
                // have we minified this file in the past
                $min_path = $this->get_minified_path($type, $a['path']);
                if ($type == 'css')
                {
                    $dir = $this->style_dir;
                }
                else
                {
                    $dir = $this->script_dir;
                }
                if ( ! is_file($dir . $min_path))
                {
                    // minify the file and write to path
                    $this->minify($type, $a['path'], $dir . $min_path); 
                }
                else
                {
                    // is the original file newer
                    $min_info = get_file_info($dir . $min_path);
                    $orig_info = get_file_info($dir . $a['path']);
                    if ($orig_info['date'] > $min_info['date'])
                    {
                        // re-minify the file and write to path
                        $this->minify($type, $a['path'], $dir . $min_path); 
                    }
                }
                $a['min'] = $min_path;
            }
            $output .= $this->tag($type, $a['min'], FALSE, $media);
        }
        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * undocumented function
     *
     * @return  void
     **/
    private function get_combined_minified_link()
    {
        return FALSE;
    }

    // --------------------------------------------------------------------

    /**
     * get path of locally stored minified version
     *
     * @access  private
	 * @param	string	$type       asset type
     * @param   string  $path       path to original
     *
     * @return  string
     **/
    private function get_minified_path($type, $path)
    {   
        if (filter_var($path, FILTER_VALIDATE_URL))
        {
            // remote path, just get the filename
            $filename = substr(strrchr($path, '/'), 1);
            return substr($filename, 0, strrpos($filename, '.')) . '.min.' . $type; 
        }
        else
        {
            // local path, include original file location
            $dir = '';
            if ($pos = strrpos($path, DIRECTORY_SEPARATOR))
            {
                // file is in sub-folder
                $dir = substr($path, 0, $pos + 1);
                $filename = strrchr($path, DIRECTORY_SEPARATOR);
            }
            else
            {
                $filename = $path;
            }
            return substr($filename, 0, strrpos($filename, '.')) . '.min.' . $type; 
        }
    }

    // --------------------------------------------------------------------

    /**
	 * minify asset
	 *
	 * @access	private
	 * @param	string  $type       asset type
     * @param	string	$path       path to original
     * @param   string  $min_path   path to save minifed version
     *
     * @return  bool
     **/
	public function minify($type, $path, $min_path)
    {
        // read file into variable
        if (filter_var($path, FILTER_VALIDATE_URL))
        {
            // use modified read_file for remote files
            $contents = $this->read_file($path);
        }
        else
        {
            // for local files use the system read_file
            switch ($type)
            {
                case 'css':
                    $path = $this->style_dir . $path;
                    break;
                default:
                    $path = $this->script_dir . $path;
                    break;
            }
            $contents = read_file($path);
        }
        // ensure we have some content
        if ( ! $contents)
        {
            return FALSE;
        }
        // minimize the contents
        $output = '';
		switch($type)
		{
            case 'js':
                $this->ci->load->library('jsmin');
                $output .= $this->ci->jsmin->minify($contents);
				break;
			case 'css':
                $this->ci->load->library('cssmin');
                $config['relativePath'] = site_url($this->style_dir) .'/';
                $this->ci->cssmin->config($config);
                $output .= $this->ci->cssmin->minify($contents);
				break;
        }
        // write the minimized content to file
        return write_file($min_path, $output);
    }

    // --------------------------------------------------------------------

    /**
     * get the hashed filename for these assets
     *
	 * @access  private
	 * @param	string	$type       asset type
     * @param	array	$assets     array of assets
     * @param   string  $process    processing type
     *
     * @return  string
     **/
    private function get_cache_filename($type, $assets, $process)
    {
        $modified = $this->get_last_modified($type, $assets);
        $hash = md5(json_encode($assets) . $modified . $process);
        return  $hash . '.' . $type;
    }

    // --------------------------------------------------------------------

    /**
     * get timestamp of most recently modified file
     *
     * @access  private
     * @param   array   $assets     files to examine
     *
     * @return  string
     **/
    private function get_last_modified($type, $assets)
    {
        $timestamp = 0;
        foreach ($assets as $a)
        {
            // only check local files
            if ( ! filter_var($a['path'], FILTER_VALIDATE_URL))
            {
                switch ($type)
                {
                    case 'css':
                        $path = $this->style_dir . $a['path'];
                        break;
                    default:
                        $path = $this->script_dir . $a['path'];
                        break;
                }
                $data = get_file_info($path);
                if ($data['date'] > $timestamp)
                {
                    $timestamp = $data['date'];
                }
            }
        }
        return $timestamp;
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
                $dir = $this->cache_dir;
            }
            elseif ($type == 'css')
            {
                $dir = $this->style_dir;
            }
            else
            {
                $dir = $this->script_dir;
            }
            $path = site_url($dir . $path);
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

    /**
     * Opens the file specfied in the path and returns it as a string
     *
     * this is a duplicate of the file_helper method, without a check
     * for file_exists()
     *
     * @access	private
     * @param	string  $file   path to file
     *
     * @return	string
     **/
    public function read_file($file)
    {
		if (function_exists('file_get_contents'))
		{
			return file_get_contents($file);
        }

		if ( ! $fp = @fopen($file, FOPEN_READ))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$data = '';
		if (filesize($file) > 0)
		{
			$data =& fread($fp, filesize($file));
		}

		flock($fp, LOCK_UN);
		fclose($fp);

		return $data;
	}

    // --------------------------------------------------------------------

}
/* End of file Assets.php */
/* Location: ./libraries/Assets.php */
