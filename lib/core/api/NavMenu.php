<?php
/**
 * Gets a nav menu
 * @author: Alex Standiford
 * @date  : 1/13/18
 */

namespace underpin\core;

if(!defined('ABSPATH')) exit;

class NavMenu extends core{

  public $menu;
  public $menus;
  public $menuSlug;
  public $menuId;

  public function __construct($menu_slug_or_id = false, $type = 'menu'){
    if($type == 'location') $menu_slug_or_id = $this->getMenuIdFromLocation($menu_slug_or_id);

    if(!$menu_slug_or_id){
      $this->menus = $this->getAllMenus();
    }
    else{
      $this->menus = $this->getMenuItems($menu_slug_or_id);
    }
    parent::__construct();
  }

  /**
   * Gets the menu ID from the specified location
   *
   * @param $location
   *
   * @return mixed
   */
  public static function getMenuIdFromLocation($location){
    $locations = get_theme_mod('nav_menu_locations');

    return $locations[$location];
  }

  /**
   * Gets all menu items
   * @return array
   */
  public function getAllMenus(){
    $nav_menus = wp_get_nav_menus();
    $menus = [];
    foreach($nav_menus as $menu){
      $menus[$menu->slug] = $this->getMenuItems($menu->slug);
    }

    return $menus;
  }

  public function getMenuItems($slug,$value = 0){
    $parent_menu_items = new \WP_Query([
      'order'       => 'ASC',
      'orderby'     => 'menu_order',
      'post_type'   => 'nav_menu_item',
      'post_status' => 'publish',
      'output'      => ARRAY_A,
      'meta_query'  => [
        [
          'key'     => '_menu_item_menu_item_parent',
          'value'   => $value,
          'compare' => '=',
        ],
      ],
      'output_key'  => 'menu_order',
      'nopaging'    => true,
    ]);

    $result = [];

    foreach($parent_menu_items->posts as $menu_item){
      $post_meta = get_post_meta($menu_item->ID);
      $menu_item_id = $menu_item->ID;

      if($post_meta['_menu_item_type'][0] == 'custom'){
        $menu_item->url = $post_meta['_menu_item_url'][0];
      }
      else{
        $menu_item = get_post($post_meta['_menu_item_object_id'][0]);
        $menu_item->url = get_permalink($menu_item);
      }
      if(strpos($menu_item->url,get_home_url()) !== false){
        $menu_item->url = str_replace(get_home_url(),'',$menu_item->url);
      }
      $menu_item->id = $menu_item_id;
      $result[] = $menu_item;

      $menu_item->children = $this->getMenuItems($slug,$menu_item_id);
    }

    return $result;
  }

  /**
   * Callback function for API menu
   *
   * @param \WP_REST_Request $request
   *
   * @return array|\WP_Error
   */
  public static function getMenuFromApi(\WP_REST_Request $request){
    $slug_or_id = $request->get_param('slug') ? $request->get_param('slug') : (int) $request->get_param('id');
    $self = new self($slug_or_id);
    $self->isApi = true;

    return $self->apiReturn($self->menus);
  }


  /**
   * Callback function for API menu
   *
   * @param \WP_REST_Request $request
   *
   * @return array|\WP_Error
   */
  public static function getMenuByLocationFromApi(\WP_REST_Request $request){
    $slug = $request->get_param('slug');
    $self = new self($slug, "location");
    $self->isApi = true;

    return $self->apiReturn($self->menus);
  }


  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){

    return null;
  }
}