<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Assets Helpers
 *
 * @package		Assets
 * @subpackage	Helpers
 * @category	Asset Management
 * @author      Jack Boberg
 * @link        https://github.com/jackboberg/CodeIgniter-Assets
 * @license		http://creativecommons.org/licenses/BSD/
 */

// ------------------------------------------------------------------------

/**
 * alias for Assets::get_styles();
 *
 * @access  public
 * @param   string  $group      name of the group
 * @param   array   $config     optional settings
 *
 * @return  string
 */
if ( ! function_exists('get_styles'))
{
	function get_styles($group = 'main', $config = array())
    {
        $CI = get_instance();
        if ( ! isset($CI->assets))
        {
            $CI->load->library('assets');
        }
        return $CI->assets->get_styles($group, $config);
	}
}

// ------------------------------------------------------------------------

/**
 * alias for Assets::get_scripts();
 *
 * @access  public
 * @param   string  $group      name of the group
 * @param   array   $config     optional settings
 *
 * @return  string
 */
if ( ! function_exists('get_scripts'))
{
	function get_scripts($group = 'main', $config = array())
    {
        $CI = get_instance();
        if ( ! isset($CI->assets))
        {
            $CI->load->library('assets');
        }
        return $CI->assets->get_scripts($group, $config);
	}
}

// ------------------------------------------------------------------------

/**
 * alias for Assets::get_assets();
 *
 * @access  public
 * @param   string  $group      name of the group
 * @param   array   $config     optional settings
 *
 * @return  string
 */
if ( ! function_exists('get_assets'))
{
	function get_assets($group = 'main', $config = array())
    {
        $CI = get_instance();
        if ( ! isset($CI->assets))
        {
            $CI->load->library('assets');
        }
        return $CI->assets->get_assets($group, $config);
	}
}

// ------------------------------------------------------------------------

/**
 * alias for Assets::add();
 *
 * @access  public
 * @param   mixed   $assets (string)    path to asset
 *                          (array)     multiple assets
 *                                      single asset with minified version
 * @param   string  $group              name of asset group
 *
 * @return void
 */
if ( ! function_exists('add_assets'))
{
	function add_assets($assets, $group = NULL)
    {
        $CI = get_instance();
        if ( ! isset($CI->assets))
        {
            $CI->load->library('assets');
        }
        return $CI->assets->add($assets, $group);
	}
}

// ------------------------------------------------------------------------

/**
 * alias for Assets::group();
 *
 * @access  public
 * @param   string  $group      name of new group
 * @param   array   $groups     names of groups to combine
 *
 * @return void
 */
if ( ! function_exists('group_assets'))
{
	function group_assets($group, $groups)
    {
        $CI = get_instance();
        if ( ! isset($CI->assets))
        {
            $CI->load->library('assets');
        }
        return $CI->assets->group($group, $groups);
	}
}
// ------------------------------------------------------------------------

/* End of file authentic_helper.php */
/* Location: ./helpers/authentic_helper.php */
