<?php
/**
 * Handles template loading and inheritance of modules
 * @author: Alex Standiford
 * @date  : 2/5/18
 */


namespace underpin\Core;


if(!defined('ABSPATH')) exit;

class TemplateLoader extends Core{

  const TEMPLATE_DIRECTORY = 'templates';
  private $defaults = ['load_as_buffer' => false];
  public $type;
  private $isPartial = false;
  private $module;
  public $parent = null;
  public $location;
  public $template;
  public $postID;
  public static $buffer = false;

  public function __construct($module, $location = 'default', $type = 'default', $args = []){
    $args = wp_parse_args($args, $this->defaults);
    $this->location = (string)$location;
    $this->type = (string)$type;
    $this->module = strtolower(sanitize_title_with_dashes($module));
    self::$buffer = $args['load_as_buffer'] !== false  ? self::$buffer = '' : self::$buffer = false;
    $this->args = $args;
    parent::__construct();
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if(!ModuleLoader::moduleIsLoaded($this->module)) $this->throwError('TemplateLoader01', 'Module '.$this->module.' either does not exist, or has not been installed in this theme');

    return null;
  }

  /**
   * Finds the correct template to load for the current template
   * @return string
   */
  public function findTemplate(){
    if($this->hasErrors()) return false;
    $located = false;
    if(!$this->hasErrors()){
      $module_file = trailingslashit($this->location).$this->type.'.php';
      $file = trailingslashit($this->module).trailingslashit($this->location).$this->type.'.php';
      switch(true){
        //Check Stylesheet directory first (Child Theme)
        case file_exists(trailingslashit(get_stylesheet_directory()).trailingslashit(self::TEMPLATE_DIRECTORY).$file):
          $located = trailingslashit(get_stylesheet_directory()).trailingslashit(self::TEMPLATE_DIRECTORY).$file;
          break;
        //Check Template directory Second (Parent Theme)
        case file_exists(trailingslashit(get_template_directory()).trailingslashit(self::TEMPLATE_DIRECTORY).$file):
          $located = trailingslashit(get_template_directory()).trailingslashit(self::TEMPLATE_DIRECTORY).$file;
          break;
        //Check filtered custom template directory, if it's set.
        case (apply_filters($this->prefix('custom_template_directory_root'), '', $this) !== '' && file_exists(trailingslashit(apply_filters($this->prefix('custom_template_directory_root'), '', $this)).$file)):
          $located = trailingslashit($this->prefix('custom_template_directory_root')).$file;
          break;
        //If nothing else exists, go ahead and get the default
        default:
          $file = trailingslashit(ModuleLoader::getModuleDir($this->module)).$module_file;
          if($this->fileExists($file)) $located = $file;
          break;
      }
    }

    return $located;
  }

  /**
   * Includes the specified template
   * @return bool Returns true if the file was successfully included
   */
  public function loadTemplate(){
    if($this->hasErrors()) return false;
    $template = $this->findTemplate();
    $this->updateBuffer();
    do_action($this->prefix('before_include_template'), $this);
    include($template);
    do_action($this->prefix('after_include_template'), $this);
    $this->updateBuffer();

    return self::$buffer === false ? $this->hasErrors() == false && $template !== false : $this->updateBuffer();
  }

  private function updateBuffer(){
    global $ebl_buffer;
    if(self::$buffer !== false){
      self::$buffer = ob_get_clean();
      $ebl_buffer .= self::$buffer;
      ob_start();
    }
    return $ebl_buffer;
  }

  /**
   * Gets a template partial from the specified template
   *
   * @param string $location
   * @param string $type
   *
   * @return bool
   */
  public function getPartial($location, $type, $args = []){
    $args['load_as_buffer'] = self::$buffer;
    if($this->hasErrors()) return false;
    $this->updateBuffer();
    $partial = new self($this->module, $location, $type, $args);
    $partial->isPartial = true;
    $partial->parent = ['location' => $this->location, 'type' => $this->type];
    do_action($this->prefix('before_partial_include'), $this);
    $partial->loadTemplate();
    do_action($this->prefix('after_partial_include'), $this);
    $this->updateBuffer();

    return null;
  }

  /**
   * Loads up the Wrapper Classes for the current template
   * @return array | bool
   */
  public function getWrapperClasses($args){
    if($this->hasErrors()) return false;
    $args = apply_filters($this->prefix('template_wrapper_args'), $args, $args, $this);
    if(!is_array($args)){
      $this->throwError('templateLoader03', 'Filtered wrapper args returned a '.gettype($args).', expected array');

      return false;
    }

    return implode(' ', $args);
  }

  /**
   * Checks to see if the currently loaded template is a partial
   * @return bool
   */
  public function isPartial(){
    return $this->isPartial;
  }

  /**
   * Gets the classes for current wrapper item
   * @return bool|string
   */
  public function wrapperClasses($extra_classes = []){
    global $post;
    if($this->hasErrors()) return false;
    $default_classes = [
      $this->prefix($this->module, '-'),
      $this->prefix($this->type, '-'),
      $this->prefix($this->location, '-'),
      $this->prefix($this->module, '-').'-'.$this->location.'-'.$this->type,
      $this->prefix($this->location, '-').'-'.$this->type,
    ];

    //If the current module is a flexible module, go ahead and load in the default module classes before adding extra classes
    if(!$this->isPartial() && ModuleLoader::isFlexible($this->module)) $default_classes = wp_parse_args($default_classes, $this->getModuleDefaultClasses());

    //Load in extra classes
    $classes = wp_parse_args($default_classes, $extra_classes);
    $classes = $this->getWrapperClasses(apply_filters($this->prefix('template_wrapper_classes'), $classes, $classes, $this));
    if(!$this->isPartial() || $this->location != 'wrapper'){
      $classes = 'class="'.$classes.'"';
    }
    else{
      $classes = post_class($classes, $post);
    }

    return $classes;
  }

  private function getModuleDefaultClasses(){
    $default_fields = get_sub_field('module_settings');
    $module_default_classes = [
      $this->prefix('flex-module', '-'),
    ];
    foreach($default_fields as $field_name => $default_field){
      $module_default_classes[] = str_replace('_', '-', 'mod--'.$field_name.'-'.$default_field);
    }

    return apply_filters($this->prefix('module_default_wrapper_classes'), $module_default_classes, $module_default_classes);
  }

  /**
   * Loads a template via a static method
   *
   * @param        $module
   * @param string $location
   * @param string $type
   * @param array  $args
   */
  public static function getTemplate($module, $location = 'default', $type = 'default', $args = []){
    $self = new self($module, $location, $type, $args);
    $self->loadTemplate();
  }


  /**
   * Loads in the flexible content modules on the current template
   */
  public static function getModules(){
    if(!function_exists('the_flexible_field') || !function_exists('get_row_layout')) return;
    while(the_flexible_field('underpin_flex_content')) TemplateLoader::getTemplate(str_replace('_', '-', get_row_layout()));
  }


  /**
   * Loads a template from the API.
   *
   * @param \WP_REST_Request $req
   *
   * @return array
   */
  public static function loadTemplateFromAPI(\WP_REST_Request $req){
    $module = $req->get_param('module');
    $location = $req->get_param('location') ? $req->get_param('location') : 'default';
    $type = $req->get_param('type') ? $req->get_param('type') : 'default';
    $args = empty((array)$req->get_param('args')) ? [] : (array)$req->get_param('args');
    $args['load_as_buffer'] = true;
    $template = new self($module, $location, $type, $args);
    $template->isApi = true;
    $result['template'] = $template->loadTemplate();

    return $template->apiReturn($result);
  }
}