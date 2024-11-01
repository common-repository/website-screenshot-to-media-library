<?php
/**
 * Website Screenshot to Media Library
 *
 * @package       WSML
 * @author        Ranuka Herath
 * @license       gplv2
 * @version       1.1.0
 *
 * @wordpress-plugin
 * Plugin Name:   Website Screenshot to Media Library
 * Plugin URI:    https://ranuka.dev/
 * Description:   Website Screenshot to Media Library
 * Version:       1.1.0
 * Author:        Ranuka Herath
 * Author URI:    https://ranuka.dev/
 * Text Domain:   website-screenshot-to-media-library
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with Website Screenshot to Media Library. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


//Create the main menu page
function wsml_screenshot_menu_page() {
  add_menu_page(
    'Screenshot', //Page title
    'Screenshot', //Menu title
    'manage_options', //Capabilities
    'screenshot', //Slug
    'wsml_screenshot_get_menu_page_callback', //Display callback
    'dashicons-format-image', //Icon URL
    3 //Position
  );
}
add_action( 'admin_menu', 'wsml_screenshot_menu_page' );


//Create the Get Screenshot sub-menu page
function wsml_screenshot_get_menu_page() {
  add_submenu_page(
    'wsml-screenshot-get', //Parent slug
    'Get Screenshot', //Page title
    'Get Screenshot', //Menu title
    'manage_options', //Capabilities
    'wsml-screenshot-get', //Slug
    'wsml_screenshot_get_menu_page_callback' //Display callback
  );
}
add_action( 'admin_menu', 'wsml_screenshot_get_menu_page' );

//Display the Get Screenshot sub-menu page content
function wsml_screenshot_get_menu_page_callback() {

  $error = '';
  $success = '';


  if(isset($_POST['wsml_action']) && $_POST['wsml_action'] == 'get_screenshot'){

    $wsml_screenshot_api_key = get_option( 'wsml_screenshot_api_key' );

    $wsml_url = filter_input(INPUT_POST, 'wsml_screenshot_website_url', FILTER_SANITIZE_URL);
    $wsml_viewport_width = filter_input(INPUT_POST, 'wsml_screenshot_viewport_width', FILTER_SANITIZE_NUMBER_INT);
    $wsml_viewport_height = filter_input(INPUT_POST, 'wsml_screenshot_viewport_height', FILTER_SANITIZE_NUMBER_INT);
    $wsml_delay = filter_input(INPUT_POST, 'wsml_delay', FILTER_SANITIZE_NUMBER_INT);
    $wsml_format = filter_input(INPUT_POST, 'wsml_screenshot_image_format', FILTER_SANITIZE_STRING);
    $wsml_filename = ucwords(filter_input(INPUT_POST, 'wsml_image_name', FILTER_SANITIZE_STRING));



    if(empty($wsml_url) || empty($wsml_viewport_width) || empty($wsml_viewport_height) || empty($wsml_format) || empty($wsml_filename)) {
      $error = 'Please provide all the required fields.';
    } else {
      $args = array(
        'timeout' => 30,
      );

      $response = wp_remote_get( 'https://api.screenshotone.com/take?access_key='.$wsml_screenshot_api_key.'&url=' . urlencode($wsml_url) . '&device_scale_factor=1&format=' . $wsml_format . '&cache=true&viewport_width=' . $wsml_viewport_width . '&viewport_height=' . $wsml_viewport_height, $args );
      $response = wp_remote_get( 'https://api.screenshotone.com/take?access_key='.$wsml_screenshot_api_key.'&url=' . urlencode($wsml_url) . '&device_scale_factor=1&format=' . $wsml_format . '&cache=true&viewport_width=' . $wsml_viewport_width . '&delay=' . $wsml_delay . '&viewport_height=' . $wsml_viewport_height, $args );

      if( is_wp_error( $response ) ) {
        $error = 'Screenshot API error: ' . $response->get_error_message();
      } else {
        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );
        if(isset($response_data['error_message'])) {
          $error = 'Screenshot API error: ' . $response_data['error_message'];
        } else {
          $upload_file = wp_upload_bits( $wsml_filename . '.' . $wsml_format, null, wp_remote_retrieve_body( $response ) );

          if ( !$upload_file['error'] ) {
            $wp_filetype = wp_check_filetype( $wsml_filename . '.' . $wsml_format, null );
            $attachment = array(
              'post_mime_type' => $wp_filetype['type'],
              'post_title' => sanitize_file_name( $wsml_filename ),
              'post_content' => '',
              'post_status' => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $upload_file['file'] );
          
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $upload_file['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
          
            // Generate a link to the attachment page in the WordPress admin
            $admin_url = admin_url( 'upload.php?item=' . $attach_id );
          
            // Update the success message to include the link to the admin page
            $success = 'Screenshot saved successfully. <a href="' . esc_url($admin_url) . '" target="_blank">View Screenshot in Media Library</a>';
          } else {
            $error = 'Error uploading the screenshot: ' . $upload_file['error'];
          }          
          
        }
      }

    }
  }


  ?>
  <div class="wrap">
    <h1>Get Screenshot</h1>

    <?php
    if(!empty($error)){
      echo '<div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 10px;">'.esc_attr($error).'</div>';
    }

    if(!empty($success)){
      $allowed_html = array(
          'a' => array(
              'href' => array(),
              'target' => array(),
          )
      );
      
      echo '<div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 10px;">' . wp_kses($success, $allowed_html) . '</div>';
    
    }
    ?>
    <form method="post">
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">Viewport Width</th>
            <td>
              <input required type="text" name="wsml_screenshot_viewport_width" value="<?php echo esc_attr(get_option( 'wsml_screenshot_viewport_width' )); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">Viewport Height</th>
            <td>
              <input required type="text" name="wsml_screenshot_viewport_height" value="<?php echo esc_attr(get_option( 'wsml_screenshot_viewport_height' )); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">Delay (Specify the delay option in seconds to wait before taking a screenshot)</th>
            <td>
              <input required type="number" name="wsml_delay" value="0" min="0">
            </td>
          </tr>
          <tr>
            <th scope="row">Website URL</th>
            <td>
              <input required type="url" name="wsml_screenshot_website_url">
            </td>
          </tr>
          <tr>
            <th scope="row">Image Name</th>
            <td>
              <input required type="text" name="wsml_image_name">
            </td>
          </tr>
          <tr>
            <th scope="row">Image Format</th>
            <td>
              <select required name="wsml_screenshot_image_format">
                <option value="jpg" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'jpg' ); ?>>jpg</option>
                <option value="png" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'png' ); ?>>png</option>
                <option value="webp" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'webp' ); ?>>webp</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
      <input type="hidden" name="wsml_action" value="get_screenshot">
      <input type="submit" value="Get Screenshot">
    </form>
  </div>
  <?php
}

//Create the Settings sub-menu page
function wsml_screenshot_settings_menu_page() {
  add_submenu_page(
    'screenshot', //Parent slug
    'Settings', //Page title
    'Settings', //Menu title
    'manage_options', //Capabilities
    'wsml-screenshot-settings', //Slug
    'wsml_screenshot_settings_menu_page_callback' //Display callback
  );
}
add_action( 'admin_menu', 'wsml_screenshot_settings_menu_page' );

//Display the Settings sub-menu page content
function wsml_screenshot_settings_menu_page_callback() {
  ?>
  <div class="wrap">
    <h1>Screenshot Settings</h1>
    <form method="post" action="options.php">
      <?php settings_fields( 'wsml_screenshot_settings' ); ?>
      <table class="form-table">
        <tbody>
          <tr>
            <th scope="row">API Key</th>
            <td>
              <input type="password" name="wsml_screenshot_api_key" value="<?php echo esc_attr(get_option( 'wsml_screenshot_api_key' )); ?>">
              <p style="font-style: italic; color: #777;">Get the API key from <a target="_blank" href="https://screenshotone.com/">https://screenshotone.com/</a></p>
            </td>
          </tr>
          <tr>
            <th scope="row">Viewport Width (Default)</th>
            <td>
              <input type="text" name="wsml_screenshot_viewport_width" value="<?php echo esc_attr(get_option( 'wsml_screenshot_viewport_width' )); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">Viewport Height (Default)</th>
            <td>
              <input type="text" name="wsml_screenshot_viewport_height" value="<?php echo esc_attr(get_option( 'wsml_screenshot_viewport_height' )); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">Image Format (Default)</th>
            <td>
              <select name="wsml_screenshot_image_format">
                <option value="jpg" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'jpg' ); ?>>jpg</option>
                <option value="png" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'png' ); ?>>png</option>
                <option value="webp" <?php selected( get_option( 'wsml_screenshot_image_format' ), 'webp' ); ?>>webp</option>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
      <?php submit_button(); ?>
    </form>
  </div>
  <?php
}

//Register the settings
function wsml_screenshot_settings_register() {
  register_setting( 'wsml_screenshot_settings', 'wsml_screenshot_api_key' );
  register_setting( 'wsml_screenshot_settings', 'wsml_screenshot_viewport_width' );
  register_setting( 'wsml_screenshot_settings', 'wsml_screenshot_viewport_height' );
  register_setting( 'wsml_screenshot_settings', 'wsml_screenshot_image_format' );
}
add_action( 'admin_init', 'wsml_screenshot_settings_register' );
