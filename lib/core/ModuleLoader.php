<?php
/**
 * Handles loading in underpin modules
 * Used as the entry point for any Underpin module
 * @author: Alex Standiford
 * @date  : 2/5/18
 */


namespace underpin\core;


if(!defined('ABSPATH')) exit;

abstract class ModuleLoader extends Core{

  public static $modules = [];
  private $flexContentACFFields = [
    'fields'         => [],
    'location'       => [
      [
        [
          'param'    => 'block',
          'operator' => '==',
        ],
      ],
    ],
  ];
  protected $type = 'default';
  protected $moduleKey = false;
  protected $moduleName = false;
  protected $moduleControllers = [];
  protected $moduleFields = false;
  protected $rootDirectory;
  protected $rootUrl;
  protected $moduleDescription = '';

  public function __construct($file){
    $this->moduleKey = $this->sanitizeModuleKey($this->moduleName);
    parent::__construct();

    if(!$this->hasErrors()){
      $this->rootDirectory = plugin_dir_path($file);

      /**
       * Provides a way to override the fields that are used on a given block
       * Filter name is based on what block you wish to filter
       * underpin_block_name_module_fields
       */
      $module_fields = apply_filters($this->prefix($this->snakeCaseModuleKey()).'_module_fields', $this->moduleFields);
      $this->moduleFields = is_array($module_fields) ? $module_fields : [];

      //Constructs the fields array before registration
      if($this->type === 'block' || $this->type === 'flexField') $this->constructFields();

      //Register this module so that it can be used in the template loader system
      $this->registerModule();
    }
  }

  /**
   * Builds the fields array
   */
  public function constructFields(){
    $this->flexContentACFFields = wp_parse_args([
      'key'   => $this->moduleKey,
      'title' => $this->moduleName,
    ], $this->flexContentACFFields);


    //Set the value of the block this will connect with
    $this->flexContentACFFields['location'][0][0]['value'] = 'acf/'.$this->moduleKey;

    //Add the fields
    $this->flexContentACFFields['fields'] = $this->type === 'flexField' ? $this->moduleFields['sub_fields'] : $this->moduleFields;

    //Prepend default wrapper fields
    $this->flexContentACFFields['fields'] = array_merge($this->getDefaultModuleOptions(), $this->flexContentACFFields['fields']);

  }

  /**
   * Registers a template so that it can be used in the template loader system
   * Also stores the fields so ACF can loop through and create these fields in the system
   */
  private function registerModule(){
    self::$modules[$this->moduleKey] = [
      'name'           => $this->moduleName,
      'field_type'     => $this->type,
      'fields'         => $this->flexContentACFFields,
      'root_directory' => $this->rootDirectory.'templates',
    ];

    if($this->type === 'flexField' || $this->type === 'block'){
      self::$modules[$this->moduleKey]['block'] = [
        'name'        => $this->moduleKey,
        'title'       => __($this->moduleName),
        'description' => __($this->moduleDescription),
      ];
    }
  }

  public function snakeCaseModuleKey(){
    return str_replace('-', '_', $this->moduleKey);
  }

  /**
   * Sanitizes the module key for use within the loader
   *
   * @param $module_key
   *
   * @return string
   */
  public static function sanitizeModuleKey($module_key){
    return strtolower(sanitize_title_with_dashes($module_key));
  }

  /**
   * Loads in ACF fields and blocks.
   * Fires on acf/init in the underpin core file
   */
  public static function registerFieldGroups(){
    if(function_exists('acf_add_local_field_group')){
      foreach(self::$modules as $module){
        acf_add_local_field_group($module['fields']);
        acf_register_block($module['block']);
      }
    }
  }

  /**
   * Registers the default module options, which are used on all modules that use ACF
   */
  private function getDefaultModuleOptions(){
    if(function_exists('acf_add_local_field_group')){
      $default_module_settings_fields = [
        [
          'key'           => 'underpin_flex_field_color_scheme',
          'label'         => 'Color Scheme',
          'name'          => 'color_scheme',
          'type'          => 'select',
          'choices'       => [
            'light' => 'Light - Use a light background with dark text',
            'dark'  => 'Dark - Use a dark background with light text',
          ],
          'default_value' => 'light',
          'ui'            => 0,
          'ajax'          => 0,
          'multiple'      => 0,
          'allow_null'    => 0,
          'instructions'  => '',
          'required'      => 0,
          'class'         => '',
          'return_format' => '',
          'wrapper'       => [
            'width' => '100',
            'class' => '',
            'id'    => '',
          ],
          '_name'         => 'color_scheme',
          '_prepare'      => 0,
          '_valid'        => 1,
          'prepend'       => '',
          'append'        => '',
        ],
        [
          'key'           => 'underpin_flex_field_top_margin',
          'label'         => 'Top Margin',
          'name'          => 'top_margin',
          'type'          => 'select',
          'choices'       => [
            'none'   => 'None - do not add a margin above this module',
            'small'  => 'Small - add a small margin above this module',
            'medium' => 'Medium - add a medium margin above this module',
            'large'  => 'Large - add a large margin above this module',
          ],
          'default_value' => 'none',
          'ui'            => 0,
          'ajax'          => 0,
          'multiple'      => 0,
          'allow_null'    => 0,
          'instructions'  => '',
          'required'      => 0,
          'class'         => '',
          'return_format' => '',
          'wrapper'       => [
            'width' => '25',
            'class' => '',
            'id'    => '',
          ],
          '_name'         => 'top_margin',
          '_prepare'      => 0,
          '_valid'        => 1,
          'prepend'       => '',
          'append'        => '',
        ],
        [
          'key'           => 'underpin_flex_field_bottom_margin',
          'label'         => 'Bottom Margin',
          'name'          => 'bottom_margin',
          'type'          => 'select',
          'choices'       => [
            'none'   => 'None - do not add a margin below this module',
            'small'  => 'Small - add a small margin below this module',
            'medium' => 'Medium - add a medium margin below this module',
            'large'  => 'Large - add a large margin below this module',
          ],
          'default_value' => 'none',
          'ui'            => 0,
          'ajax'          => 0,
          'multiple'      => 0,
          'allow_null'    => 0,
          'instructions'  => '',
          'required'      => 0,
          'class'         => '',
          'return_format' => '',
          'wrapper'       => [
            'width' => '25',
            'class' => '',
            'id'    => '',
          ],
          '_name'         => 'bottom_margin',
          '_prepare'      => 0,
          '_valid'        => 1,
          'prepend'       => '',
          'append'        => '',
        ],
        [
          'key'           => 'underpin_flex_field_top_padding',
          'label'         => 'Top Padding',
          'name'          => 'top_padding',
          'type'          => 'select',
          'choices'       => [
            'none'   => 'None - do not add a padding above this module',
            'small'  => 'Small - add a small padding above this module',
            'medium' => 'Medium - add a medium padding above this module',
            'large'  => 'Large - add a large padding above this module',
          ],
          'default_value' => 'none',
          'ui'            => 0,
          'ajax'          => 0,
          'multiple'      => 0,
          'allow_null'    => 0,
          'instructions'  => '',
          'required'      => 0,
          'class'         => '',
          'return_format' => '',
          'wrapper'       => [
            'width' => '25',
            'class' => '',
            'id'    => '',
          ],
          '_name'         => 'top_padding',
          '_prepare'      => 0,
          '_valid'        => 1,
          'prepend'       => '',
          'append'        => '',
        ],
        [
          'key'           => 'underpin_flex_field_bottom_padding',
          'label'         => 'Bottom Padding',
          'name'          => 'bottom_padding',
          'type'          => 'select',
          'choices'       => [
            'none'   => 'None - do not add a padding below this module',
            'small'  => 'Small - add a small padding below this module',
            'medium' => 'Medium - add a medium padding below this module',
            'large'  => 'Large - add a large padding below this module',
          ],
          'default_value' => 'none',
          'ui'            => 0,
          'ajax'          => 0,
          'multiple'      => 0,
          'allow_null'    => 0,
          'instructions'  => '',
          'required'      => 0,
          'class'         => '',
          'return_format' => '',
          'wrapper'       => [
            'width' => '25',
            'class' => '',
            'id'    => '',
          ],
          '_name'         => 'bottom_padding',
          '_prepare'      => 0,
          '_valid'        => 1,
          'prepend'       => '',
          'append'        => '',
        ],

      ];
      $default_module_settings_group['fields'][0]['sub_fields'] = apply_filters($this->prefix('default_module_settings_fields'), $default_module_settings_fields, $default_module_settings_fields);
      acf_add_local_field_group($default_module_settings_group);
    }
    self::$defaultOptionsAreSet = true;
  }


  /**
   * Checks to see if the specified module is loaded
   *
   * @param $module_name - The name of the module you're trying to load in
   *
   * @return bool
   */
  public static function moduleIsLoaded($module_key){
    $module_key = self::sanitizeModuleKey($module_key);
    return array_key_exists($module_key, self::$modules);
  }


  /**
   * Checks to see if the specified module is loaded
   *
   * @param $module_name - The module directory of the specified module
   *
   * @return bool
   */
  public static function getModuleDir($module_key){
    $module_key = self::sanitizeModuleKey($module_key);
    if(self::moduleIsLoaded($module_key)){
      return self::$modules[$module_key]['root_directory'];
    }

    return false;
  }

  /**
   * Gets the root URL of the specified module
   * @param $module_key
   *
   * @return bool|string
   */
  public static function getModuleUrl($module_key){
    if(self::moduleIsLoaded($module_key)){
      return plugin_dir_url(self::getModuleDir($module_key));
    }

    return false;
  }

  /**
   * Gets the module object based on the provided key
   *
   * @param $module_key
   *
   * @return bool|mixed
   */
  public static function getModule($module_key){
    $module_key = self::sanitizeModuleKey($module_key);
    if(self::moduleIsLoaded($module_key)){
      return self::$modules[$module_key];
    }

    return false;
  }

  public static function getModules(){
    return self::$modules;
  }

  /**
   * Checks to see if the specified module is a flexible field
   *
   * @param $module
   *
   * @return bool
   */
  public static function isFlexible($module_key){
    $module_key = self::sanitizeModuleKey($module_key);
    return self::$modules[$module_key]['field_type'] == 'flexField';
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if(isset(self::$modules[$this->moduleKey])) return $this->throwError('ModuleLoader01', 'Error loading in module '.$this->moduleName.'. It appears this module has already been loaded in.');
    if($this->moduleFields && !is_array($this->moduleFields)) return $this->throwError('ModuleLoader03', 'Expected $this->moduleFields to be an array. '.gettype($this->moduleFields).' was given.');

    return null;
  }

}