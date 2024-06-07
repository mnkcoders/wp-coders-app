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

abstract class CodersApp {

    /**
     * @var array
     */
    private static $_apps = array();
    private static $_instance = NULL;
    private $_endpoint = '';

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
    public final function endpoint(){
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
     * @return array
     */
    public static final function apps() {
        return self::$_apps;
        //return $list ? array_keys(self::$_apps) : self::$_apps;
    }

    /**
     * 
     * @param string $action
     */
    public final function run($action = '') {
        
        $response = sprintf('run%sResponse', ucfirst(strlen($action) ? $action : 'main' ) );
        
        $input = array_merge($_GET,$_POST);
        
        return method_exists($this,$response) ?
                $this->$response( $input ) :
                $this->runErrorResponse($input,$action);
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function runErrorResponse( array $input = array() , $action = '' ) {
        
        printf('<!-- Invalid action %s -->',$action);
        
        return FALSE;
    }
    /**
     * @param array $input
     * @return bool
     */
    protected function runMainResponse( array $input = array( ) ) {

        // logics here
        printf('<!-- Main Action Response OK -->');
        
        return TRUE;
    }

    /**
     * 
     */
    public function setupAdminMenu() {
        var_dump(sprintf('%s : %s', self::class, $this->_endpoint));
    }

    /**
     * 
     */
    public function admin() {
        var_dump(sprintf('%s : %s', self::class, $this->_endpoint));
    }

    /**
     * 
     * @param string|path $plugin
     */
    public static final function register($plugin) {
        $app = self::__name($plugin);
        //$endpoint = $path[count($path)-1];
        if (!in_array($app, self::$_apps)) {
            self::$_apps[] = $app;
        }
    }

    /**
     * @param string $endpoint
     * @return \CodersApp
     */
    public static final function create($endpoint) {
        
        if (self::has($endpoint)) {
            
            $class = self::__cc($endpoint);
            
            $path = sprintf('%s/%s/application.php', preg_replace('/\\\\/', '/', WP_PLUGIN_DIR), $endpoint);
            
            if (file_exists($path)) require_once $path;
            
            self::$_instance = class_exists( $class, true) && is_subclass_of($class, self::class, true) ?
                    new $class($endpoint) :
                    null;
        }
        
        return self::$_instance;
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

        /* SETUP ROUTE | URL */
        if (is_admin()) {
            add_action('init', function () {
                //admin
                foreach (CodersApp::apps() as $app => $class) {
                    $instance = CodersApp::create($app);
                    if (!is_null($instance)) {
                        $instance->setupAdminMenu();
                    }
                }
            }, 10);
        } else {
            add_action('plugins_loaded', function () {
                //run the app register setup
                do_action('coders_app_register');
                //do some more setups here
            }, 10000);
            add_action('init', function () {
                //public
                global $wp;
                foreach (CodersApp::apps() as $app) {
                    //CodersApp::install($app);
                    $wp->add_query_var($app);
                }
                /* SETUP RESPONSE */
                add_action('template_redirect', function () {
                    global $wp_query;
                    $type = array_intersect(array_keys( $wp_query->query ), CodersApp::apps());
                    if ( count($type) ) {
                        $wp_query->set('is_404', FALSE);
                        $app = CodersApp::create($type[0]);
                        if( !is_null($app)){
                            $app->run($wp_query->get( $app->endpoint(), 'main'));
                        }
                        exit;
                    }
                }, 10);
            }, 10);
        }
    }
}

CodersApp::init();

