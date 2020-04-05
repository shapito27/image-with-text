<?php

require_once __DIR__ . '/vendor/autoload.php';

use Shapito27\ImageCreator\Services\ImageGenerator;
use Shapito27\ImageCreator\Models\Color;

/**
 * Plugin Name: Create Post Feature Image
 * Plugin URI: https://thisis-blog.ru/
 * Description: Creates uniq feature image for posts
 * Version: 1.0
 * Author: Saifullin Ruslan
 * Author URI: https://thisis-blog.ru/
 **/

//to add menu item
add_action('admin_menu', 'generate_image_menu');

/**
 * add page to admin panel menu
 */
function generate_image_menu(): void
{
    add_options_page(
        'Generate Image Options',
        'Generate Image',
        'manage_options',
        'my-unique-identifier',
        'generate_image_options'
    );
    //call register settings function
    add_action('admin_init', 'register_generate_image_settings');
}

/**
 * register all plugin options
 */
function register_generate_image_settings()
{
    //register  settings
    register_setting('generate_image_settings-group', 'posts_number');
    register_setting('generate_image_settings-group', 'background_image_id');
}

/**
 * render plugin option page
 */
function generate_image_options()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    wp_enqueue_media();
    // Enqueue custom script that will interact with wp.media
    wp_enqueue_script('myprefix_script', plugins_url('/wp_js/main.js', __FILE__), array('jquery'), '0.1');
    ?>

    <div class="wrap">
        <h1>Generate image</h1>
        <h2>Set up options</h2>

        <form method="post" action="options.php">
            <?php settings_fields('generate_image_settings-group'); ?>
            <?php do_settings_sections('generate_image_settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Number of posts to update at once</th>
                    <td><input type="text" name="posts_number" id="posts_number"
                               value="<?php echo esc_attr(get_option('posts_number')); ?>"/></td>
                </tr>

                <tr valign="top">
                    <th scope="row">Background image</th>
                    <td>
                        <?php
                        $image_id = get_option('background_image_id');
                        if (intval($image_id) > 0) {
                            // Change with the image size you want to use
                            $image = wp_get_attachment_image(
                                $image_id,
                                'medium',
                                false,
                                array('id' => 'myprefix-preview-image')
                            );
                        } else {
                            // Some default image
                            $image = '<img id="myprefix-preview-image" src="" />';
                        }

                        echo $image;
                        ?>
                        <br>
                        <input type="hidden" name="background_image_id" id="background_image_id"
                               value="<?php echo esc_attr($image_id); ?>" class="regular-text"/>
                        <input type='button' class="button-primary"
                               value="<?php esc_attr_e('Select a image', 'generate_image'); ?>"
                               id="background_image_media_manager"/>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

        </form>

    </div>
    <div class="wrap">
        <h2>Run image generation</h2>
        <h3>Take first <?php echo esc_attr(get_option('posts_number')); ?> posts and generate images by using background
            image and Post title.</h3>
        <br>
        <input type='button' class="button-primary" value="<?php esc_attr_e('Generate', 'generate_image'); ?>"
               id="run-generation"/>
        <br>
        <div id="generated-images-wrap"></div>
    </div>
    <?php
}

// Ajax action to refresh the user image
add_action('wp_ajax_myprefix_get_image', 'myprefix_get_image');

function myprefix_get_image()
{
    $attachmentId = (int)$_GET['id'];
    if (isset($attachmentId) && is_int($attachmentId)) {
        $image = wp_get_attachment_image(
            filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT),
            'medium',
            false,
            array('id' => 'myprefix-preview-image')
        );
        $data  = array(
            'image' => $image,
        );
        wp_send_json_success($data);
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_generate_image', 'generate_image');

function generate_image()
{
    try {
        $postId       = intval($_POST['post_id']);
        $attachmentId = generateImageByPost($postId);
        $res          = wp_get_attachment_url($attachmentId);
    } catch (Exception $exception) {
        $res = $exception->getMessage();
    }
    //response
    echo $res;//sprintf('<img src="%s">', $res);

    wp_die(); // this is required to terminate immediately and return a proper response
}

/**
 * @param  int  $postId
 *
 * @return int
 */
function generateImageByPost(int $postId): int
{
//    global $wpdb; // this is how you get access to the database

    $post = get_post($postId);

    $resultImagePath = sprintf("public/images/result/%s.jpg", fileNameSanitaze($post->post_name));

    $resultImageFullPath = __DIR__ . DIRECTORY_SEPARATOR . $resultImagePath;
    $imageGenerator      = new ImageGenerator();
    $result              = $imageGenerator
        ->setSourceImagePath(get_attached_file(get_option('background_image_id')))
        ->setResultImagePath($resultImageFullPath)
        ->setFontPath(__DIR__ . DIRECTORY_SEPARATOR . 'public/font/merriweatherregular.ttf')
        ->setTextColor(new Color(255, 255, 255))
        ->setText($post->post_title)//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
        ->setCoeficientLeftRightTextPadding(20)
        ->setTextLinesTopBottomPadding(15)
        ->setImageQuality(100)
        ->generate();

    $attachmentId = saveImageToMedialibrary($resultImageFullPath, $postId);
    //set post's featured image
    add_post_meta($postId, '_thumbnail_id', $attachmentId);
    //set image alt
    update_post_meta($attachmentId, '_wp_attachment_image_alt', $post->post_title);

    return $attachmentId;
}

add_action('wp_ajax_generate_images', 'generate_images');

function generate_images()
{
    $count          = (int)$_GET['count'];
    $res            = null;
    $res['success'] = false;

    try {
        $args = array(
            'meta_query'  => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'numberposts' => $count,
        );

        $query = get_posts($args);

        if (count($query) === 0) {
            throw new RuntimeException("No posts without feature image.");
        }

        /**
         * @var WP_Post $post
         */
        foreach ($query as $post) {
            $attachmentId   = generateImageByPost($post->ID);
            $res['posts'][] = [
                'title'     => $post->post_title,
                'url'       => 'https://digit-tv.ru/wp-admin/post.php?post=' . $post->ID . '&action=edit',
                'image_src' => wp_get_attachment_url($attachmentId),
            ];
        }
        $res['success'] = true;
    } catch (Exception $exception) {
        $res['error'] = $exception->getMessage();
    }
    //response
    echo json_encode($res);

    wp_die(); // this is required to terminate immediately and return a proper response
}

// Добавляем блоки в основную колонку на страницах постов и пост. страниц
add_action('add_meta_boxes', 'myplugin_add_custom_box');
function myplugin_add_custom_box()
{
    $screens = [
        'post',
        //        'page'
    ];
    add_meta_box('myplugin_sectionid', 'Generate image', 'myplugin_meta_box_callback', $screens, 'side');
}

// HTML код блока
function myplugin_meta_box_callback($post)
{
    // Используем nonce для верификации
    wp_nonce_field(plugin_basename(__FILE__), 'create_image');
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            jQuery(document).on('click', '#generate_image', function (e) {
                e.preventDefault();

                var data = {
                    'action': 'generate_image',
                    'post_id': <?=$post->ID?>
                };

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    jQuery('#generated_image_link').attr("href", response);
                    jQuery('#generated_image').show().attr("src", response);
                });
            });
        });
    </script><?php
    $html = '<div id="major-publishing-actions" style="overflow:hidden">';
    $html .= '<div id="publishing-action">';
    $html .= '<input type="submit" accesskey="p" tabindex="5" value="Generate" class="button-primary" id="generate_image" name="publish">';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<a id="generated_image_link" target="_parent"><img style="display: none;" id="generated_image"></a>';

    echo $html;
}

/**
 * @param  string  $filePath
 * @param  int  $postId
 *
 * @return int attachment id
 */
function saveImageToMedialibrary(string $filePath, int $postId): int
{
    $filename = basename($filePath);

    $upload_file = wp_upload_bits($filename, null, file_get_contents($filePath));
    if (!$upload_file['error']) {
        $wp_filetype   = wp_check_filetype($filename, null);
        $attachment    = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_parent'    => $postId,
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $postId);
        if (!is_wp_error($attachment_id)) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }
    }

//    return $upload_file['url'];
    return $attachment_id;
}

/**
 * @param  string  $filename
 *
 * @return string
 */
function fileNameSanitaze(string $filename): string
{
    // Remove anything which isn't a word, whitespace, number
    // or any of the following caracters -_~,;[]().
    // If you don't need to handle multi-byte characters
    // you can use preg_replace rather than mb_ereg_replace
    // Thanks @Łukasz Rysiak!
    $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '-', $filename);
    if ($filename === false) {
        throw new RuntimeException('Problems with sanitize string ' . $filename);
    }
    // Remove any runs of periods (thanks falstro!)
    $filename = mb_ereg_replace("([\.]{2,})", '-', $filename);
    if ($filename === false) {
        throw new RuntimeException('Problems with sanitize string ' . $filename);
    }

    return $filename;
}
