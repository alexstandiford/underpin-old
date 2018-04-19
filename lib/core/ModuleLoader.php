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
  private static $flexContentACFFields = [
    'key'            => 'underpin_group',
    'title'          => 'Underpin Group',
    'fields'         => [
      [
        'key'     => 'underpin_flex_content',
        'label'   => 'Underpin',
        'name'    => 'underpin_flex_content',
        'type'    => 'flexible_content',
        'layouts' => [],
      ],
    ],
    'location'       => [
      [
        [
          'param'    => 'post_type',
          'operator' => '==',
          'value'    => 'page',
        ],
      ],
    ],
    'hide_on_screen' => array(
      0 => 'the_content',
    ),
  ];
  protected $type = 'default';
  protected $moduleKey = false;
  protected $moduleName = false;
  protected $moduleControllers = [];
  protected $moduleFields = false;
  protected $rootDirectory;
  protected $rootUrl;
  private static $defaultOptionsAreSet = false;

  public function __construct($file){
    $this->moduleKey = $this->sanitizeModuleKey($this->moduleName);
    parent::__construct();

    if(!$this->hasErrors()){
      $this->rootDirectory = plugin_dir_path($file);
      //If we're dealing with a module that supports ACF flexible content, set it up
      if($this->type == 'flexField'){
        if(!self::$defaultOptionsAreSet) $this->registerDefaultModuleOptions();
        $this->moduleFields['name'] = $this->moduleKey;
      }

      //moduleFields are filtered via theme_prefix + _module_key + _module_fields
      $this->moduleFields = apply_filters($this->prefix(str_replace('-', '_', $this->moduleKey).'_module_fields'), $this->moduleFields);
      self::$modules[$this->moduleKey] = [
        'name'           => $this->moduleName,
        'fields'         => $this->moduleFields,
        'field_type'     => $this->type,
        'root_directory' => $this->rootDirectory.'templates',
      ];

      if($this->type == 'flexField'){
        self::$flexContentACFFields['fields'][0]['layouts'][$this->moduleFields['key']] = $this->moduleFields;
        //Add default wrapper fields
        array_unshift(self::$flexContentACFFields['fields'][0]['layouts'][$this->moduleFields['key']]['sub_fields'], [
          'key'     => $this->moduleFields['key'].'_default_options',
          'label'   => 'Module Options',
          'name'    => 'default_options',
          'type'    => 'clone',
          'clone'   => [
            0 => 'module_settings',
          ],
          'display' => 'seamless',
          'layout'  => 'block',
        ]);
      }
    }
  }

  /**
   * Sanitizes the module key for use within the loader
   * @param $module_key
   *
   * @return string
   */
  public static function sanitizeModuleKey($module_key){
    return strtolower(sanitize_title_with_dashes($module_key));
  }

  /**
   * Loads in ACF fields.
   */
  public static function registerFlexFieldGroup(){
    if(function_exists('acf_add_local_field_group')) acf_add_local_field_group(self::$flexContentACFFields);
  }


  /**
   * Loads in ACF fields.
   */
  public static function registerFieldGroups(){
    if(function_exists('acf_add_local_field_group')){
      foreach(self::$modules as $module){
        if($module['field_type'] === 'default') acf_add_local_field_group($module['fields']);
      }
    }
  }

  /**
   * Registers the default module options, which are used on all modules that use ACF
   */
  private function registerDefaultModuleOptions(){
    if(function_exists('acf_add_local_field_group')){
      $default_module_settings_group = [
        'key'                   => 'underpin_default_module_settings_group',
        'title'                 => 'Field Group',
        'fields'                => [
          [
            'key'    => 'module_settings',
            'label'  => 'Module Settings',
            'name'   => 'module_settings',
            'type'   => 'group',
            'layout' => 'block',
          ],
        ],
        'location'              => [
          [
            [
              'param'    => 'post_type',
              'operator' => '==',
              'value'    => 'post',
            ],
            [
              'param'    => 'post_type',
              'operator' => '!=',
              'value'    => 'post',
            ],
          ],
        ],
        'menu_order'            => 0,
        'position'              => 'normal',
        'style'                 => 'default',
        'label_placement'       => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen'        => '',
        'active'                => 1,
        'description'           => '',
      ];
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