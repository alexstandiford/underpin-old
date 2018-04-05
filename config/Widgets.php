<?php
/**
 * Configuration class for Sidebars
 * @author: Alex Standiford
 * @date  : 2/23/2017
 */

namespace underpin\config;

use underpin\core\Config;

if(!defined('ABSPATH')) exit;

class Widgets extends Config{

  protected $action = 'widgets_init';

  protected function implementConfiguration($config_key, $config_value){
    $config_value['name'] = $config_key;
    if(!isset($config_value['id'])){
      $config_value['id'] = $this->prefix(strtolower(str_replace(' ', '_', $config_value['name'])));
    };

    register_sidebar($config_value);
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}