<?php
/**
 * Handles Customizer Updates for color scheme
 * @author: Alex Standiford
 * @date  : 3/26/18
 */


namespace underpin\admin;

use underpin\core\Core;
use WP_Customize_Manager;
use Leafo\ScssPhp\Compiler;


if(!defined('ABSPATH')) exit;

class CssUpdater extends Core{

  private $scssVariables;
  private $sources = false;
  private $updatedValues = [];
  private $isCustomizer = false;


  /**
   * ColorSchemeUpdater constructor.
   *
   * @param bool|\WP_Customize_Manager $scss_variables - The WP_Customize_manager object
   */
  public function __construct($scss_variables = false){
    parent::__construct();
    $this->scssVariables = $scss_variables;
    $this->isCustomizer = $this->scssVariables instanceof WP_Customize_Manager;
    $this->scssFileParams = new ColorSchemeFactory();
  }

  /**
   * Gets the CSS Url of the current site
   *
   * @param string $name text to append to url
   *
   * @return mixed
   */
  public static function getCssFileUrl($name = 'style.css'){
    if(is_multisite()){
      $upload_dir = wp_upload_dir();
      $dir = trailingslashit($upload_dir['baseurl']).'css/'.$name;
    }
    else{
      $dir = get_stylesheet_directory_uri().'/build/assets/'.$name;
    }

    return $dir;
  }

  /**
   * Gets the CSS Directory and file of the current site
   *
   * @param string $name text to append to url
   *
   * @return mixed
   */
  public static function getCssDirFile($name = 'style.css'){
    if(is_multisite()){
      $upload_dir = wp_upload_dir();
      $dir = trailingslashit($upload_dir['basedir']).'css/'.$name;
    }
    else{
      $dir = get_stylesheet_directory().'/build/assets/'.$name;
    }

    return $dir;
  }

  /**
   * Updates the CSS from the customizer submission
   *
   * @param $scss_variables - An array of SCSS variable values keyed by their variable name OR a customizer object
   *
   * @return bool
   */
  public static function runUpdater($scss_variables){
    $self = new self($scss_variables);
    if(!empty($self->getUpdatedCssVariables())){
      $self->updateVariablesFile();
      $self->recompileCss();
    }

    return $self->scssVariables;
  }

  /**
   * Returns true if the current instance is running from a customize manager instance
   * @return bool
   */
  public function isCustomizer(){
    return $this->isCustomizer;
  }

  /**
   * Prevents the customizer from saving the color scheme values to the database
   * This is done because we don't rely on the database to get these values
   * Instead, we gather these items directly from the SCSS file
   * @return bool|WP_Customize_Manager
   */
  private function getUpdatedCssVariables(){
    if($this->isCustomizer()){
      foreach($this->scssVariables->unsanitized_post_values() as $key => $value){
        if(strpos($key, 'underpin_color_scheme_') === 0){
          $this->updatedValues[str_replace('underpin_color_scheme_', '', $key)] = $value;
          $this->scssVariables->set_post_value($key, null);
        }
      }
    }
    else{
      $this->updatedValues = $this->scssVariables;
    }

    return $this->updatedValues;
  }

  /**
   * updates the variables in the variables.scss file and returns the resulting string
   * @return string
   */
  private function getVariablesFile(){
    $variables = $this->scssFileParams->splitValues();
    $variables_to_set = [];

    if($this->isCustomizer()){
      foreach($this->updatedValues as $key => $updated_value){
        if(isset($variables[$key])){
          $variables_to_set[$key] = $updated_value;
        }
      }
      $variables = wp_parse_args($variables_to_set, $variables);
    }
    else{
      $variables = $this->updatedValues;
    }

    $scss_string = '';
    ksort($variables);
    foreach($variables as $selector => $param){
      $scss_string .= "$".$selector.":".$param." !default; \r\n\r\n";
    }

    return $scss_string;
  }

  /**
   * Updates customizer-variables.scss to reflect the customizer overrides
   */
  private function updateVariablesFile(){
    $scss_string = $this->getVariablesFile();
    if(!is_dir($this->getCssDirFile(''))) mkdir($this->getCssDirFile(''));
    file_put_contents($this->getCssDirFile('customizer-variables.scss'), $scss_string);
  }

  /**
   * Compiles the CSS and returns the resulting string
   */
  private function getCompiledCss(){
    $variables = file_get_contents($this->getCssDirFile('customizer-variables.scss'));
    $variables .= file_get_contents(UNDERPIN_ASSETS_DIR.'css/variables.scss');
    $compiled_scss = '';

    //Loop through the SCSS map, compile the SCSS, and concatenate the CSS file
    foreach($this->getScssSources() as $file){
      $scss = new Compiler();
      $path = trailingslashit(dirname($file));
      $scss->setImportPaths([$path]);
      $file = file_get_contents($file);
      $compiled_scss .= $scss->compile($variables.$file);
    }

    //Move Import statements to the beginning of the file
    preg_match('/@import url\(.*.\)\;/', $compiled_scss, $imports);
    preg_replace('/@import url\(.*.\)\;/', '', $compiled_scss);
    foreach($imports as $import){
      $compiled_scss = $import.$compiled_scss;
    }

    return $compiled_scss;
  }

  /**
   * Gets the compiled css and saves it to the theme
   */
  private function recompileCss(){
    $compiled_scss = $this->getCompiledCss();
    file_put_contents($this->getCssDirFile(), $compiled_scss);
  }

  /**
   * Gets the SCSS map to determine which files need included
   */
  private function getScssSources(){
    if($this->sources === false){
      $this->sources = [];
      $map = json_decode(file_get_contents(get_stylesheet_directory().'/build/assets/style.css.map'));

      foreach($map->sources as $source){
        preg_match('/\/vendor.*|\.\/.*/', $source, $match);
        $this->sources[] = strpos($match[0], '.') === 0 ? get_stylesheet_directory().substr($match[0], 1) : UNDERPIN_ROOT_DIR.$match[0];
      }
    }

    return $this->sources;
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    // TODO: Implement checkForErrors() method.
  }
}