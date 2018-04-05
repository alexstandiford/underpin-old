<?php
/**
 * Configuration class for Customizer values
 * @author: Alex Standiford
 * @date  : 2/1/2017
 */

namespace underpin\config;

use underpin\core\Config;

if(!defined('ABSPATH')) exit;

class Customizer extends Config{

  protected $action = 'customize_register';

  public function __construct($configs){
    parent::__construct($configs);
  }

  /**
   * Implements Customizer Configurations
   *
   * @param $config_key
   * @param $config_value
   *
   * @return bool
   */
  protected function implementConfiguration($config_key, $config_value){
    if($this->hasErrors()) return false;
    $section = $config_value;
    $fields = apply_filters($this->prefix("{$config_key}_customizer_fields"), $section['settings']);
    $this->loadSection($config_key, $section);
    $this->loadFields($config_key,$fields);

    return true;
  }

  /**
   * Loads the specified Customizer section
   * @return bool
   */
  private function loadSection($name, $section){
    if($this->hasErrors()) return false;
    global $wp_customize;
    $wp_customize->add_section($this->prefix($name), $section);

    return true;
  }

  /**
   * Gets the basic fields for the Customizer
   * Extra basic fields can be added via `eav_customizer_settings`, but support is limited to basic fields that require no controller
   * @return bool
   */
  private function loadFields($section,$fields){
    if($this->hasErrors()) return false;
    global $wp_customize;
    foreach($fields as $setting => $value){
      $wp_customize->add_setting($this->prefix($setting), [
        'default' => isset($value['default']) ? $value['default'] : '',
        'type'    => 'theme_mod',
      ]);
      if(!isset($value['control_type'])){
        $control_args = [
          'label'       => isset($value['label']) ? $value['label'] : '',
          'type'        => isset($value['type']) ? $value['type'] : 'text',
          'description' => isset($value['description']) ? $value['description'] : '',
          'section'     => $this->prefix($section),
          'settings'    => $this->prefix($setting),
        ];
        if($value['type'] == 'select'){
          $control_args['choices'] = $value['choices'];
        }
        $wp_customize->add_control($this->prefix($setting), $control_args);
      }
      else{
        $customizer = $value['control_type'];
        $customizer_settings = [
          'label'    => $value['label'],
          'section'  => $this->prefix($section),
          'settings' => $this->prefix($setting),
        ];
        if(isset($value['settings']) && is_array($value['settings'])){
          $customizer_settings = array_merge($customizer_settings, $value['settings']);
        }
        $wp_customize->add_control(new $customizer($wp_customize, $setting, $customizer_settings));
      }
    }

    return true;
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if($this->configExists()) return $this->throwError('Config01','New Configuration array was called incorrectly. To add to the underpin configurations, hook into underpin_customizer_config using add_filter().');
  }
}