<?php
/**
 * A single instance of a widget for the reactcore api
 * @author: Alex Standiford
 * @date  : 5/16/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

class Widget{

  public static $key_counter = 0;

  public function __construct($id,$type){
    $this->id = $id;
    $this->type = $type;
    $widgets = get_option('widget_'.$this->type);
    $this->widgetData = $widgets[$this->id];
    $this->key = self::$key_counter++;
    unset($this->widgetData['filter']);
    unset($this->widgetData['visual']);
  }

}