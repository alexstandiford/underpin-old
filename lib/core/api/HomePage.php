<?php
/**
 * Gets the currently configured home page object
 * @author: Alex Standiford
 * @date  : 1/14/18
 */


namespace underpin\core;

use stdClass;

if(!defined('ABSPATH')) exit;

class HomePage extends core{

  public $pageID;
  public $page;

  public function __construct(){
    $this->pageID = (int)get_option('page_on_front');
    $this->page = $this->pageID > 0 ? get_post($this->pageID) : false;
    parent::__construct();
  }

	/**
	 * Converts the object to something more api-friendly
	 * @return array
	 */
  public function convertObjectToAPICompatible(){
    $page = $this->page;
    foreach($page as $key => $value){
      if(strpos($key, 'post_') !== false){
        $new_key = substr($key,5);
        $page->$new_key = $value;
        unset($page->$key);
      }
      $page->id = $page->ID;
    }

    $this->shiftValueToRenderedObject($page,'content');
    $this->shiftValueToRenderedObject($page,'title');
    $this->shiftValueToRenderedObject($page,'guid');
    $this->shiftValueToRenderedObject($page,'excerpt');

    return [$page];
  }

  public function shiftValueToRenderedObject($object,$element){
    $content = $object->$element;
    $object->$element = new stdClass();
    $object->$element->rendered = $content;
  }

  public static function getHomePageFromAPI(){
    $self = new self();
    $self->isApi = true;

    return $self->apiReturn($self->convertObjectToAPICompatible());
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    return null; //nothing to see here!
  }
}