<?php

defined('ABSPATH') or die;
/* * *****************************************************************************
 * Plugin Name: Coders App
 * Plugin URI: https://coderstheme.org
 * Description: Coders App Framework
 * Version: 0.1
 * Author: Coder01
 * Author URI: 
 * License: GPLv2 or later
 * Text Domain: coders_app
 * Domain Path: lang
 * Class: CodersApp
 * 
 * @author Coder01 <coder01@mnkcoder.com>
 * **************************************************************************** */

/**
 * CodersApp Application Bootstrapper
 */
abstract class CodersApp {

    /**
     * @var array
     */
    private static $_apps = array();

    /**
     * @var array
     */
    private static $_extensions = array();

    /**
     * @var string
     */
    private $_endpoint = '';
    /**
     * 
     * @var array
     */
    private $_require = array();

    /**
     * 
     */
    protected function __construct($root) {

        $this->_endpoint = self::__name($root);
    }

    /**
     * @param string $string
     * @return String
     */
    private static final function __cc($string) {
        return preg_replace('/\s/', '', ucwords(preg_replace('/[\-\_]/', ' ', $string)));
    }

    /**
     * 
     * @param string $plugin
     * @return string
     */
    private static final function __name($plugin) {
        $path = explode('/', preg_replace('/\\\\/', '/', $plugin));
        return $path[count($path) - 1];
    }

    /**
     * @return string
     */
    public final function __toString() {
        return $this->endpoint();
    }

    /**
     * 
     * @return string
     */
    public final function endpoint() {
        return $this->_endpoint;
    }

    /**
     * @param string $app
     * @return boolean
     */
    private static final function has($app) {
        return strlen($app) && in_array($app, self::$_apps);
        //return strlen($app) && array_key_exists($app, self::$_apps);
    }
    /**
     * @param string $ext
     * @return CodersApp
     */
    protected final function require( $ext ){
        if( !in_array($ext, $this->_require)){
            $this->_require[] = $ext;
        }
        return $this;
    }
    /**
     * @return bool
     */
    protected final function validate(){

        foreach( $this->_require as $ext ){
            if(strlen($ext) && !in_array($ext, self::extensions())){
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @return array
     */
    public static final function apps() {
        return self::$_apps;
    }

    /**
     * @return array
     */
    public static final function extensions() {
        return self::$_extensions;
    }

    /**
     * 
     * @param string $action
     */
    public final function run($action = '') {

        $response = sprintf(
                is_admin() ? 'runAdmin%s' : 'run%s',
                ucfirst(strlen($action) ? $action : 'main'));

        $input = array_merge($_GET, $_POST);

        return method_exists($this, $response) ?
                $this->$response($input) :
                (is_admin() ? $this->runAdminError($input) : $this->runError($input, $action));
    }

    /**
     * @param array $input
     * @return bool
     */
    protected function runError(array $input = array(), $action = '') {

        printf('<!-- Invalid action %s -->', $action);

        return FALSE;
    }

    /**
     * @param array $input
     */
    protected function runAdminError(array $input = array()) {
        printf('<h1>Main Admin Error Page</h1>');
        var_dump($input);
        return TRUE;
    }

    /**
     * @param array $input
     * @return bool
     */
    protected function runMain(array $input = array()) {
        printf('<!-- runMain %s -->', $this->endpoint());
        return TRUE;
    }

    /**
     * 
     * @param array $input
     */
    protected function runAdminMain(array $input = array()) {
        printf('<!-- runMain %s -->', $this->endpoint());
        return TRUE;
    }

    /**
     * 
     */
    public function registerAdminMenu() {

        $menu = $this->adminMenu();

        $app = $this;

        add_action('admin_menu', function () use ($menu, $app) {
            $endpoint = $menu['slug'];
            if (strlen($menu['parent']) === 0) {
                add_menu_page(
                        $menu['name'], $menu['title'], $menu['capability'], $endpoint,
                        array($app, 'run'), $menu['icon'], $menu['position']);

                $submenu = array_key_exists('children', $menu) ? $menu['children'] : array();

                foreach ($submenu as $option) {
                    $context = $option['slug'];
                    add_submenu_page($endpoint, $option['name'], $option['title'], $option['capability'],
                            $endpoint . '-' . $context, array($app, 'run', $context), $option['position']);
                }
            } else {
                //append to other existing menus
                add_submenu_page(
                        $menu['parent'], $menu['name'], $menu['title'], $menu['capability'],
                        $endpoint, array($app, 'run'), $menu['position']);
            }
        });
    }

    /**
     * 
     */
    protected function adminMenu() {
        return array(
            //framework menu setup
            //'parent' => '',
            'name' => __('Coders App', 'coders_app'),
            'title' => __('Coders App', 'coders_app'),
            'capability' => 'administrator',
            'slug' => 'coders-app',
            'icon' => 'dashicons-grid-view',
            //'children' => array(),
            'position' => 100,
        );
    }

    /**
     * 
     * @param string|path $app
     */
    protected static final function add($app) {
        //$app = self::__name($plugin);
        //$endpoint = $path[count($path)-1];
        if (!in_array($app, self::$_apps)) {
            self::$_apps[] = $app;
        }
    }

    /**
     * @param string $ext
     */
    protected static final function register($ext) {
        //$ext = self::__name($plugin);
        if (!in_array($ext, self::$_extensions)) {
            self::$_extensions[] = $ext;
        }
    }

    /**
     * @param string $endpoint
     * @return \CodersApp
     */
    public static final function create($endpoint) {

        if (self::has($endpoint)) {

            $class = self::__cc($endpoint);

            $path = sprintf('%s/%s/application.php',
                    preg_replace('/\\\\/', '/', WP_PLUGIN_DIR),
                    $endpoint);

            if (file_exists($path))
                require_once $path;

            return class_exists($class, true) && is_subclass_of($class, self::class, true) ?
                    new $class($endpoint) :
                    null;
        }
    }

    /**
     * @param string $plugin
     */
    public static function install($plugin) {

        $app = self::__name($plugin);
        global $wp_rewrite;
        //global $wp_rewrite, $wp;
        $wp_rewrite->add_endpoint($app, EP_ROOT);
        //$wp->add_query_var($app);
        $wp_rewrite->add_rule("^/$app/?$", 'index.php?' . $app . '=$matches[1]', 'top');
        $wp_rewrite->flush_rules();

        if (is_admin()) {
            add_action('admin_notices', function () use ($app) {
                printf('<div class="notice"><p><strong>%s</strong> added to URL rewrite rules.</p></div>', $app);
            });
        }
    }

    /**
     * 
     * @param string $plugin
     */
    public static function uninstall($plugin) {
        $app = self::__name($plugin);

        add_action('admin_notices', function () use ($app) {
            printf('<div class="notice"><p><strong>%s</strong> uninstalled.</p></div>', $app);
        });
    }

    /**
     * 
     */
    public static final function init() {

        if (defined('CODERS_APP_ROOT')) {
            return;
        }
        
        //setup framework root paths
        define('CODERS_APP_ROOT', preg_replace('/\\\\/', '/', __DIR__));

        $extensions = [];
        $apps = [];
        
        //load all dependencies before proceeding with the apps
        do_action('register_coder_extensions', $extensions);
        foreach( $extensions as $ext ){
            self::register(self::__name( $ext ) );
        }
        //var_dump(self::extensions());
        //run the app register setup
        do_action('register_coder_app', $apps);
        foreach( $apps as $app ){
            self::add(self::__name($app));
        }
        var_dump(self::apps());
        die;

        /* SETUP ROUTE | URL */
        if (is_admin()) {
            //admin
            foreach (CodersApp::apps() as $app) {
                $instance = CodersApp::create($app);
                if (!is_null($instance)) {
                    $instance->registerAdminMenu();
                }
            }
        }
        else {
            //public
            global $wp;
            foreach (CodersApp::apps() as $app) {
                $wp->add_query_var($app);
            }
            /* SETUP RESPONSE */
            add_action('template_redirect', function () {
                global $wp_query;
                $type = array_intersect(array_keys($wp_query->query), CodersApp::apps());
                if (count($type)) {
                    $wp_query->set('is_404', FALSE);
                    $app = CodersApp::create($type[0]);
                    if (!is_null($app)) {
                        $app->run($wp_query->get($app->endpoint(), 'main'));
                    }
                    exit;
                }
            }, 10);
        }
    }
}

add_action('init', function () {
    CodersApp::init();
}, 100);

