<?php
/**
 * Configuration Class for Image Sizes
 * @author: Alex Standiford
 * @date  : 2/17/2017
 */

namespace underpin\config;

use underpin\core\Config;

if(!defined('ABSPATH')) exit;

//TODO: Document this class

class ImageSizes extends Config{

  public function __construct($configs){
    parent::__construct($configs);
  }

  protected function implementConfiguration($config_key,$config_value){
    if(!isset($config_value['crop'])){
      $config_value['crop'] = false;
    }
    add_image_size($config_key, $config_value['width'], $config_value['height'], $config_value['crop']);
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}