<?php
/**
 * Rebuilds the CSS file if the current theme file is newer than the site's css file
 * @author: Alex Standiford
 * @date  : 4/6/18
 */


namespace underpin\admin;


if(!defined('ABSPATH')) exit;

class CssSynchronizer{

  public function __construct(){
    $this->networkCssFile = get_stylesheet_directory().'/build/assets/style.css';
    $this->siteCssFile = CssUpdater::getCssDirFile();
  }

  /**
   * Synchronize the site CSS file with the network CSS file
   * Basically, this checks to see if the theme CSS was changed since the page was last loaded
   * if so, this function merges the SCSS variables and then runs SCSS updater with the newly merged vars
   */
  public static function syncCssFile(){
    $self = new self();
    if($self->needsUpdated()){
      $variables = $self->mergeScssVariables();
      CssUpdater::runUpdater($variables);
    }
  }

  /**
   * Merges the network available SCSS variables with the site-specific variables
   * @return array
   */
  private function mergeScssVariables(){
    $network_scss_vars = new ColorSchemeFactory('network');
    $site_scss_vars = new ColorSchemeFactory('site');
    $network_scss_vars = $network_scss_vars->splitValues();
    $site_scss_vars = $site_scss_vars->splitValues();
    $values_to_remove = array_diff_key($site_scss_vars,$network_scss_vars);
    foreach($values_to_remove as $value_to_remove => $value){
      unset($site_scss_vars[$value_to_remove]);
    }
    $variables = wp_parse_args($site_scss_vars,$network_scss_vars);

    return $variables;
  }

  /**
   * Checks to see if the site's CSS file needs to be regenerated
   * @return bool
   */
  private function needsUpdated(){
    $needs_updated = false;
    if(file_exists($this->siteCssFile)){
      $needs_updated = filemtime($this->siteCssFile) < filemtime($this->networkCssFile);
    }

    return $needs_updated;
  }

}