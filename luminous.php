<?php
/*
  Plugin Name: Luminous
  Description: A syntax highlighting plugin using the Luminous engine.
  Plugin URI: https://github.com/markwatkinson/wp-luminous
  Author: Mark Watkinson
  Version: 1.0
  Author URI: http://asgaard.co.uk
  License: WTFPL <http://en.wikipedia.org/wiki/WTFPL>  
*/

require_once(dirname(__FILE__) . '/luminous/luminous.php');

add_action('init', 'luminouswp::init');
add_action('wp_head', 'luminouswp::head');

// this does the actual stuff, and then sticks in another shortcode with just
// an md5 referencing the output... this occurs before any Wordpress formatting
add_filter('the_content', 'luminouswp::sourcecode', 9);

// this is then executed later, after Wordpress adds in all its formatting, 
// and replaces the shortcode/md5 with the output.
add_shortcode('sourcecode', 'luminouswp::shortcode2');

add_filter('the_excerpt', 'luminouswp::excerpt');
add_action('admin_menu', 'luminouswp::create_menu');




$luminouswp_table = array();

abstract class luminouswp {
  static $options = array(
  'height'=> 500,
  'theme'=>'geonyx.css',
  'path' => '/wp-content/plugins/luminous/luminous/',
  'css' => '.luminous-container {
  border: 1px dashed #aaa;
  padding: 0px;
  margin: 0px;
  display: inline-block;
  min-width: 85%;
  max-width: 100%;
  font-size: inherit;
}
.luminous-container a, .luminous-container a:hover {
  background-color: transparent;
  text-decoration: none;
  font-weight: normal;
  border: 0px;
}');

  static function init() {
    add_option('luminous', self::$options);

    if (count($_POST) && isset($_POST['luminous_hidden']))
      self::save_settings();

    $options = get_option('luminous');

    luminous::set('theme', $options['theme']);
    luminous::set('max-height', $options['height']);
    luminous::set('relative-root', $options['path']);
  }

  static function head() {
    $options = get_option('luminous');
    echo luminous::head_html();
    echo '<!-- Luminous custom CSS //-->' . "\n";
    echo '<style type="text/css"> /*<![CDATA[*/'
      .  htmlspecialchars($options['css'])
      . '/*]]>*/</style>' . "\n";
  }


  // this removes the code from the 'excerpt' view. It just works better this
  // way.
  static function excerpt_shortcode($attrs, $content=null) {
    return '';
  }

  static function excerpt($content) {
    global $shortcode_tags;
    // Backup current registered shortcodes and clear them all out
    $orig_shortcode_tags = $shortcode_tags;
    $shortcode_tags = array();
    add_shortcode('sourcecode', 'luminouswp::excerpt_shortcode');
    $content = do_shortcode( $content );

    // Put the original shortcodes back
    $shortcode_tags = $orig_shortcode_tags;
    return $content;
  }

  static function highlight($language, $source, $options) {
    $out = '';
    try {
      $out = luminous::highlight($language, $source);
    } catch(Exception $e) {
      $out = htmlspecialchars($source);
    }
    $div = "<div class='luminous-container'>$out</div>";
    return $div;
  }

  static function sourcecode($content) {
    global $shortcode_tags;

    // Backup current registered shortcodes and clear them all out
    $orig_shortcode_tags = $shortcode_tags;
    $shortcode_tags = array();

    add_shortcode('sourcecode', 'luminouswp::shortcode');

    $content = do_shortcode( $content );

    // Put the original shortcodes back
    $shortcode_tags = $orig_shortcode_tags;
    return $content;
  }

  static function shortcode($atts, $content = null )
  {
    global $luminouswp_table;
    extract( shortcode_atts( array(
      'language' => 'plain',
      'height' => '',
      'escaped' => 'false'
    ), $atts ) );

    // trim the first line and the last line

    $content = preg_replace("/\r\n|\n|\r/", "\n", $content);
    if (($pos = strpos($content, "\n")) !== false)
      $content = substr($content, $pos+1);

    if (($pos = strrpos($content, "\n")) !== false)
      $content = substr($content, 0, $pos);

    $options = array();

    if ($height !== '')
      $options['height'] = $height;
    if ($escaped === "true")
      $content = htmlspecialchars_decode($content);

    $src = self::highlight($language, $content, $options);

    // we now hide this behind an MD5 to stop anyone messing with the
    // formatting
    $md5 = md5($src);
    $luminouswp_table[$md5] = $src;
    return "[sourcecode md5=$md5]";
  }

  // sub back in the REAL highlighted code in place of the MD5.
  static function shortcode2($atts, $content=null) {
    global $luminouswp_table;
    extract( shortcode_atts( array(
      'md5' => ''
    ), $atts ) );

    if ($md5 !== '' && isset($luminouswp_table[$md5]))
      return $luminouswp_table[$md5];
    else return ''; // should never happen
  }

  // create custom plugin settings menu
  static function create_menu() {
    add_submenu_page('plugins.php', 'Luminous Settings', 'Luminous settings',
      'administrator', 'luminous-handle', 'luminouswp::settings_view');
  }

  static function settings_view() {
    $options = get_option('luminous');
    $default_theme = $options['theme'];
    $height = $options['height'];
    $css = $options['css'];
    $path = $options['path'];

    ?>
    <div class="wrap">
    <h2>Luminous</h2>

    <form method="post" action="<?php
      $uri = htmlentities($_SERVER['PHP_SELF']);
      $uri .= "?";
      foreach($_GET as $k=>$v)
        $uri .= "$k=$v&";
      $uri = rtrim($uri, "&");
      echo $uri;
      ?>">
    <table class="form-table">
      <tr valign='top'>
      <th scope="row">Theme</th>
      <td><select name="theme">
      <?php foreach(luminous::themes() as $theme): ?>
      <option name="<?=htmlspecialchars($theme)?>" <?=
        ($theme === $default_theme)? 'selected' :'' ?>>
        <?=htmlspecialchars($theme)?>
      </option>
      <?php endforeach;?>
      </select>
      </td></tr>
      <tr valign="top">
      <th scope="row">Maximum widget height (pixels)</th>
      <td><input type="text" name="height" value="<?= htmlspecialchars($height); ?>"/></td>
      </tr>
      <tr valign="top">
      <th scope="row">Server path (for CSS includes)</th>
      <td><input style='width:20em' type="text" name="path" value="<?= htmlspecialchars($path); ?>"/></td>
      </tr>
      <tr valign="top">
      <th scope="row">Custom CSS</th>
      <td><textarea name='css'  rows=20 cols=40 ><?=htmlspecialchars($css)?></textarea></td>
      </tr>
    </table>
    <p class="submit">
    <input type='hidden' name='luminous_hidden'>
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>
    </form>
    </div>
  <?php
  } // end function

  static function save_settings() {
    if (!current_user_can('manage_options'))
      return;

    $settings = get_option($luminous);
    $settings['height'] = $_POST['height'];
    $settings['theme'] = $_POST['theme'];
    $settings['css'] = $_POST['css'];
    $settings['path'] = $_POST['path'];
    update_option('luminous', $settings);
  }
}