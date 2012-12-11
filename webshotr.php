<?php

header("Content-type: " . image_type_to_mime_type(IMAGETYPE_PNG));

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

global $wpdb;
define(WEBSHOTR_DB_TABLE, $wpdb->prefix . 'webshotr');
define(WEBSHOTR_VERSION, '1.0');

function webshotr_init()
{
    wp_register_script('webshotr', 'https://s3.amazonaws.com/web.webshotr.com/webshotr_base.js');
    wp_enqueue_script('webshotr');
    add_action('wp_head', 'webshotr_js_init');
}

function webshotr_js_init()
{
    $settings = webshotr_get_db_settings();
    $isHoverEnabled = ($settings['hover_enabled'] == 1) ? true : false;
    $hoverSelector = $settings['hover_selector'];
    $apiKey = $settings['api_key'];
    $apiSecret = $settings['api_secret'];
    $hoverSize = $settings['hover_size'];

//    $thisurl = get_bloginfo('url');
//    $thisurl = str_replace ("https://",'', $thisurl);
//    $thisurl = str_replace ("http://",'', $thisurl);
//    $thisurl = explode('/',$thisurl);
//    $thisurl = $thisurl[0];
//
//
//    $options['url'] = urlencode($thisurl);
//
//    foreach ($options as $key => $value) {
//        $_parts[] = "$key=$value";
//    }
//
//    $query_string = implode("&", $_parts);
//
//    $apiToken = hash_hmac("sha1", $query_string, $apiSecret);


    if (!$isHoverEnabled)
        return;

    echo "<!-- Begin -->\n";
    echo "<script type=\"text/javascript\">\n";
    echo "    WebShotrAPIKey = '" . $apiKey . "';\n";
    echo "    WebShotrAPISecret = '" . $apiSecret . "';\n";
    echo "    WebShotrSize = '" . $hoverSize . "';\n";
    echo "    WebShotrHoverSelector = '" . $hoverSelector . "';\n";
//    echo "    PostUrl = '" . $thisurl . "';\n";
//    echo "    QueryString = '" . $query_string . "';\n";

    echo "</script>\n";
    echo "<!-- End -->\n";
}

function webshotr_init_db()
{
    global $wpdb;

    // First time install
    if ($wpdb->get_var("SHOW TABLES LIKE '" . WEBSHOTR_DB_TABLE . "'") != WEBSHOTR_DB_TABLE) {
        $sql = "CREATE TABLE IF NOT EXISTS " . WEBSHOTR_DB_TABLE . " (api_key CHAR(150), api_secret CHAR(150), hover_size CHAR(9), hover_enabled TINYINT(1), hover_selector TEXT)";
        $result = $wpdb->query($sql);
        $result = $result && $wpdb->query("TRUNCATE " . WEBSHOTR_DB_TABLE);
        $result = $result && $wpdb->insert(WEBSHOTR_DB_TABLE,
            array(
                'hover_selector' => base64_encode('a.webshotr,a.webshotr_post_hover'),
                'hover_size' => 'xlarge'
            ),
            '%s'
        );

        add_option('webshotr_version', WEBSHOTR_VERSION);
    } else {
        // Upgrade path
    }

    if (!$result) {
        echo "There was an issue creating the database table used for this plugin. Please refresh this page and try again.";
        return false;
    }

    return true;
}

function webshotr_uninstall_db()
{
    global $wpdb;

    $sql = "DROP TABLE IF EXISTS " . WEBSHOTR_DB_TABLE;
    $wpdb->query($sql);
    delete_option('webshotr_version');
}

function webshotr_get_db_settings()
{
    global $wpdb;

    $query = "SELECT api_key, api_secret, hover_size, hover_enabled, hover_selector FROM " . WEBSHOTR_DB_TABLE . " LIMIT 1";
    $data = $wpdb->get_results($query, ARRAY_A);
    $data = isset($data[0]) ? $data[0] : $data;

    $data['hover_selector'] = base64_decode($data['hover_selector']);

    return $data;
}

function webshotr_add_pages()
{
    add_options_page('Webshotr Screenshot Service', 'Webshotr', 'manage_options', __FILE__, 'webshotr_manager');
}

function webshotr_manager()
{
    global $wpdb;

    // Did the manager save changes to this page
    if (count($_POST) > 0) {
        $success = true;
        $error = "";

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 3) == "ws_") {
                // Scrub the key name
                $key = preg_replace("/[^a-zA-Z0-9_]+/", "", substr($key, 3));

                if ($key == "hover_enabled") {
                    // Make sure there is an api key and secret to use
                    $settings = webshotr_get_db_settings();
                    if (strlen($settings['api_key']) < 3 || strlen($settings['api_secret']) < 3) {
                        $error = "Before you can enable this plugin, you must specify your api key and secret below.";
                        $success = false;
                        continue;
                    }
                } else if ($key == "api_key") {
                    $value = trim($value);

                } else if ($key == "api_secret") {
                    $value = trim($value);
                } else if ($key == "hover_selector") {
                    $value = implode(",", array_filter(explode("\r\n", $value)));
                    $value = base64_encode($value);

                }

                // Save this value
                $success = $success & ($wpdb->query($wpdb->prepare("UPDATE " . WEBSHOTR_DB_TABLE . " SET " . $key . " = %s", $value)) !== false);
            }
        }

        if (!$success) {
            echo "<div style='color: #d8000c; background-color: #ffbaba; border: 3px solid red; padding: 5px; margin-top: 5px;'>An error occurred while updating the settings in the database. " . $error . "</div>\n";
        } else {
            echo "<div style='color: #4f8a10; background-color: #dff2bf; border: 3px solid green; padding: 5px; margin-top: 5px;'>Your changes have been saved.</div>\n";
        }
    }

    $settings = webshotr_get_db_settings();
    $isHoverEnabled = ($settings['hover_enabled'] == 1) ? true : false;
    $hoverSelector = $settings['hover_selector'];
    $apiKey = $settings['api_key'];
    $apiSecret = $settings['api_secret'];
    $hoverSize = $settings['hover_size'];

    echo "<script type=\"text/javascript\">\n";
    echo "  function ws_set_sample(width, height) {\n";
    echo "    document.getElementById('wsSample').style.display = 'block';\n";
    echo "    document.getElementById('wsSample').style.width = width + 'px';\n";
    echo "    document.getElementById('wsSample').style.height = height + 'px';\n";
    echo "  }\n";
    echo "</script>\n";

    echo "<div class='wrap'>\n";

    echo "<h2>Webshotr Screenshot Service</h2>\n";
    echo "<br />\n";
    echo "<form name='webshotr_enable_disable' action='" . $_SERVER["REQUEST_URI"] . "' method='post'>\n";
    echo "<div style='text-decoration: underline;'>Main Options</div>\n";
    echo "<div>Hover Link Thumbnails: <span style='font-weight: bold;'>" . ($isHoverEnabled ? 'Enabled' : 'Disabled') . "</span></div>\n";
    echo "<input type='hidden' name='ws_hover_enabled' value='" . ($isHoverEnabled ? '0' : '1') . "' />\n";
    echo "<div class='submit'><input type='submit' value='" . ($isHoverEnabled ? 'Disable' : 'Enable') . " It' /></div>\n";
    echo "</form>\n";

    echo "<form name='webshotr_manager' action='" . $_SERVER["REQUEST_URI"] . "' method='post'>\n";
    echo "<br />\n";
    echo "<div style='text-decoration: underline;'>Hover CSS Selectors</div>\n";
    echo "<p>This is an advanced feature, generally you won't need to change this setting but it is here if you do.  You can specify additional CSS selectors that will trigger hover thumbnail popups.</p>\n";
    echo "<div style='margin-left: 15px;'><pre>Any link with the class 'webshotr' (default):</pre><code>a.webshotr</code></div>\n";
    echo "<div style='margin-left: 15px;'><pre>Links in your posts (default):</pre><code>a.webshotr_post_hover</code></div>\n";
    echo "<br />\n";
    echo "<textarea name='ws_hover_selector' cols='40' rows='4' style='width: 70%; font-size: 12px;' class='code'>\n";
    echo implode("\r\n", explode(",", $hoverSelector));
    echo "</textarea>\n";
    echo "<div><span style='font-weight: bold;'>Note:</span> Enter one CSS selector per line.</div>\n";
    echo "<div class='submit'><input type='submit' value='Save Changes' /></div>\n";
    echo "<br />\n";
    echo "<div style='text-decoration: underline;'>API Key</div>\n";
    echo "<p>The api key can be found after logging into your Webshotr Dashboard at <a href='http://www.webshotr.com' target='_blank'>http://www.webshotr.com</a>.</p>\n";
    echo "<input name='ws_api_key' type='text' value='" . $apiKey . "' />\n";
    echo "<div class='submit'><input type='submit' value='Save Changes' /></div>\n";
    echo "<br />\n";
    echo "<div style='text-decoration: underline;'>API Secret</div>\n";
    echo "<p>The api secret can be found after logging into your Webshotr Dashboard at <a href='http://www.webshotr.com' target='_blank'>http://www.webshotr.com</a>.</p>\n";
    echo "<input name='ws_api_secret' type='text' value='" . $apiSecret . "' />\n";
    echo "<div class='submit'><input type='submit' value='Save Changes' /></div>\n";
    echo "<br />\n";
    echo "<div style='text-decoration: underline;'>Thumbnail Image Size</div>\n";
    echo "<div style='width: 35%; float: left;'>\n";
    echo "<div><input name='ws_hover_size' id='RequestMicroSize' value='micro' onchange='ws_set_sample(75,56);' type='radio' " . ($hoverSize == 'micro' ? "checked=''" : "") . "><label for='RequestMicroSize'>Micro (75 x 56)</label></div>\n";
    echo "<div><input name='ws_hover_size' id='RequestTinySize' value='tiny' onchange='ws_set_sample(90,68);' type='radio' " . ($hoverSize == 'tiny' ? "checked=''" : "") . "><label for='RequestTinySize'>Tiny (90 x 68)</label></div>\n";
    echo "<div><input name='ws_hover_size' id='RequestVerySmallSize' value='verysmall' onchange='ws_set_sample(100,75);' type='radio' " . ($hoverSize == 'verysmall' ? "checked=''" : "") . "><label for='RequestVerySmallSize'>Very Small (100 x 75)</label></div>\n";
    echo "<div><input name='ws_hover_size' id='RequestSmallSize' value='small' onchange='ws_set_sample(120,90);' type='radio' " . ($hoverSize == 'small' ? "checked=''" : "") . "><label for='RequestSmallSize'>Small (120 x 90)</label></div>\n";
    echo "<div><input name='ws_hover_size' id='RequestLargeSize' value='large' onchange='ws_set_sample(200,150);' type='radio' " . ($hoverSize == 'large' ? "checked=''" : "") . "><label for='RequestLargeSize'>Large (200 x 150)</label></div>\n";
    echo "<div><input name='ws_hover_size' id='RequestXLargeSize' value='xlarge' onchange='ws_set_sample(320,240);' type='radio' " . ($hoverSize == 'xlarge' ? "checked=''" : "") . "><label for='RequestXLargeSize'>Extra Large (320 x 240)</label></div>\n";
    echo "</div>\n";
    echo "<div id='wsSample' style='float: left; background-color: lightgrey; border: 2px solid black; width: 0; height: 0; display: none;'><center>Sample</center></div>\n";
    echo "<div style='clear: both;' class='submit'><input type='submit' value='Save Changes' /></div>\n";
    echo "</form>\n";

    echo "</div>\n";

    echo "<script type=\"text/javascript\">\n";
    echo "  var elements = document.getElementsByName('ws_hover_size');\n";
    echo "  for (var i = 0; i < elements.length; i++) {\n";
    echo "    if (elements[i].checked) {\n";
    echo "      elements[i].onchange();\n";
    echo "    }\n";
    echo "  }\n";
    echo "</script>\n";
}

function webshotr_hook_post_links($content)
{
    $settings = webshotr_get_db_settings();
    $isHoverEnabled = ($settings['hover_enabled'] == 1) ? true : false;

    if (!$isHoverEnabled)
        return $content;

    // Add our class to links that already have a class
    $content = preg_replace('/(<a.*?\s*class\s*=\s*["|\'].*?)(["|\'].*?>)/i', '$1 webshotr_post_hover $2', $content);
    // Add our class to links that don't already have a class
    $content = preg_replace('/(<a.*?)>(?<! class)/i', '$1 class="webshotr_post_hover">', $content);

    return $content;
}

add_action('wp_enqueue_scripts', 'webshotr_init');
add_action('admin_menu', 'webshotr_add_pages');

register_activation_hook(__FILE__, 'webshotr_init_db');
register_uninstall_hook(__FILE__, 'webshotr_uninstall_db');

add_filter('the_content', 'webshotr_hook_post_links');

?>
