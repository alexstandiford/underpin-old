<?php
/**
 * Constructs a single field
 * @author: Alex Standiford
 * @date  : 5/14/18
 */

namespace reactcore\lib\app\forms;

if(!defined('ABSPATH')) exit;

class Field{

  public function __construct($gf_field,$is_submit = false){
    if($is_submit == true){
      $this->isRequired = false;
      $this->visibility = true;
      $this->label = $gf_field['text'];
      $this->description = '';
      $this->type = 'submit';
      $this->id = 9000;
    }
    else{
      $this->isRequired = $gf_field->isRequired;
      $this->visibility = $gf_field->visibility;
      $this->label = $gf_field->label;
      $this->description = $gf_field->description;
      $this->type = $gf_field->type;
      $this->id = $gf_field->id;
    }
  }
}