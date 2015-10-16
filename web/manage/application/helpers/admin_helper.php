<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *
 * @package		helpers
                'title' => '个人资料',
 * @author		Glen.luo
 * @since		Version 1.0
 */

// ------------------------------------------------------------------------

/**
 * 根据当前用户的权限生成相关的侧边栏操作菜单.
 *
 * @access	public
 * @param	array	the priv config.
 * @return	array
 */
if ( ! function_exists('menu'))
{
	function menu($priv = array())
    {
        $CI = & get_instance();
        $priv = empty($priv) ? load_priv_by_role($_SESSION['user']['role']) : $priv;
        $CI->load->config('menu');
        $CI->load->config('privilege');
        $modules = $CI->config->item('modules');
        $purview = $CI->config->item('purview');

        foreach ($modules as $k => &$module) {
            if (isset($module['url'])) {
                if (!in_array($module['priv'], $priv)) {
                    unset($modules[$k]);
                    continue;
                }
                else {
                    $module['url'] = site_url($module['url']);
                }
            }

            if (isset($module['children'])) {
                foreach ($module['children'] as $j => $item) {
                    if ( !in_array($item['priv'], $priv) ) {
                        unset($module['children'][$j]);
                    }
                    else {
                        $module['children'][$j]['url'] = site_url($module['children'][$j]['url']);
                    }
                }
            }
            else {
                $module['children'] = array();
            }

            if (empty($module['children']) && !isset($module['url'])) {
                unset($modules[$k]);
            }
        }

        return $modules;
	}
}

/**
 * 车型选择前缀树状菜单.
 *
 * @access	public
 * @param	array	    树形结构的多维数组.
 * @return	array
 */
if ( ! function_exists('cars_prefix_tree'))
{
    function cars_prefix_tree($tree, $disply_name = 'title', $val_name = 'id', $selected = '')
    {
        if (empty($tree)) {
            return '';
        }

        $cars_select_tree = array();

        $prefix = array();
        foreach ($tree as $brand) {
            $prefix[] = $brand['title'];
            foreach ($brand['children'] as $manufacturer) {
                $prefix[] = $manufacturer['title'];
                foreach ($manufacturer['children'] as $series) {
                    $prefix[] = $series['title'];
                    foreach ($series['children'] as $spec) {
                        $cars_select_tree[] = array(
                            'id'    => $spec['id'],
                            'title' => join('-', $prefix) . "-{$spec['title']}"
                        );
                    }
                    array_pop($prefix);
                }
                array_pop($prefix);
            }
            array_pop($prefix);
        }

        $opt_tag = '';

        if (!is_array($selected)) {
            $selected = array($selected);
        }

        foreach ($cars_select_tree as $node) {
            if ( in_array($node[$val_name], $selected) ) {
                $format = "<option value=\"%s\" selected>%s</option>\n";
            }
            else {
                $format = "<option value=\"%s\">%s</option>\n";
            }

            $opt_tag .= sprintf($format, $node[$val_name], $node[$disply_name]);
        }

        return $opt_tag;
    }
}


/**
 * 操作提示.
 *
 * @access	public
 * @param	string	操作描述.
 * @param	string	操作结果：fail、success、warn.
 * @param	string	跳转地址.
 * @return	array
 */
if ( ! function_exists('error_handler'))
{
    function error_handler($content, $status, $url)
    {
        $CI = & get_instance();
        $CI->session->set_flashdata(
            'action_msg',
            array(
                'content' => $content,
                'status'  => $status,
            )
        );

        redirect($url);

        return FALSE;
    }
}


/**
 * 获取当前登录管理员用信息.
 *
 * @access	public
 * @param	mixed	用户ID.
 * @return	array
 */
if ( ! function_exists('get_manager_info'))
{
	function get_manager_info(&$user)
    {
        $CI = & get_instance();
        $role_cfg = $CI->config->item('role');

        if (!isset($user) || empty($user)) {
            return NULL;
        }

        $user['avatar'] = empty($user['avatar'])
                            ? '/assets/img/user-default-382x382.jpg'
                            : $user['avatar'];
        $user['display_name'] = empty($user['realname'])
                            ? $user['username']
                            : $user['realname'];
        $user['group_name'] = isset($role_cfg[$user['role']]) ? $role_cfg[$user['role']]['title'] : '-';


        return $user;
	}
}

/**
 * 写入管理员操作日志.
 *
 * @access	public
 * @return	null
 */
if ( ! function_exists('admin_log'))
{
	function admin_log($msg)
    {
        $CI = & get_instance();
        $data = array(
            'uid'       => $_SESSION['user']['uid'],
            'action'    => $msg,
            'ip'        => ip2long($CI->input->ip_address()),
            'model'     => $CI->router->class,
        );
        $CI->load->model('admin_log_model', 'admin_log');
        $CI->admin_log->write($data);
	}
}

/**
 * 生成面包屑导航.
 *
 * @access	public
 * @param	array	the nav path config.
 * @return	array
 */
if ( ! function_exists('breadcrumb_trail'))
{
	function breadcrumb_trail($path = array())
    {
        $CI = & get_instance();

        $_out = array(
            'login', 'upload',
        );

        $_c = $CI->router->class;
        if (in_array($_c, $_out)) {
            return array();
        }

        $CI->load->config('menu');
        $modules = $CI->config->item('modules');
        $_cfg = array(
            'start' => array(
                'title' => '管理中心',
                'url'   => '/home/index',
            ),
        );

        $_r = "/{$CI->router->class}/{$CI->router->method}";

        if ($_r == '/home/profile') {
            $_cfg['c'] = array(
                'title' => '个人资料',
                'url'   => ''
            );

            return $_cfg;
        }

        foreach ($modules as $item) {
            if (isset($item['url']) && $item['url'] == $_r) {
                $_cfg['c'] = array(
                    'title' => $item['title'],
                    'url'   => site_url($item['url'])
                );
            }
            if (isset($item['children'])) {
                foreach ($item['children'] as $v) {
                    if (stristr($v['url'], "/{$CI->router->class}/") !== FALSE) {
                        $_cfg['c'] = array(
                            'title' => $item['title'],
                            'url'   => site_url($v['url'])
                        );
                    }
                }
            }
        }

        if (!empty($path)) {
            if (isset($path['c'])) {
                $_cfg['c'] = $path['c'];
            }

            if (isset($path['m'])) {
                $_cfg['m'] = $path['m'];
            }
            else {
                $_cfg['m'] = $path;
            }
        }

        return $_cfg;
	}
}

/**
 * 得到站内资源的图片地址.
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('get_website_img'))
{
    function get_website_img($img)
    {
        $pos = strpos($img, '/');
        if ($pos !== 0 || $pos === FALSE) {
            $img = "/{$img}";
        }

        return get_website_domain() . $img;
    }
}

/**
 * 得到指定的子域名
 *
 * @access	public
 * @param	string
 * @return	string
 */
if ( ! function_exists('get_website_domain'))
{
    function get_website_domain($subdomain = 'www')
    {
        $_domain = explode('.', $_SERVER['HTTP_HOST']);
        $_domain[0] = $subdomain;
        return 'http://' . join('.', $_domain);
    }
}

/**
 * 发送短信.
 *
 * @access	public
 * @return	string
 */
if ( ! function_exists('sms'))
{
    function sms($type, $phone, $msg)
    {
    }
}

/**
 * 检查多维数组的最大维度.
 *
 * @access	public
 * @param	array
 * @return	string
 */
if ( ! function_exists('count_dim'))
{
    function count_dim($a)
    {
        $rv = array_filter($a,'is_array');
        return count($rv) > 0 ? count($rv) : 0;
    }
}


/**
 * Convert BR tags to nl
 *
 * @param string The string to convert
 * @return string The converted string
 */
if ( ! function_exists('br2nl'))
{
    function br2nl($string)
    {
        return preg_replace('/(\<br\s*?\/?\>)+/mi', "\n", $string);
    }
}

/**
 * 更新SiteMap
 */
if ( ! function_exists('sitemap'))
{
    function sitemap($url, $date_day = '')
    {
        $tmplate_item = <<<EOF
        <url>
          <loc>{url}</loc>
          <lastmod>{date_day}</lastmod>
          <changefreq>daily</changefreq>
          <priority>0.8</priority>
        </url>

EOF;

        $tmplate_end = <<<EOF
   </urlset>
EOF;

        $date_day = empty($date_day) ? date('Y-m-d') : $date_day;

        $_path = realpath(BASEPATH . '../www/sitemap.xml');
        if (file_exists($_path) === FALSE) {
            return FALSE;
        }

        $c = @file_get_contents($_path);
        if ($c === FALSE) {
            return FALSE;
        }

        $item = str_replace(array('{url}', '{date_day}'), array($url, $date_day), $tmplate_item);
        $item .= $tmplate_end;

        $c = str_replace($tmplate_end, $item, $c);

        $result = @file_put_contents($_path, $c);
        if ($result) {
           return TRUE;
        }
        else {
            return FALSE;
        }
    }
}