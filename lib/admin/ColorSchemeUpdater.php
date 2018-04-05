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

class ColorSchemeUpdater extends Core{

  private $manager;
  private $sources = false;
  private $updatedValues = [];
  private $buildDirectory = '';


  /**
   * ColorSchemeUpdater constructor.
   *
   * @param bool|\WP_Customize_Manager $manager - The WP_Customize_manager object
   */
  public function __construct($manager = false){
    parent::__construct();
    $this->buildDirectory = get_stylesheet_directory().'/build/assets/';
    $this->manager = $manager;
    $this->scssFileParams = new ColorSchemeFactory();

  }

  /**
   * Updates the CSS from the customizer submission
   * @param $manager
   *
   * @return bool
   */
  public static function runUpdater($manager){
    $self = new self($manager);
    $self->unsetColorSchemeValues();
    $self->updateVariablesFile();
    $self->recompileCss();

    return $self->manager;
  }

  /**
   * Prevents the customizer from saving the color scheme values to the database
   * This is done because we don't rely on the database to get these values
   * Instead, we gather these items directly from the SCSS file
   * @return bool|WP_Customize_Manager
   */
  private function unsetColorSchemeValues(){
    if(!$this->manager instanceof WP_Customize_Manager) return false;

    foreach($this->manager->unsanitized_post_values() as $key => $value){
      if(strpos($key, 'underpin_color_scheme_') === 0){
        $this->updatedValues[str_replace('underpin_color_scheme_', '', $key)] = $value;
        $this->manager->set_post_value($key, null);
      }
    }

    return $this->manager;
  }

  /**
   * updates the variables in the variables.scss file and returns the resulting string
   * @return string
   */
  private function getVariablesFile(){
    $variables = $this->scssFileParams->splitValues();
    $variables_to_set = [];

    foreach($this->updatedValues as $key => $updated_value){
      if(isset($variables[$key])){
        $variables_to_set[$key] = $updated_value;
      }

    }

    $scss_string = '';
    $variables = wp_parse_args($variables_to_set, $variables);
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

    file_put_contents($this->scssFileParams->customizerFileLocation, $scss_string);
  }

  /**
   * Compiles the CSS and returns the resulting string
   */
  private function getCompiledCss(){
    $variables = file_get_contents(UNDERPIN_ASSETS_DIR.'css/variables.scss');
    $compiled_scss = '';
    foreach($this->getScssSources() as $file){
      $scss = new Compiler();
      $path = trailingslashit(dirname($file));
      $scss->setImportPaths([UNDERPIN_ASSETS_DIR.'css/', $path]);
      $compiled_scss .= $scss->compile($variables.file_get_contents($file));
    }

    return $compiled_scss;
  }

  /**
   * Gets the compiled css and saves it to the theme
   */
  private function recompileCss(){
    $compiled_scss = $this->getCompiledCss();
    file_put_contents($this->buildDirectory.'style.css', $compiled_scss);
  }

  /**
   * Gets the SCSS map to determine which files need included
   */
  private function getScssSources(){
    if($this->sources === false){
      $map = json_decode(file_get_contents($this->buildDirectory.'style.css.map'));

      foreach($map->sources as $source){
        $this->sources[] = str_replace('webpack:///.', get_stylesheet_directory(), $source);
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