<?php
/**
 * Handles Sidebar endpoint display
 * @author: Alex Standiford
 * @date  : 5/16/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

class Sidebar extends Core{

  public $result = [];

  public function __construct($sidebar_id){
    $widgets = wp_get_sidebars_widgets();
    $widgets = isset($widgets[$sidebar_id]) ? $widgets[$sidebar_id] : [];
    foreach($widgets as $widget){
      $widget = explode('-', $widget);
      $type = $widget[0];
      $id = $widget[1];
      $this->result[] = new Widget($id, $type);
      parent::__construct();
    }
  }

  /**
   * Gets the sidebar widgets as an array
   * @param \WP_REST_Request $req
   *
   * @return array|\WP_Error
   */
  public static function getSidebarWidgetsForApi(\WP_REST_Request $req){
    $sidebar = $req->get_param('sidebar');
    $self = new self($sidebar);
    $self->isApi = true;

    return ($self->apiReturn($self->result));
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}