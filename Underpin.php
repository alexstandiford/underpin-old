<?php
/**
 * Core parent class
 * Provides the most fundamental items to the Underpin system.
 * @author: Alex Standiford
 * @date  : 2/2/18
 */

namespace underpin\core;

use DOMDocument;
use underpin\admin\ColorSchemeFactory;
use underpin\admin\CssSynchronizer;
use underpin\admin\CssUpdater;
use underpin\config\Customizer;
use underpin\config\ImageSizes;
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

  /**
   * All of the non-template functionality includes to grab. Pulls from the app directory.
   * @var array
   */
  private static $core_files = [
    'Core.php',                    //Core Abstract Class. Houses Underpin-specific functions, and error handling
    'Config.php',                  //Core Config Class. Used to implement configuration classes
    'ModuleLoader.php',            //Core Module Loader Class. Used to implement modules
    'TemplateLoader.php',          //Core Template Loader Class. Used to implement module templates
  ];


  /**
   * All of the admin-specific includes to grab. Pulls from the admin directory.
   * @var array
   */
  private static $admin_files = [
    'ColorSchemeFactory.php',      //Loads in the color scheme customizations
    'CssUpdater.php',              //Loads in the CSS Updater
    'CssSynchronizer.php',         //Handles CSS file syncing between theme and site css files
  ];


  /**
   * All of the non-template functionality includes to grab. Pulls from the app directory.
   * @var array
   */
  private static $config_files = [
    'ImageSizes.php',      //Integrates image sizes
    'Customizer.php',      //Integrates Customizer Fields
    'Widgets.php',         //Integrates Sidebars
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
      self::$instance->_includeEach(UNDERPIN_LIB_PATH.'admin/', self::$admin_files);
      self::$instance->_includeAutoloader();
      do_action('underpin_after_core_init');
      self::$instance->_includeEach(UNDERPIN_CONFIG_PATH, self::$config_files);
      self::$instance->_loadCoreConfigurations();
      do_action('underpin_load_configurations');

      /**
       * Registers the flexible field group that houses all module ACF fields
       */
      add_action('acf/init', ['underpin\core\ModuleLoader', 'registerFlexFieldGroup']);

      /**
       * Registers ACF fields for modules
       */
      add_action('acf/init', ['underpin\core\ModuleLoader', 'registerFieldGroups']);

      /**
       * Loads the compiled JS file
       */
      add_action('wp_enqueue_scripts', [self::$instance, '_loadScripts']);

      /**
       * Loads the compiled CSS file
       */
      add_action('wp_enqueue_scripts', [self::$instance, '_loadStyles']);

      /**
       * Modifies uploaded image HTML to implement lazy-loaded image markup on-upload
       */
      add_action('wp_get_attachment_image_attributes', [self::$instance, '_buildLazyLoadSupport'], 10, 2);

      /**
       * Modifies image HTML to implement lazy-loaded images
       */
      add_filter('the_content', [self::$instance, '_buildLazyLoadContentSupport'], 15);

      /**
       * Registers RESTful API endpoints related to theme
       */
      add_action('rest_api_init', [self::$instance, 'registerRestEndpoints']);

      /**
       * Runs the updater to recompile CSS when the customizer is saved
       */
      add_action('customize_save', 'underpin\admin\CssUpdater::runUpdater');

      /**
       * Handle preview CSS for color scheme customizer
       */
      add_action('wp_head', [self::$instance, 'updatePreviewCss']);

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
    register_rest_route('underpin/v1', '/template/get', array(
      'methods'  => 'GET',
      'callback' => '\underpin\Core\TemplateLoader::loadTemplateFromAPI',
    ));
  }

  /**
   * Adds the extra data attributes to work with lazy loader
   *
   * @param $attributes
   *
   * @return mixed
   */
  public static function _buildLazyLoadSupport($attributes, $attachment){
    $attributes['data-src'] = $attributes['src'];
    $attributes['src'] = wp_get_attachment_image_url($attachment->ID, 'lazy-load');
    $attributes['class'] .= " mod--lazyload";
    unset($attributes['srcset']);

    return $attributes;
  }

  /**
   * Implements lazy loading support for WYSIWYG content
   *
   * @param $content
   *
   * @return string
   */
  public static function _buildLazyLoadContentSupport($content){
    if(!is_singular() || !$content) return $content; //bail early if this isn't a single blog post
    libxml_use_internal_errors(true);
    global $wpdb;
    $post = new DOMDocument();
    $post->loadHTML($content);
    //Get the images
    $images = $post->getElementsByTagName('img');

    foreach($images as $img){
      $src = $img->getAttribute('src');
      $img->setAttribute('data-src', $src);
      $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $src));
      if(isset($attachment[0])) $img->setAttribute('src', wp_get_attachment_image_url($attachment[0], 'lazy-load'));
      $imgClass = $img->getAttribute('class');
      $img->setAttribute('class', $imgClass.' mod--lazyload');
    };

    return $post->saveHTML();
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
    if(file_exists(CssUpdater::getCssDirFile())){
      $css_url = CssUpdater::getCssFileUrl();
      //Sync the CSS file if the original file was updated recently
      CssSynchronizer::syncCssFile();
    }
    else{
      $css_url = get_stylesheet_directory_uri().'/build/assets/style.css';
    }
    wp_enqueue_style('underpin_style', $css_url);
  }

  /**
   * Updates the preview CSS
   */
  //TODO: Improve this function to actually use SCSS compiler instead of a basic grep
  public function updatePreviewCss(){
    if(!empty ($GLOBALS['wp_customize'])){
      $color_scheme = new ColorSchemeFactory();
      $css = file_exists(CssUpdater::getCssDirFile()) ? CssUpdater::getCssDirFile() : get_stylesheet_directory().'/build/assets/style.css';
      $css = file_get_contents($css);
      foreach($color_scheme->splitValues() as $selector => $old_value){
        $new_value = get_theme_mod('underpin_color_scheme_'.$selector);
        if($old_value != $new_value){
          $css = str_replace($old_value, $new_value, $css);
        }
      }
      echo '<style id="underpin-color-scheme">'.$css.'</style>';
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

  public function setupCustomizer(){
    $color_scheme = new ColorSchemeFactory();
    add_filter('underpin_customizer_config', [$color_scheme, 'addCustomizerFields']);
  }

  private function _loadCoreConfigurations(){
    $this->setupCustomizer();
    new imageSizes(self::$image_sizes);
    new Customizer(self::$customizer);
    new Widgets(self::$widgets);
  }

  private function _includeEach($dir, $array){
    foreach($array as $file){
      require_once($dir.$file);
    }
  }

}