<?php
/**
 * Configuration class for CPT values
 * @author: Alex Standiford
 * @date  : 2/1/2017
 */

namespace underpin\config;

use underpin\core\Config;

if(!defined('ABSPATH')) exit;

class PostTypes extends Config{

  protected $action = 'init';
  protected $fields = [];

  public function __construct($configs){
    parent::__construct($configs);
  }

  /**
   * Implements Customizer Configurations
   *
   * @param $config_key
   * @param $config_value
   *
   * @return bool
   */
  protected function implementConfiguration($config_key, $config_value){
    if($this->hasErrors()) return false;
    $default_args = $this->getDefaultArgs($this->getNames($config_key, $config_value));
    $merged_args = $this->getArgsWithDefaults($default_args,$config_value);
    $this->registerPostTypeWithTaxonomies($config_key, $merged_args);

    return true;
  }


  /**`
   * Gets the singular and plural form of the given name
   */
  public function getNames($post_type, $args){
    $name = isset($args['singular_name']) ? $args['singular_name'] : false;
    $name = $name == false ? $post_type : $name;
    if(strpos($name,'_') !== false) $name = ucfirst(str_replace('_', ' ',$name));
    if(!isset($args['name'])) $args['name'] = '';
    $plural_name = isset($args['singular_name']) ? $args['name'] : $name.'s';

    return ['singular' => $name, 'plural' => $plural_name];
  }

  /**
   * Registers the post type and taxonomies (if any exist)
   *
   * @param $post_type
   * @param $merged_args
   */
  public function registerPostTypeWithTaxonomies($post_type, $merged_args){
    if(!empty($merged_args['taxonomies'])){
      $taxonomies_to_push = [];
      foreach($merged_args['taxonomies'] as $taxonomy_name => $args){
        if(!taxonomy_exists($taxonomy_name)){
          $names = $this->getNames($taxonomy_name, $args);
          if(isset($args['meta_box_cb'])) $args['meta_box_cb'] = [$this, $args['meta_box_cb']];
          $default_args = $this->getDefaultArgs($names, 'taxonomy');
          register_taxonomy(strtolower($taxonomy_name), $post_type, $this->getArgsWithDefaults($default_args, $args));
          $taxonomies_to_push[] = $taxonomy_name;
        }
      }
      unset($merged_args['taxonomies']);
      $merged_args['taxonomies'] = $taxonomies_to_push;
    }
    register_post_type($post_type, $merged_args);
  }

  /**
   * Creates the merged arguments of the given arguments and defaults
   *
   * @param        $default_args
   * @param        $args
   * @param string $type
   *
   * @return array
   */
  private function getArgsWithDefaults($default_args, $args){
    unset($args['name']);
    unset($args['singular_name']);
    $merged_args = is_array($args) ? array_replace_recursive($default_args, $args) : $default_args;
    if(!isset($args['labels'])) $args['labels'] = null;

    return $merged_args;
  }

  /**
   * Gets the default args for the given item type
   *
   * @param        $name
   * @param string $type - can be taxonomy or post
   *
   * @return array
   */
  private function getDefaultArgs($name, $type = 'post'){
    $plural_name = $name['plural'];
    $name = $name['singular'];

    if($type == 'post'){
      $args = [
        'public'            => true,
        'has_archive'       => true,
        'capability_type'   => 'post',
        'show_in_menu'      => true,
        'show_ui'           => true,
        'show_in_admin_bar' => true,
        'can_export'        => true,
        'menu_position'     => 5,
        'show_in_rest'      => true,
        'taxonomies'        => [],
        'supports'          => ['title', 'editor', 'excerpt', 'revisions', 'thumbnail'],
        'labels'            => [
          'name'               => __(ucfirst($plural_name)),
          'singular_name'      => __(ucfirst($name)),
          'label'              => __(ucfirst($name)),
          'add_new'            => _x('Add New', $name),
          'add_new_item'       => __('Add New '.$name),
          'new_item'           => __('New '.$name),
          'edit_item'          => __('Edit '.$name),
          'view_item'          => __('View '.$name),
          'all_items'          => __('All '.$plural_name),
          'search_items'       => __('Search '.$plural_name),
          'not_found'          => __('No '.$plural_name.' found.'),
          'not_found_in_trash' => __('No '.$plural_name.' found in Trash.'),
        ],
      ];
    }
    else{
      $args = [
        'labels' => [
          'name'          => __(ucfirst($plural_name)),
          'singular_name' => __(ucfirst($name)),
          'menu_name'     => __(ucfirst($plural_name)),
        ],
      ];
    }

    return $args;
  }


  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if($this->configExists()) return $this->throwError('Config01', 'New Configuration array was called incorrectly. To add to the underpin configurations, hook into underpin_posttypes_config using add_filter().');
  }
}