<?php
/**
 * Core parent class
 * Provides the most fundamental items to the Underpin system.
 * @author: Alex Standiford
 * @date  : 2/2/18
 */

namespace underpin\core;

use underpin\config\Customizer;
use underpin\config\ImageSizes;
use underpin\config\PostTypes;
use underpin\config\Widgets;

if(!defined('ABSPATH')) exit;

class Underpin{

  /**
   * Creates custom image sizes
   * @var array
   */
  private static $image_sizes = [
    'lazy-load' => [
      'width'  => 50,
      'height' => 50,
    ],
  ];

  /**
   * Creates Customizer sections and fields
   * @var array
   */
  private static $customizer = [
    //    'contact_information' => [
    //      'title'       => 'Contact Information',
    //      'description' => 'Customize Your Contact Info',
    //      'priority'    => 50,
    //      'capability'  => 'edit_theme_options',
    //      'settings'    => [
    //        'company_logo'  => [
    //          'control_type' => 'WP_Customize_Image_Control',
    //          'default'      => '',
    //          'label'        => 'Upload your logo',
    //        ],
    //      ],
    //    ],
  ];

  private static $widgets = [
    //    'Sidebar' => [
    //      'description'   => '',
    //      'class'         => 'Sidebars-content',
    //      'before_widget' => '<div>',
    //      'after_widget'  => '</div>',
    //      'before_title'  => '<h3>',
    //      'after_title'   =>   '</h3>',
    //    ]
  ];

  private static $post_types = [
    //    'beers' => [
    //      'name'          => 'Beers',
    //      'singular_name' => 'Beer',
    //      'menu_icon'     => 'icons/beer-icon.png',
    //      'supports'      => ['title', 'editor', 'thumbnail'],
    //      'taxonomies'    => [
    //        'style'   => [
    //          'hierarchical' => true,
    //          'meta_box_cb' => 'metaBoxAsSelect',
    //          'rewrite'     => ['slug' => 'style'],
    //          'labels'       => [
    //            'label'       => ' Style',
    //          ],
    //        ],
    //        'pairing' => [
    //          'label'         => 'Pairings',
    //        ],
    //        'tags'    => [
    //          'name'          => 'Tags',
    //          'singular_name' => 'tag',
    //          'label'         => 'Tags',
    //        ],
    //      ],
    //    ],
  ];

  /**
   * All of the non-template functionality includes to grab. Pulls from the app directory.
   * @var array
   */
  private static $core_files = [
    'Core.php',                    //Core Abstract Class. Houses Underpin-specific functions, and error handling
    'Config.php',                  //Core Config Class. Used to implement configuration classes
    'ModuleLoader.php',            //Core Module Loader Class. Used to implement modules
    'TemplateLoader.php',          //Core Template Loader Class. Used to implement module templates
    'api/ACFParser.php',           //Handles Gutenberg-related content parsing for REST
    'api/BlogInfo.php',            //Spits out blog information for REST
    'api/HomePage.php',            //Spits out home page info for REST
    'api/NavMenu.php',             //Spits out Nav Menu for REST
    'api/sidebar/Sidebar.php',     //Spits out Sidebar data for REST
    'api/sidebar/Widget.php',      //Spits out Widget data for REST
    'api/forms/Field.php',         //Handles Form Field data using for REST
    'api/forms/Form.php',          //Handles Form data using REST
    'api/routes/Routes.php',       //Gets site structure using REST
  ];

  /**
   * All of the non-template functionality includes to grab. Pulls from the app directory.
   * @var array
   */
  private static $config_files = [
    'ImageSizes.php',      //Integrates image sizes
    'Customizer.php',      //Integrates Customizer Fields
    'Widgets.php',         //Integrates Sidebars
    'PostTypes.php',       //Integrates Custom Post Types
  ];

  private $rest_endpoints = [
    [
      'route'   => 'template/get',
      'version' => 'v1',
      'args'    => [
        'methods'  => 'GET',
        'callback' => '\underpin\Core\TemplateLoader::loadTemplateFromAPI',
      ],
    ],
    [
      'route'   => 'acf/data/(?P<id>[\d]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => 'GET',
        'callback' => '\underpin\Core\ACFParser::getAcfFieldsForApiByID',
      ],
    ],
    [
      'route'   => 'acf/data/(?P<post_type>[\w-_]+)/(?P<slug>[\w-_]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => 'GET',
        'callback' => '\underpin\Core\ACFParser::getAcfFieldsForApiBySlug',
      ],
    ],
    [
      'route'   => 'menu/(?P<slug>[\w-_]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\NavMenu::getMenuFromApi',
      ],
    ],
    [
      'route'   => 'routes/',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\Routes::getRoutesFromApi',
      ],
    ],
    [
      'route'   => 'menu/(?P<id>[\d]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\NavMenu::getMenuFromApi',
      ],
    ],
    [
      'route'   => 'menu/location/(?P<slug>[\w-_]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\NavMenu::getMenuByLocationFromApi',
      ],
    ],
    [
      'route'   => 'home-page/get',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\HomePage::getHomePageFromApi',
      ],
    ],
    [
      'route'   => 'blog-info/get',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\BlogInfo::getBlogInfoFromApi',
      ],
    ],
    [
      'route'   => 'form/(?P<form_id>[\d]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\forms\Form::getFormFieldsFromApi',
      ],
    ],
    [
      'route'   => 'sidebar/(?P<sidebar>[\w-_]+)',
      'version' => 'v2',
      'args'    => [
        'methods'  => ['GET'],
        'callback' => '\underpin\Core\sidebar\Sidebar::getSidebarWidgetsForApi',
      ],
    ],
  ];

  /**
   * The singleton instance of the theme initialization
   * @var null
   */
  private static $instance = null;

  /**
   * init constructor. Set to empty and private. Just keeps other things from poking around in here indirectly.
   */
  private function __construct(){
  }


  /**
   * Initializes the theme
   * @return self
   */
  public static function init($dir){
    if(!isset(self::$instance)){
      self::$instance = new self();
      do_action('underpin');
      self::$instance = new self;
      self::$instance->_defineConstants($dir);
      do_action('underpin_init');
      self::$instance->_defineThemeSupports();
      self::$instance->_includeEach(UNDERPIN_CORE_PATH, self::$core_files);
      self::$instance->_includeAutoloader();
      do_action('underpin_after_core_init');
      self::$instance->_includeEach(UNDERPIN_CONFIG_PATH, self::$config_files);
      self::$instance->_loadCoreConfigurations();
      do_action('underpin_load_configurations');

      /**
       * Registers ACF fields for modules
       */
      add_action('acf/init', ['underpin\core\ModuleLoader', 'registerFieldGroups']);

      /**
       * Loads the compiled JS file
       */
      add_action('wp_enqueue_scripts', [self::$instance, '_loadScripts']);
      add_action('admin_enqueue_scripts', [self::$instance, '_loadScripts']);

      /**
       * Loads the compiled CSS file
       */
      add_action('wp_enqueue_scripts', [self::$instance, '_loadStyles']);

      /**
       * Removes ACF from the admin area
       */
      if(!is_super_admin()) add_filter('acf/settings/show_admin', '__return_false');

      /**
       * Registers RESTful API endpoints related to theme
       */
      add_action('rest_api_init', [self::$instance, 'registerRestEndpoints']);
      do_action('underpin_after_init');
    }

    return self::$instance;
  }

  /**
   * Loads the Composer Autoloader
   */
  private function _includeAutoloader(){
    if(file_exists(UNDERPIN_COMPOSER_PATH.'autoload.php')) require_once(trailingslashit(UNDERPIN_COMPOSER_PATH).'autoload.php');
  }

  /**
   * Registers REST endpoints
   */
  //TODO: Expand this to run as a config class
  public function registerRestEndpoints(){
    foreach($this->rest_endpoints as $rest_endpoint){
      register_rest_route('underpin/'.$rest_endpoint['version'], $rest_endpoint['route'], $rest_endpoint['args']);
    }
  }

  /**
   * Loads in the JavaScript files and passes script values
   */
  public function _loadScripts(){
    $script_values = [];
    $script_values = apply_filters('underpin_script_values', $script_values, $script_values);
    wp_register_script('underpin_script', get_stylesheet_directory_uri().'/build/assets/bundle.js', ['jquery'], false, true);
    wp_localize_script('underpin_script', 'Underpin', $script_values);
    wp_enqueue_script('underpin_script');
  }

  public function _defineThemeSupports(){
    add_theme_support('post-thumbnails');
  }

  /**
   * Loads in the CSS file
   */
  public function _loadStyles(){
    $css_url = get_stylesheet_directory_uri().'/build/assets/style.css';

    if(is_admin()){

      /**
       * underpin_enable_editor_styles
       * Allows theme developers to force-disable the stylesheet to display in Gutenberg
       */
      if(apply_filters('underpin_enable_editor_styles',true)){
        /**
         * underpin_admin_styles_url
         * Allows theme developers to specify the editor stylesheet
         * Useful in situations where you need to compile a different stylesheet for the admin interface
         */
        $css_url = apply_filters('underpin_admin_styles_url', $css_url);

        add_theme_support('editor-styles');
        add_editor_style($css_url);
      }
    }
    else{
      wp_enqueue_style('underpin_style', $css_url);
    }
  }


  /**
   * Defines the constants related to the theme
   * @return void
   */
  private function _defineConstants($dir){
    $dir = untrailingslashit($dir);
    define('UNDERPIN_ROOT_DIR', $dir);
    define('UNDERPIN_ROOT_URL', network_site_url('wp-content/mu-plugins/'));
    define('UNDERPIN_CONFIG_PATH', $dir.'/underpin/config/');
    define('UNDERPIN_LIB_PATH', $dir.'/underpin/lib/');
    define('UNDERPIN_CORE_PATH', $dir.'/underpin/lib/core/');
    define('UNDERPIN_COMPOSER_PATH', $dir.'/vendor/');
    define('UNDERPIN_ASSETS_DIR', get_stylesheet_directory().'/assets/');
    define('UNDERPIN_PREFIX', 'underpin');
  }

  private function _loadCoreConfigurations(){
    new imageSizes(self::$image_sizes);
    new Customizer(self::$customizer);
    new Widgets(self::$widgets);
    new PostTypes(self::$post_types);
  }

  private function _includeEach($dir, $array){
    foreach($array as $file){
      require_once($dir.$file);
    }
  }

}