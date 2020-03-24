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

add_action('wp_ajax_generate_image', 'generate_image');

function generate_image()
{
    global $wpdb; // this is how you get access to the database

    $res = 'Good';
    try {
        $postId = intval($_POST['post_id']);
        $post   = get_post($postId);

        $resultImagePath     = 'public/images/result/digi.jpg';
        $resultImageFullPath = __DIR__ . DIRECTORY_SEPARATOR . $resultImagePath;
        $imageGenerator      = new ImageGenerator();
        $result              = $imageGenerator
            ->setSourceImagePath(__DIR__ . DIRECTORY_SEPARATOR . 'public/images/source/tv.jpg')
            ->setResultImagePath($resultImageFullPath)
            ->setFontPath(__DIR__ . DIRECTORY_SEPARATOR . 'public/font/merriweatherregular.ttf')
            ->setTextColor(new Color(255, 255, 255))
            ->setTextFontSize(25)
            ->setText($post->post_title)//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
            ->setCoeficientLeftRightTextPadding(20)
            ->setTextLinesTopBottomPadding(15)
            ->setImageQuality(100)
            ->generate();

//    echo '<img src="' . $resultImagePath . '">';

    } catch (Exception $exception) {
        $res = $exception->getMessage();
    }
    //response
    echo $res;

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

//    // значение поля
//    $value = get_post_meta( $post->ID, 'my_meta_key', 1 );

    if ( !has_post_thumbnail($post->ID)) {
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
                        alert('Done!' + response);
                    });
                });
            });
        </script> <?php
        $html = '<div id="major-publishing-actions" style="overflow:hidden">';
        $html .= '<div id="publishing-action">';
        $html .= '<input type="submit" accesskey="p" tabindex="5" value="Customize Me!" class="button-primary" id="generate_image" name="publish">';
        $html .= '</div>';
        $html .= '</div>';

        echo $html;
    }

//    // Поля формы для введения данных
//    echo '<label for="myplugin_new_field">' . __("Description for this field", 'myplugin_textdomain' ) . '</label> ';
//    echo '<input type="text" id="myplugin_new_field" name="myplugin_new_field" value="'. $value .'" size="25" />';
}
