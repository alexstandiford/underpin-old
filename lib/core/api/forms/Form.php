<?php
/**
 * Form constructor
 * Allows us to build a form via the API
 * @author: Alex Standiford
 * @date  : 5/14/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

class Form extends Core{

  public $form;

  public function __construct($form_id){
    parent::__construct();
    if(!$this->hasErrors()){
      $this->form = \GFAPI::get_form($form_id);
      $this->fields = $this->getFields();
    }
  }

  /**
   * Loops through the GFAPI fields and extracts the necessary info to create our form
   * @return array|bool
   */
  public function getFields(){
    if($this->hasErrors()) return false;
    $fields = [];
    foreach($this->form['fields'] as $field){
      $fields[] = new Field($field);
    }
    $fields[] = new Field($this->form['button'],true);

    return $fields;
  }

  /**
   * Creates a rest endpoint to get form fields
   * @param \WP_REST_Request $req
   *
   * @return array|\WP_Error
   */
  public static function getFormFieldsFromApi(\WP_REST_Request $req){
    $form_id = $req->get_param('form_id');
    $self = new self($form_id);
    $self->isApi = true;

    return $self->apiReturn($self->fields);
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if(!class_exists("\GFAPI")) return $this->throwError('Form01','Gravity Forms is not installed Form will not render');
  }
}