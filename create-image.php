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
add_action('post_submitbox_misc_actions', 'custom_button');

function custom_button($post): string
{
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
}


add_action('wp_ajax_generate_image', 'generate_image');

function my_action()
{
    global $wpdb; // this is how you get access to the database

    $postId = intval($_POST['post_id']);


    $resultImagePath     = 'images/result/law2.jpg';
    $resultImageFullPath = __DIR__ . DIRECTORY_SEPARATOR . $resultImagePath;
    $imageGenerator      = new ImageGenerator();
    $result              = $imageGenerator
        ->setSourceImagePath(__DIR__ . '/images/source/law.jpg')
        ->setResultImagePath($resultImageFullPath)
        ->setFontPath('font/merriweatherregular.ttf')
        ->setTextColor(new Color(255, 255, 255))
        ->setTextFontSize(25)
        ->setText('Взыскание долга 2')//"Взыскание долга";//"Взыскание долга, неустойки, дебиторской задолженности";
        ->setCoeficientLeftRightTextPadding(20)
        ->setTextLinesTopBottomPadding(15)
        ->setImageQuality(100)
        ->generate();
    echo '<img src="' . $resultImagePath . '">';

    //response
//    echo $whatever;

    wp_die(); // this is required to terminate immediately and return a proper response
}