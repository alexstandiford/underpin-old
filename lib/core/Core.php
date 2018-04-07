<?php
/**
 * Houses many helper functions that make many common WordPress development tasks easier
 * @author: Alex Standiford
 * @date  : 2/2/18
 */


namespace underpin\core;

if(!defined('ABSPATH')) exit;

abstract class Core{

  private static $isACFInstalled;
  public $isApi = false;

  public function __construct(){
    $this->errors = [];
    $this->checkForErrors();
    do_action($this->prefix('after_core'), $this);
  }


  /**
   * Adds an error to the array of errors, and triggers a PHP warning
   *
   * @param string|int $code    Error code
   * @param string     $message Error message
   * @param mixed      $data    Optional. Error data
   *
   * @return \WP_Error
   */
  public function throwError($code_or_error, $message = '', $data = null){
    $error = null;
    if($this->isApi){
      $data = ['status' => 400];
    }

    //If the error code is a string or int, use it
    if(is_string($code_or_error) || is_int($code_or_error)){
      $error = new \WP_Error($code_or_error, __($message), $data);
      $this->throwWarning($code_or_error, $message);
    }
    //If the error code isn't a string, check to see if it's a WP error. If it is, use that.
    elseif(is_wp_error($code_or_error)){
      $error = $code_or_error;
    }

    $this->errors[] = $error;

    return $error;
  }

  /**
   * Used to return a result for API endpoints. Handles proper error returning when there are any errors
   *
   * @param $result
   *
   * @return array|\WP_Error
   */
  public function apiReturn($result){
    if($this->isApi != true) $this->throwError('underpin01', 'The method apiReturn is designed to be used with API Endpoints. Be sure to specify isApi to true in your object');
    if($this->hasErrors()){
      $result = [];
      foreach($this->errors as $error){
        $result[key($error->errors)] = $error->errors[key($error->errors)];
      }
    }

    return $this->hasErrors() ? new \WP_Error('underpin01', 'Errors found', ['errors' => $result, 'status' => 400]) : $result;
  }

  /**
   * Checks to see if ACF is currently installed
   * @return bool
   */
  public function acfIsInstalled(){
    if(!isset($this->isACFInstalled)){
      if(function_exists('get_field')){
        self::$isACFInstalled = true;
      }
    }

    return self::$isACFInstalled;
  }

  /**
   * Throws a warning, but does not stop the object from running
   *
   * @param        $code_or_error
   * @param string $message
   */
  public function throwWarning($code_or_error, $message = ''){
    trigger_error(__($code_or_error.' error: '.$message));
  }

  /**
   * Checks to see if the current object has any errors
   * @return bool
   */
  public function hasErrors(){
    return !empty($this->errors);
  }

  /**
   * Checks to see if a specified file exists. Throws an error if not
   *
   * @param $file
   *
   * @return bool
   */
  public function fileExists($file){
    if(!file_exists($file)){
      $this->throwError('missing_file', 'The file at '.$file.'. does not exist.');

      return false;
    }

    return true;
  }

  /**
   * Gets an option using get_option. Applies & sanitizes the underpin prefix automatically
   *
   * @param      $option
   * @param bool $default
   *
   * @return mixed
   */
  public function getOption($option, $default = false){
    $option = $this->prefix(str_replace('-', '_', sanitize_title_with_dashes($option)));

    return get_option($option, $default);
  }

  /**
   * Gets an option using get_option. Applies & sanitizes the underpin prefix automatically
   *
   * @param      $option
   * @param bool $default
   *
   * @return mixed
   */
  public function getThemeMod($option, $default = false){
    $option = $this->prefix(str_replace('-', '_', sanitize_title_with_dashes($option)));

    return get_theme_mod($option, $default);
  }

  /**
   * Updates an option using update_option. Applies & sanitizes the underpin prefix automatically
   *
   * @param      $option
   * @param bool $default
   *
   * @return mixed
   */
  public function updateOption($option, $value, $autoload = null){
    $option = $this->prefix(str_replace('-', '_', sanitize_title_with_dashes($option)));

    return update_option($option, $value, $autoload);
  }

  /**
   * Deletes an option using delete_option. Applies & sanitizes the underpin prefix automatically
   *
   * @param $option
   *
   * @return bool
   */
  public function deleteOption($option){
    $option = $this->prefix(str_replace('-', '_', sanitize_title_with_dashes($option)));

    return delete_option($option);
  }

  /**
   * Loads post metadata, with a prefix
   *
   * @param        $post_id
   * @param string $key
   * @param bool   $default_option
   * @param bool   $default_value
   * @param bool   $single
   *
   * @return bool|mixed
   */
  public function getPostMeta($post_id, $key = '', $single = false){
    $key = $this->prefix($key);

    $post_meta = get_post_meta($post_id, $key, $single);

    return $post_meta;
  }

  /**
   * Returns the current class name, without the namespace
   */
  public function className(){
    $class_name = get_class($this);
    $class_name = substr($class_name, strrpos($class_name, '\\') + 1);

    return $class_name;
  }

  /**
   * Adds the UNDERPIN_PREFIX to the value, if it isn't already prefixed
   *
   * @param        $value
   * @param string $separator
   *
   * @return string
   */
  public function prefix($value, $separator = '_'){
    if(strpos($value, UNDERPIN_PREFIX) !== 0) return UNDERPIN_PREFIX.$separator.$value;

    return $value;
  }

  /**
   * Gets rid of the underpin prefix on a key
   *
   * @param $value
   *
   * @return bool|string
   */
  public function removePrefix($value){
    if(strpos($value, UNDERPIN_PREFIX.'_') !== false){
      $value = (substr($value, strlen(UNDERPIN_PREFIX.'_')));
    }

    return $value;
  }

  /**
   * Dumps errors in the current object
   */
  public function dumpErrors(){
    foreach($this->errors as $error){
      var_dump($error);
    }
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  abstract protected function checkForErrors();

}