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
add_action( 'admin_menu', 'generate_image_menu' );

/**
 * add page to admin panel menu
 */
function generate_image_menu():void
{
    add_options_page( 'Generate Image Options', 'Generate Image', 'manage_options', 'my-unique-identifier', 'generate_image_options' );
    //call register settings function
    add_action( 'admin_init', 'register_generate_image_settings' );
}

/**
 * register all plugin options
 */
function register_generate_image_settings()
{
    //register  settings
    register_setting( 'generate_image_settings-group', 'posts_number' );
}

/**
 * render plugin option page
 */
function generate_image_options()
{
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
?>

    <div class="wrap">
<h1>Generate image</h1>
<h2>Set up options</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'generate_image_settings-group' ); ?>
    <?php do_settings_sections( 'generate_image_settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Number of posts</th>
        <td><input type="text" name="posts_number" value="<?php echo esc_attr( get_option('posts_number') ); ?>" /></td>
        </tr>
<!---->
<!--        <tr valign="top">-->
<!--        <th scope="row">Some Other Option</th>-->
<!--        <td><input type="text" name="some_other_option" value="--><?php //echo esc_attr( get_option('some_other_option') ); ?><!--" /></td>-->
<!--        </tr>-->
<!---->
<!--        <tr valign="top">-->
<!--        <th scope="row">Options, Etc.</th>-->
<!--        <td><input type="text" name="option_etc" value="--><?php //echo esc_attr( get_option('option_etc') ); ?><!--" /></td>-->
<!--        </tr>-->
    </table>

    <?php submit_button(); ?>

</form>
</div>
<?php
}


add_action('wp_ajax_generate_image', 'generate_image');

function generate_image()
{
    global $wpdb; // this is how you get access to the database

    $res = 'Good';
    try {
        $postId = intval($_POST['post_id']);
        $post   = get_post($postId);

        $resultImagePath     = sprintf("public/images/result/%s.jpg", $post->post_title);
        $resultImageFullPath = __DIR__ . DIRECTORY_SEPARATOR . $resultImagePath;
        $imageGenerator      = new ImageGenerator();
        $result              = $imageGenerator
            ->setSourceImagePath(__DIR__ . DIRECTORY_SEPARATOR . 'public/images/source/tv.jpg')
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

        $res = wp_get_attachment_url($attachmentId);
//    echo '<img src="' . $resultImagePath . '">';

    } catch (Exception $exception) {
        $res = $exception->getMessage();
    }
    //response
    echo $res;//sprintf('<img src="%s">', $res);

    wp_die(); // this is required to terminate immediately and return a proper response
}

## Добавляем блоки в основную колонку на страницах постов и пост. страниц
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
    </script> <?php
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
    if ( !$upload_file['error']) {
        $wp_filetype   = wp_check_filetype($filename, null);
        $attachment    = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_parent'    => $postId,
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $postId);
        if ( !is_wp_error($attachment_id)) {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
        }
    }

//    return $upload_file['url'];
    return $attachment_id;
}
