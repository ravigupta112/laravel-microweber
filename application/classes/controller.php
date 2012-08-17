<?php

// Controller Class
class Controller {

    function index() {
        $page_url = url_string();
        $page_url = rtrim($page_url, '/');

        $is_editmode = url_param('editmode');
        if ($is_editmode) {
            $editmode_sess = session_get('editmode');

            $page_url = url_param_unset('editmode', $page_url);
            if (is_admin() == true) {
                if ($editmode_sess == false) {
                    session_set('editmode', true);
                }
                safe_redirect(site_url($page_url));
                exit();
            } else {
                $is_editmode = false;
            }
        }
        $is_editmode = session_get('editmode');

        if (trim($page_url) == '') {
            //
            $page = get_homepage();
        } else {

            $page = get_page_by_url($page_url);

            if (empty($page)) {
                $page = get_homepage();
            }
        }
        // d($page);
        if ($page['content_type'] == "post") {
            $content = $page;
            $page = get_content_by_id($page['content_parent']);
        } else {
            $content = $page;
        }
        // d($page);
        define_constants($content);

        $render_file = get_layout_for_page($page);

        // d ( $render_file );

        if ($render_file) {
            $l = new View($render_file);
            // $this->content = $content;
            // var_dump($l);
            // $l->set ( $this );
            $l = $l->__toString();

            if ($is_editmode == true) {
                $is_admin = is_admin();
                if ($is_admin == true) {

                    $tb = INCLUDES_DIR . DS . 'toolbar' . DS . 'toolbar.php';

                    $layout_toolbar = new View($tb);
                    $layout_toolbar = $layout_toolbar->__toString();
                    if ($layout_toolbar != '') {
                        $l = str_replace('<body>', '<body>' . $layout_toolbar, $l);
                    }
                }
            }

            // var_dump($l);
            $l = parse_micrwober_tags($l, $options = false);
            print $l;
            exit();
        } else {
            print 'NO LAYOUT IN ' . __FILE__;
            d($template_view);
            d($page);
            exit();
        }
        // var_dump ( $page );
        // var_dump($ab);
    }

    function editor_tools() {

        $tool = url(1) ? : 'index';

        $tool = str_replace('..', '', $tool);

        $p_index = INCLUDES_PATH . 'toolbar/editor_tools/index.php';
        $p_index = normalize_path($p_index, false);

        $p = INCLUDES_PATH . 'toolbar/editor_tools/' . $tool . '/index.php';
        $p = normalize_path($p, false);

        $l = new View($p_index);
        $layout = $l->__toString();
        // var_dump($l);

        if (is_file($p)) {
            $p = new View($p);
            $layout_tool = $p->__toString();
            $layout = str_replace('{content}', $layout_tool, $layout);
        } else {
            $layout = str_replace('{content}', 'Not found!', $layout);
        }

        $layout = parse_micrwober_tags($layout, $options = false);
        print $layout;
        exit();
        //
        //header("HTTP/1.0 404 Not Found");
        //$v = new View(ADMIN_VIEWS_PATH . '404.php');
        //echo $v;
    }

    function show_404() {
        header("HTTP/1.0 404 Not Found");
        $v = new View(ADMIN_VIEWS_PATH . '404.php');
        echo $v;
    }

    function admin() {
        define_constants();
        $l = new View(ADMIN_VIEWS_PATH . 'admin.php');
        $l = $l->__toString();
        // var_dump($l);
        $layout = parse_micrwober_tags($l, $options = false);
        print $layout;
        exit();
    }

    function api_html() {
        if (!defined('MW_API_HTML_OUTPUT')) {
            define('MW_API_HTML_OUTPUT', true);
        }
        $this->api();
    }

    function api() {
        if (!defined('MW_API_CALL')) {
            define('MW_API_CALL', true);
        }
        define_constants();
        $api_exposed = '';




        // user functions
        $api_exposed .= 'user_login user_logout ';

        // content functions
        $api_exposed .= 'save_edit ';
        $api_exposed .= 'set_language ';
        $api_exposed .= (api_expose(true));
        $api_exposed = explode(' ', $api_exposed);
        $api_exposed = array_unique($api_exposed);
        $api_exposed = array_trim($api_exposed);

        $api_function = url(1) ? : 'index';

        if ($api_function == 'module') {
            $this->module();
        } else {
            if (in_array($api_function, $api_exposed)) {
                if (function_exists($api_function)) {
                    if (!$_POST) {
                        //  $data = url(2);
                        $data = url_params(true);
                        if (empty($data)) {
                            $data = url(2);
                        }
                    } else {
                        $data = $_POST;
                    }
                    $res = $api_function($data);
                    if (!defined('MW_API_HTML_OUTPUT')) {
                        print json_encode($res);
                    } else {
                        print ($res);
                    }
                } else {
                    error('The api function does not exist', __FILE__, __LINE__);
                }

                // print $api_function;
            } else {
                error('The api function is not defined in the allowed functions list');
            }
            exit();
        }
        // exit ( $api_function );
    }

    function module() {
        if (!defined('MW_API_CALL')) {
            define('MW_API_CALL', true);
        }
        $page = false;
        if (isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
            if (trim($url) == '') {

                $page = get_homepage();
                // var_dump($page);
            } else {

                $page = get_content_by_url($url);
            }
        }
        define_constants($page);
        $module_info = url_param('module_info', true);

        if ($module_info) {
            if ($_POST['module']) {
                $_POST['module'] = str_replace('..', '', $_POST['module']);
                $try_config_file = MODULES_DIR . '' . $_POST['module'] . '_config.php';
                $try_config_file = normalize_path($try_config_file, false);
                if (is_file($try_config_file)) {
                    include ($try_config_file);
                    if ($config['icon'] == false) {
                        $config['icon'] = MODULES_DIR . '' . $_POST['module'] . '.png';
                        ;
                        $config['icon'] = pathToURL($config['icon']);
                    }
                    print json_encode($config);
                    exit();
                }
            }
        }

        $admin = url_param('admin', true);

        $mod_to_edit = url_param('module_to_edit', true);
        $embed = url_param('embed', true);


        $mod_iframe = false;
        if ($mod_to_edit != false) {
            $mod_to_edit = str_ireplace('_mw_slash_replace_', '/', $mod_to_edit);
            $mod_iframe = true;
        }
        //$data = $_POST;



        if (($_POST)) {
            $data = $_POST;
        } else {
            $url = url();
            if (!empty($url)) {
                foreach ($url as $k => $v) {
                    $kv = explode(':', $v);
                    if (isset($kv[0]) and isset($kv[1])) {
                        $data[$kv[0]] = $kv[1];
                    }
                }
            }
        }






        $is_page_id = url_param('page_id', true);
        if ($is_page_id != '') {
            $data['page_id'] = $is_page_id;
        }

        $is_post_id = url_param('post_id', true);
        if ($is_post_id != '') {
            $data['post_id'] = $is_post_id;
        }

        $is_category_id = url_param('category_id', true);
        if ($is_category_id != '') {
            $data['category_id'] = $is_category_id;
        }

        $is_rel = url_param('rel', true);
        if ($is_rel != '') {
            $data['rel'] = $is_rel;

            if ($is_rel == 'page') {
                $test = get_ref_page();
                if (!empty($test)) {
                    if ($data['page_id'] == false) {
                        $data['page_id'] = $test['id'];
                    }
                }
                // p($test);
            }

            if ($is_rel == 'post') {
                // $refpage = get_ref_page ();
                $refpost = get_ref_post();
                if (!empty($refpost)) {
                    if ($data['post_id'] == false) {
                        $data['post_id'] = $refpost['id'];
                    }
                }
            }

            if ($is_rel == 'category') {
                // $refpage = get_ref_page ();
                $refpost = get_ref_post();
                if (!empty($refpost)) {
                    if ($data['post_id'] == false) {
                        $data['post_id'] = $refpost['id'];
                    }
                }
            }
        }

        $tags = false;
        $mod_n = false;

        if (isset($data['type']) != false) {
            if (trim($data['type']) != '') {
                $mod_n = $data['data-type'] = $data['type'];
            }
        }

        if (isset($data['data-type']) != false) {
            $mod_n = $data['data-type'];
        }
        if (isset($data['data-module']) != false) {
            if (trim($data['data-module']) != '') {
                $mod_n = $data['module'] = $data['data-module'];
            }
        }

        if (isset($data['module'])) {
            $mod_n = $data['data-type'] = $data['module'];
            unset($data['module']);
        }

        if (isset($data['type'])) {
            $mod_n = $data['data-type'] = $data['type'];
            unset($data['type']);
        }
        if (isset($data['data-type']) != false) {
            $data['data-type'] = rtrim($data['data-type'], '/');
            $data['data-type'] = rtrim($data['data-type'], '\\');
        }
        $has_id = false;

        foreach ($data as $k => $v) {

            if ($k == 'id') {
                $has_id = true;
            }

            if (is_array($v)) {
                $v1 = encode_var($v);
                $tags .= "{$k}=\"$v1\" ";
            } else {
                $tags .= "{$k}=\"$v\" ";
            }
        }

        if ($has_id == false) {

            $mod_n = url_title($mod_n) . '-' . date("YmdHis");
            $tags .= "id=\"$mod_n\" ";
        }

        $tags = "<module {$tags} />";



        $opts = array();
        if ($_POST) {
            $opts = $_POST;
        }
        $opts['admin'] = $admin;




        $res = parse_micrwober_tags($tags, $opts);
        $res = preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $res);


        if ($embed != false) {
            $p_index = INCLUDES_PATH . 'api/index.php';
            $p_index = normalize_path($p_index, false);
            $l = new View($p_index);
            $layout = $l->__toString();
            $res = str_replace('{content}', $res, $layout);
        }
        print $res;
        exit();
    }

    function apijs() {


        $ref_page = false;
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref_page = $_SERVER['HTTP_REFERER'];
            if ($ref_page != '') {
                $ref_page = $the_ref_page = get_content_by_url($ref_page);
                $page_id = $ref_page['id'];
                $ref_page['custom_fields'] = get_custom_fields_for_content($page_id, false);
            }
        }
        header("Content-type: text/javascript");
        define_constants($ref_page);
        $l = new View(INCLUDES_PATH . 'api' . DS . 'api.js');
        $l = $l->__toString();
        // var_dump($l);
        $l = parse_micrwober_tags($l, $options = array('parse_only_vars' => 1));
        print $l;
        exit();
    }

}

