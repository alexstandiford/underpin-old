<?php
/**
 * Gets all routes (page structure) on this site
 * @author: Alex Standiford
 * @date  : 11/16/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

class Routes extends Core{

  public $query = [];

  public function __construct(){
    parent::__construct();

    $post_types = get_post_types(['public' => true]);
    $excluded_post_types = ['revision', 'attachment', 'nav_menu_item'];
    $this->query = new \WP_Query(['post_type' => array_diff($post_types, $excluded_post_types), 'posts_per_page' => - 1, 'post_status' => 'publish']);
  }

  public function getUrls(){
    $urls = [];
    while($this->query->have_posts()){
      $this->query->the_post();
      $urls[] = [
        'route'     => str_replace(get_site_url(), '', get_permalink(get_the_ID())),
        'id'        => get_the_ID(),
        'post_type' => get_post_type(get_the_ID()),
      ];
    }

    return $urls;
  }

  public static function getRoutesFromApi(){
    $self = new self();
    $self->isApi = true;

    return $self->apiReturn($self->getUrls());
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}