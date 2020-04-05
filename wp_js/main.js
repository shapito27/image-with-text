jQuery(document).ready(function ($) {

    jQuery('input#background_image_media_manager').click(function (e) {

        e.preventDefault();
        var image_frame;
        if (image_frame) {
            image_frame.open();
        }
        // Define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple: false,
            library: {
                type: 'image',
            }
        });

        image_frame.on('close', function () {
            // On close, get selections and save to the hidden input
            // plus other AJAX stuff to refresh the image preview
            var selection = image_frame.state().get('selection');
            var gallery_ids = new Array();
            var my_index = 0;
            selection.each(function (attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            var ids = gallery_ids.join(",");
            jQuery('input#background_image_id').val(ids);
            Refresh_Image(ids);
        });

        image_frame.on('open', function () {
            // On open, get the id from the hidden input
            // and select the appropiate images in the media manager
            var selection = image_frame.state().get('selection');
            var ids = jQuery('input#background_image_id').val().split(',');
            ids.forEach(function (id) {
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
            });

        });

        image_frame.open();
    });

    jQuery('input#run-generation').click(function (e) {

        e.preventDefault();

        var data = {
            action: 'generate_images',
            withImages: false,
            count: jQuery('input#posts_number').val()
        };

        jQuery.get(ajaxurl, data, function (response) {
            response = jQuery.parseJSON( response );
            if (response.success === true) {
                // jQuery('#generated-images-wrap').replaceWith(response['posts']);
                $.each(response['posts'], function (index, value) {
                    var title = value['title'],
                        edit = value['url'],
                        src = value['image_src'],
                        block = '<div><a target="_blank" href="' + edit + '"><span>' + title + '</span></a><br><a target="_blank" href="' + src + '"><img src="' + src + '"></a></div>';

                    jQuery('#generated-images-wrap').append(block);
                });
            }else{
                jQuery('#generated-images-wrap').replaceWith('Error! ' + response['error']);
            }
        });
    });
});

// Ajax request to refresh the image preview
function Refresh_Image(the_id) {
    var data = {
        action: 'myprefix_get_image',
        id: the_id
    };

    jQuery.get(ajaxurl, data, function (response) {

        if (response.success === true) {
            jQuery('#myprefix-preview-image').replaceWith(response.data.image);
        }
    });
}