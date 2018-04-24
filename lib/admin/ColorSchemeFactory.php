<?php
/**
 * Constructs the color scheme customizer section
 * Reads the customizer-variables.scss file and generates customizer fields automatically
 * @author: Alex Standiford
 * @date  : 3/25/18
 */


namespace underpin\admin;


use underpin\config\Customizer;
use underpin\core\Core;

if(!defined('ABSPATH')) exit;

class ColorSchemeFactory extends Core{

  public $customizerFile;
  public $variables;
  public $customizerFileLocation;
  private $customizerSplitValues = false;
  private $splitValues = false;

  public function __construct($location = 'auto'){
    if($this->themeHasColorSchemeFile()){
      $this->customizerFileLocation = $this->locateCssDirFile('customizer-variables.scss', $location);
    }
    parent::__construct();

    if(!$this->hasErrors() && $this->themeHasColorSchemeFile()){
      $this->customizerFile = file_get_contents($this->customizerFileLocation);
      preg_match_all('/(?<=\$)(.*)(?=!)/', $this->customizerFile, $this->variables, PREG_PATTERN_ORDER, 0);
      $this->variables = $this->variables[0];
    }
  }

  /**
   * Locates a CSS Dir file
   * Checks to see if there is an override CSS file located in the website directory
   *
   * @param string $file     - File name to load in
   * @param string $location - The location to check. Defaults to auto.
   *
   * @return mixed|string
   */
  public static function locateCssDirFile($file = 'style.css', $location = 'auto'){
    switch($location){
      case 'site':
        $file_location = CssUpdater::getCssDirFile($file);
        break;
      //If i'm configured to locate the network css (inside the theme file)
      case 'network':
        $file_location = UNDERPIN_ASSETS_DIR.'css/'.$file;
        break;
      //If the configured file to locate needs to be automatically found
      default:
        if(file_exists(CssUpdater::getCssDirFile($file))){
          $file_location = CssUpdater::getCssDirFile($file);
        }
        else{
          $file_location = UNDERPIN_ASSETS_DIR.'css/'.$file;
        }
        break;
    }

    return file_exists($file_location) ? $file_location : false;
  }

  /**
   * Checks to see if the current theme has a color scheme file to load in
   * @return bool
   */
  public function themeHasColorSchemeFile(){
    if(!isset($this->colorSchemeFile)){
      $this->colorSchemeFile = self::locateCssDirFile('customizer-variables.scss', 'network') === false ? false : true;
    }

    return $this->colorSchemeFile;
  }

  /**
   * Splits the CSS and adds the necessary data to add them to the customizer
   * @return array|bool
   */
  public function splitValuesForCustomizer(){
    if($this->customizerSplitValues === false){
      $this->customizerSplitValues = [];

      foreach($this->variables as $value){
        $exploded_value = explode(':', $value);
        $css_selector = trim($exploded_value[0]);
        $key = 'color_scheme_'.$css_selector;
        $value = trim($exploded_value[1]);
        $name = ucwords(preg_replace('/[-,_]/', ' ', $css_selector));
        $this->customizerSplitValues[$key] = [
          'default' => $value,
          'label'   => $name,
        ];
        if(sanitize_hex_color($value)){
          $this->customizerSplitValues[$key]['control_type'] = 'WP_Customize_Color_Control';
        }
        else{
          $this->customizerSplitValues[$key]['type'] = 'text';
        }
      }
    }

    return $this->customizerSplitValues;
  }

  /**
   * Extracts the values from the file as an associative array
   * @return array|bool
   */
  public function splitValues(){
    if($this->splitValues === false){
      $this->splitValues = [];
      foreach($this->variables as $value){
        $exploded_value = explode(':', $value);
        $css_selector = trim($exploded_value[0]);
        $value = trim($exploded_value[1]);
        $this->splitValues[$css_selector] = $value;
      }
    }

    return $this->splitValues;
  }

  /**
   * Function to call to add the customizer fields to the theme
   *
   * @param $configs
   *
   * @return mixed
   */
  public function addCustomizerFields($configs){
    if($this->themeHasColorSchemeFile()){
      $configs = Customizer::merge($configs, 'color_scheme', [
        'title'       => 'Color Scheme',
        'description' => "Customize this theme's color scheme, margins, and padding sizes",
        'priority'    => 10,
        'capability'  => 'edit_theme_options',
        'settings'    => $this->splitValuesForCustomizer(),
      ]);
    }

    return $configs;
  }

  /**
   * Required on every object to check for errors
   * @return mixed
   */
  protected function checkForErrors(){
    if(!$this->fileExists($this->customizerFileLocation)) return $this->throwError('ColorSchemeFactory01', 'The file located at '.$this->customizerFileLocation.' does not exist.');
  }
}