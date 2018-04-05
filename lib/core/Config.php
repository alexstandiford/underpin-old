<?php
/**
 * Houses setup for configuration classes.
 * @author: Alex Standiford
 * @date  : 2/2/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

abstract class Config extends Core{

  protected $action = 'underpin_after_init';
  private static $config = [];

  public function __construct($configs){
    $class_name = $this->className();
    parent::__construct();
    if(!in_array($class_name, Config::$config)){
      add_action($this->action, [$this, 'implementConfigurations']);
      Config::$config[$class_name] = apply_filters($this->prefix(strtolower($class_name).'_config'), $configs);
    }
  }

  /**
   * Loops through each configuration item and sets up the configuration data accordingly
   */
  public function implementConfigurations(){
    $class_name = $this->className();
    foreach(Config::$config[$class_name] as $config_key => $config_value){
      $this->implementConfiguration($config_key, $config_value);
    }
  }

  /**
   * Getter function for the configuration array
   * This allows us to access configuration values
   * without allowing end users to actually modify the array improperly
   * @param $class_name
   *
   * @return mixed
   */
  public static function getConfigArray($class_name){
    return self::$config[$class_name];
  }

  /**
   * Checks to see if this configuration has already ran
   *
   * @param $class_name
   *
   * @return bool
   */
  public function configExists($class_name = false){
    if(!$class_name) $class_name = $this->className();

    return isset(Config::$config[$class_name]);
  }

  /**
   * Adds a new configuration, or merges into the existing configuration
   * Generally, this is used in tandem with a hook, or callback
   *
   * @param $config_array - The configuration array, passed by your hook
   * @param $config_to_add - The name of the configuration you wish to add
   * @param $options_to_add - The array of options you want to add to the configuration
   */
  public static function merge($config_array, $config_to_add, $options_to_add){
    if(isset($config_array[$config_to_add])){
      $config_array[$config_to_add]['settings'] = array_merge($config_array[$config_to_add]['settings'], $options_to_add['settings']);
    }
    else{
      $config_array[$config_to_add] = $options_to_add;
    }

    return $config_array;
  }

  protected abstract function implementConfiguration($config_key, $config_value);
}