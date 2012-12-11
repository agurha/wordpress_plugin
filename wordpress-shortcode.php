<?php

/**
 * @package WebShotr
 * @version 1.0
 */
/*
Plugin Name: Webshotr screenshots and thumbnails
Plugin URI: http://www.webshotr.com/
Description: Add rich experience to your website with webshotr screenshot service. Add website previews / thumbnails to links in your posts
Author: WebShotr
Version: 1.0
*/

// define the plugin URL
define('WESBHOTR_PLUGIN_URL', plugin_dir_url(__FILE__));

// URL2PNG API URL
define('API_URL_PREFIX', 'http://api.webshotr.com/v1');
// Enter your API key
define('API_KEY', 'ca482d7e-9417-4569-90fe-80f7c5e1c781');
// Enter your secret key
define('SECRET_KEY', 'd18ff559-8fc2-447f-8e8d-1b1157f9b1c2');

// Gets the wp-content directory
define('CONTENT_DIR', dirname(dirname(dirname(__FILE__))));
// Change 'screnshots' to an alternative directory if you like
define('SCREENSHOT_DIR', CONTENT_DIR . '/' . 'screenshots');
// Used for the frontend, if you have another directory than 'screenshots' change it here as well
define('SCREENSHOT_URL', '/wp-content/screenshots');


class Webshotr {
  protected $api_url_prefix;
  protected $api_key;
  protected $secret_key;
  protected $token;
  
  public function __construct() {
    $this->api_url_prefix = API_URL_PREFIX;
    $this->api_key = API_KEY;
    $this->secret_key = SECRET_KEY;
    $this->screenshot_dir = SCREENSHOT_DIR;
    $this->screenshot_url = SCREENSHOT_URL;

    // add the shortcode handler to available shortcodes
    add_shortcode('webshotr', array($this, 'shortcode'));

    // Add shortcode support for widgets  
    add_filter('widget_text', 'do_shortcode');  
  }

  // # usage
  // $options['force']     = 'false';      # [false,always,timestamp] Default: false
  // $options['full_page']  = 'false';      # [true,false] Default: false
  // $options['thumbnailSize'] = 'false';      # scaled image width in pixels; Default no-scaling.
  // $options['width']  = "1280";
  // $options['height'] = "1024";  # Max 5000x5000; Default 1280x1024
  // $options['wrap'] = 'iphone5';
  
  // Construct the URL
  public function generateUrl($query_string) {

    // $TOKEN = md5($query_string . $URL2PNG_SECRET);
    $TOKEN = hash_hmac("sha1", $query_string, $this->secret_key);

    return $this->api_url_prefix . "/" . $this->api_key . "/$TOKEN/png/?$query_string";
  }

  private function queryStringFromArgs($url, $args) {
    # urlencode request target
    if ($url == '') return '<p>ERROR: No webshotr url in queryStringFromArgs </p>';
    
    $options['url'] = urlencode(trim($url));

    $options += $args;

    # create the query string based on the options
    foreach($options as $key => $value) { $_parts[] = "$key=$value"; }

    # create a token from the ENTIRE query string
    return implode("&", $_parts);
  }
  
  // Save the screenshot to disk
  public function saveScreenshot($query_string) {

    $path = $this->screenshot_dir . '/' . md5($query_string) . '.png'; 
    
    $img = $this->generateUrl($query_string);
    
    $ch = curl_init($img);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $rawdata = curl_exec($ch);
    curl_close ($ch);
    if (file_exists($path)) {
      unlink($path);
    }
    $fp = fopen($path,'w+');
    fwrite($fp, $rawdata);
    fclose($fp);

    if (file_exists($path)){  
      return $path;
    }

  }
  
  // Get the screenshot and either return the path or display the image with the img tag
  public function getScreenshot($url, $args, $imagetag = false, $class = false) {
    $query_string = $this->queryStringFromArgs($url, $args);
    $path = $this->screenshot_dir . '/' . md5($query_string) . '.png';
    $image_url = get_bloginfo('url') . $this->screenshot_url . '/' . md5($query_string) . '.png';
    
    if (!file_exists($path)) {
      $this->saveScreenshot($query_string);
    }

    if ($imagetag == true) {
      echo '<img src="' . $image_url . '" alt="' . $url . '" class="' . $class . '" />';
    } else {
      return $image_url;
    }
  }

  public function shortcode($atts, $content=null) {
    // extract(shortcode_atts(array('url' => ''), $atts));
    extract(shortcode_atts(array(
        'url' => '',
        'width' => '1024', 
        'height' => '768',
        'thumbnailSize' => '100',
        'full_page' => false,
        'wrap' => '',
        'force' => false,
      ), $atts));
    
    if (empty($atts['url'])) return '<p>ERROR: No Webshotr Url</p>';

    $this->getScreenshot($atts['url'], $atts, true, 'myclass');

    if(WP_DEBUG) {
      $qs = $this->queryStringFromArgs($url, $atts);
      echo '<p>DEBUG: Generated webshotr url: ';
      echo '<a href="' . $this->generateUrl($qs) . '">' . $this->generateUrl($qs) . '</a>';
      echo '</p>';
    }

    // add our custom javascript and css
    // webshotr_load_css_and_javascript();

  }
}

$webshotr = new Webshotr();



// // we need to load customer js and css - this function will be called to do it
// // when the shortcode is rendered
// function webshotr_load_css_and_javascript() {

//    # CSS first
//    wp_register_style('webshotr.wordpress.css', WEBSHOTR_PLUGIN_URL . 'webshotr.wordpress.css');
//    wp_enqueue_style('webshotr.wordpress.css');

//    # Now JS
//    wp_register_script('webshotr.wordpress.js', WEBSHOTR_PLUGIN_URL . 'webshotr.wordpress.js', false, "", true);
//    wp_enqueue_script('webshotr.wordpress.js');
//    wp_register_script('jquery.ba-postmessage.js', WEBSHOTR_PLUGIN_URL . 'jquery.ba-postmessage.js', false, "", true);
//    wp_enqueue_script('jquery.ba-postmessage.js');
// }

?>