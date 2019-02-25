<?php
/**
 * Loads up the necessary header components
 * @author: Alex Standiford
 * @date  : 5/10/18
 */


namespace underpin\core;


if(!defined('ABSPATH')) exit;

class BlogInfo extends Core{

  public $info;
  public $options;

  public function __construct(){

    $this->info = new \stdClass();
    $this->info->title = get_bloginfo('title');
    $this->info->description = get_bloginfo('description');
    parent::__construct();
  }

  /**
   * REST Callback
   * @return array|\WP_Error
   */
  public static function getBlogInfoFromApi(){
    $self = new self();
    $self->isApi = true;

    return $self->apiReturn($self->info);
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}