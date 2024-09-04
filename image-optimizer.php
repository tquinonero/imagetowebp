<?php
/*
Plugin Name: My Image Optimizer
Description: A plugin to optimize images and serve WebP format if supported by the browser.
Version: 1.3
Author: Toni Q.
*/

add_filter('wp_handle_upload', 'convert_image_to_webp_and_replace', 10, 1);

function convert_image_to_webp_and_replace($file) {
    // Proceed only for PNG or JPEG images
    if ($file['type'] === 'image/png' || $file['type'] === 'image/jpeg' || $file['type'] === 'image/jpg') {
        $file_path = $file['file'];
        $webp_path = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $file_path);

        if ($file['type'] === 'image/png') {
            $img = imagecreatefrompng($file_path);
        } elseif ($file['type'] === 'image/jpeg' || $file['type'] === 'image/jpg') {
            $img = imagecreatefromjpeg($file_path);
        }

        if ($img) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            imagewebp($img, $webp_path, 100);
            imagedestroy($img);

            // Rename the WebP file to match the original filename pattern
            $upload_dir = wp_upload_dir();
            $original_filename = basename($file_path);
            $webp_basename = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $original_filename);

            $final_webp_path = trailingslashit($upload_dir['path']) . $webp_basename;

            // Rename the WebP file to remove the resolution suffix
            if (rename($webp_path, $final_webp_path)) {
                // Update the file array to use the WebP file
                $file['file'] = $final_webp_path;
                $file['url'] = trailingslashit($upload_dir['url']) . $webp_basename;

                // Delete the old PNG or JPEG file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
    }

    return $file;
}