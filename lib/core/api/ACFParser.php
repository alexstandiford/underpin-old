<?php
/**
 * Parses Gutenberg ACF object data and converts it into a REST endpoint
 * @author: Alex Standiford
 * @date  : 11/15/18
 */


namespace underpin\core;


if(!defined('ABSPATH')) exit;

class ACFParser extends Core{

  public $results = [];
  public $objects = [];
  public $fields = [];

  public function __construct($post_or_slug,$post_type = 'page', $regex = '/<!-- wp:(.*) ({.*) \/-->/'){
    parent::__construct();
    if(is_int($post_or_slug)){
      $this->post = get_post($post_or_slug);
    }
    else{
      $this->post = get_posts(['name' => $post_or_slug, 'post_type' => $post_type]);
      $this->post = $this->post[0];
    }
    preg_match_all($regex, $this->post->post_content, $matches);
    $this->matches = $matches;
    $this->fields = $matches[1];
    $this->objects = $matches[2];
  }

  /**
   * Parses the ACF block string and converts it into an array
   * @return array
   */
  public function parseAcfBlocks(){
    $results = [];
    foreach($this->objects as $key => $acf_json_string){
      $object = json_decode($acf_json_string);
      $results[] = ['component' => $this->fields[$key], 'id' => $object->id, 'fields' => $this->parseAcfFields((array)$object->data)];
    }

    return $results;
  }

  /**
   * Parses an individual acf field string and converts it into an array
   * @param $block
   *
   * @return array
   */
  private function parseAcfFields($block){
    $results = [];
    foreach($block as $key => $field){
      $field_object = get_field_object($key);
      $results[$field_object['name']] = $block[$key];
    }

    return $results;
  }

  /**
   * REST API Callback
   * @param \WP_REST_Request $req
   *
   * @return array|\WP_Error
   */
  public static function getAcfFieldsForApiByID(\WP_REST_Request $req){
    $id = (int) $req->get_param('id');

    $self = new self($id);
    $self->isApi = true;
    $result = $self->parseAcfBlocks();

    return $self->apiReturn($result);
  }

  /**
   * REST API Callback
   * @param \WP_REST_Request $req
   *
   * @return array|\WP_Error
   */
  public static function getAcfFieldsForApiBySlug(\WP_REST_Request $req){
    $slug =$req->get_param('slug');
    $post_type = $req->get_param('post_type');

    $self = new self($slug, $post_type);
    $self->isApi = true;
    $result = $self->parseAcfBlocks();

    return $self->apiReturn($result);
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}