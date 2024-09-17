<?php
/*
Plugin Name: My Image Optimizer
Description: Converts uploaded images to WebP and serves the appropriate version based on browser support.
Version: 1.4
Author: Toni Q
*/

// Hook into WordPress upload process
add_filter('wp_handle_upload', 'sio_convert_to_webp');

function sio_convert_to_webp($file) {
    // Check if the uploaded file is an image
    $image_types = array('image/jpeg', 'image/png');
    if (!in_array($file['type'], $image_types)) {
        return $file;
    }

    // Load the image based on its type
    $image = ($file['type'] === 'image/jpeg') ? imagecreatefromjpeg($file['file']) : imagecreatefrompng($file['file']);

    // Get WebP quality setting
    $quality = get_option('sio_webp_quality', 80);

    // Generate WebP filename
    $webp_file = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file['file']);

    // Convert to WebP with specified quality
    imagewebp($image, $webp_file, $quality);

    // Free up memory
    imagedestroy($image);

    return $file;
}

// Add settings page
add_action('admin_menu', 'sio_add_settings_page');
add_action('admin_init', 'sio_register_settings');

function sio_add_settings_page() {
    add_options_page('Simple Image Optimizer Settings', 'Image Optimizer', 'manage_options', 'sio-settings', 'sio_settings_page');
}

function sio_register_settings() {
    register_setting('sio_settings_group', 'sio_webp_quality');
}

function sio_settings_page() {
    ?>
    <div class="wrap">
        <h1>Simple Image Optimizer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('sio_settings_group'); ?>
            <?php do_settings_sections('sio_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">WebP Quality (0-100)</th>
                    <td><input type="number" name="sio_webp_quality" value="<?php echo esc_attr(get_option('sio_webp_quality', 80)); ?>" min="0" max="100" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Hook into WordPress to modify image URLs
add_filter('wp_get_attachment_image_src', 'sio_modify_image_src', 10, 4);
add_filter('wp_calculate_image_srcset', 'sio_modify_image_srcset', 10, 5);

function sio_modify_image_src($image, $attachment_id, $size, $icon) {
    if (sio_browser_supports_webp()) {
        $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $image[0]);
        if (file_exists(str_replace(home_url(), ABSPATH, $webp_url))) {
            $image[0] = $webp_url;
        }
    }
    return $image;
}

function sio_modify_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    if (sio_browser_supports_webp()) {
        foreach ($sources as &$source) {
            $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source['url']);
            if (file_exists(str_replace(home_url(), ABSPATH, $webp_url))) {
                $source['url'] = $webp_url;
            }
        }
    }
    return $sources;
}

function sio_browser_supports_webp() {
    return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
}
