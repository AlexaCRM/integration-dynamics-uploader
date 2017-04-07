<?php
/**
 * @var array $imageUrls List of {annotationId => imageUrl} associations
 * @var int $columnWidth Gallery column width
 * @var int $columnGutter Gallery column gutter
 */

?>
<style>
    .wpcrm-media-gallery {
        display: block;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .wpcrm-media-gallery li {
        display: block;
        margin: 0 0 <?php echo $columnGutter; ?>px;
        padding: 0;
        min-width: <?php echo $columnWidth; ?>px;
        min-height: 72px;
        background: url(/wp-content/plugins/integration-dynamics/resources/front/images/progress.gif) no-repeat center;
    }
    .wpcrm-media-gallery li .form-group {
        display: none;
        position: absolute;
        bottom: 5px;
        right: 5px;
        margin-bottom: 0;
    }
    .wpcrm-media-gallery li:hover .form-group {
        display: block;
    }

    .dropzone {
        height: auto;
        text-align: center;
        padding: 15px;
        margin: 15px 0;
        cursor: pointer;
        border-color: #333;
        background-color: #eceeef;
    }
</style>

<ul class="wpcrm-media-gallery">
    <?php foreach ( $imageUrls as $imageId => $imageUrl ) {
        ?><li data-id="<?php echo esc_attr( $imageId ); ?>">
        <img src="<?php echo esc_attr( $imageUrl ); ?>" width="<?php echo esc_attr( $columnWidth ); ?>">
        <div class="form-group">
            <button class="btn btn-default wpcrm-remove"><?php _e( 'Remove', 'integration-dynamics-uploader' ); ?></button>
        </div>
        </li><?php
    } ?>
</ul>

<script>
    ( function( $ ) {
        var $body = $( 'body' );
        $( function() {
            var $gallery = $( '.wpcrm-media-gallery' );

            $gallery.isotope( {
                itemSelector: 'li',
                masonry: {
                    columnWidth: <?php echo esc_js( $columnWidth ); ?>,
                    gutter: <?php echo esc_js( $columnGutter ); ?>
                }
            } );

            $gallery.imagesLoaded().progress( function() {
                $gallery.isotope( 'layout' );
            } );

            Dropzone.options.wpcrmUploader = {
                init: function() {
                    var dz = this;

                    dz.on( 'complete', function( file ) {
                        if ( file.status === 'error' ) {
                            dz.removeFile( file );
                            alert( file.name + ' - <?php echo esc_js( __( 'File size must not exceed 4 megabytes.', 'integration-dynamics-uploader' ) ); ?>' );
                            return;
                        }

                        var imageId = file.xhr.response, $el;
                        dz.removeFile( file );

                        $el = $( '<li data-id="' + imageId + '"><img src="<?php echo esc_attr( admin_url( 'admin-ajax.php?action=msdyncrm_image&width=' . $columnWidth . '&id=' ) ); ?>' + imageId + '" width="<?php echo esc_attr( $columnWidth ); ?>"><div class="form-group"><button class="btn btn-default wpcrm-remove"><?php echo esc_js( __( 'Remove', 'integration-dynamics-uploader' )) ?></button></div></li>' ).imagesLoaded().progress( function() {
                            $gallery.append( $el );
                            $gallery.isotope( 'appended', $el );
                        } );
                    } );
                },
                maxFilesize: 4, // CRM limit if 4MB
                dictDefaultMessage: '<?php echo esc_js( __( 'Click here to select files or drag and drop files here to upload', 'integration-dynamics-uploader' ) ); ?>',
                previewTemplate: '<div><?php echo sprintf( __( '"%s" is being uploaded...', 'integration-dynamics-uploader' ), '<span data-dz-name></span>' ); ?> <img src="<?php echo esc_attr( plugins_url( 'integration-dynamics/resources/front/images/progress.gif' ) ); ?>" width="18" height="18" alt="<?php echo esc_js( __( 'Uploading...', 'integration-dynamics-uploader' ) ); ?>"></div>'
            };

            $body.on( 'click', '.wpcrm-media-gallery .wpcrm-remove', function() {
                var $btn = $( this ), $img = $btn.parents( '.wpcrm-media-gallery li' ),
                    imageId = $img.data( 'id' );

                wp.ajax.send( 'wpcrm_upload_remove', {
                    data: {
                        _ajax_nonce: <?php echo json_encode( wp_create_nonce( 'wpcrm_upload_remove' ) ); ?>,
                        annotationId: imageId
                    }
                } ).done( function() {
                    $gallery.isotope( 'remove', $img ).isotope( 'layout' );
                } )
                    .fail( function() {
                        alert( '<?php echo esc_js( __( 'Failed to remove the image.', 'integration-dynamics-uploader' ) ); ?>' );
                    } );
            } );
        } );
    } ( jQuery ) );
</script>
