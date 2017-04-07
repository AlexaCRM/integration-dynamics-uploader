<?php
/*
 * Plugin Name: Dynamics CRM Integration Uploader
 * Plugin URI: https://alexacrm.com/
 * Description: Image uploader for Dynamics CRM Integration.
 * Version: 1.1
 * Author: AlexaCRM
 * Author URI: https://alexacrm.com
 * Text Domain: integration-dynamics-uploader
 * Domain Path: /languages
 */

spl_autoload_register( function ( $className ) {
    $namespacePrefix = 'AlexaCRM\\WordpressCRM\\';

    $baseDirectory = __DIR__ . '/src/';

    $namespacePrefixLength = strlen( $namespacePrefix );
    if ( strncmp( $namespacePrefix, $className, $namespacePrefixLength ) !== 0 ) {
        return;
    }

    $relativeClassName = substr( $className, $namespacePrefixLength );

    $classFilename = $baseDirectory . str_replace( '\\', '/', $relativeClassName ) . '.php';

    if ( file_exists( $classFilename ) ) {
        require $classFilename;
    }
} );

load_plugin_textdomain( 'integration-dynamics-uploader', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

add_action( 'wp_enqueue_scripts', function() {
    wp_register_script( 'dropzone-js', plugins_url( 'integration-dynamics-uploader/resources/js/dropzone.min.js' ) );
    wp_register_script( 'isotope', plugins_url( 'integration-dynamics-uploader/resources/js/isotope.pkgd.min.js' ), [ 'jquery', 'imagesloaded', 'wp-util' ] );
} );

// Add uploader shortcode to the ShortcodeManager
add_filter( 'wordpresscrm_shortcodes', function( $shortcodes ) {
    $shortcodes['uploader'] = '\AlexaCRM\WordpressCRM\Uploader';

    return $shortcodes;
} );

// Add custom template storage
add_filter( 'wordpresscrm_locate_template', function( $template, $template_name, $template_path ) {
    if ( !file_exists( $template ) ) {
        return __DIR__ . '/templates/' . $template_name;
    }

    return $template;
}, 10, 3 );

add_action( 'wp_ajax_wpcrm_upload', 'wpcrm_media_uploader' );
add_action( 'wp_ajax_wpcrm_upload_remove', 'wpcrm_media_remove' );

function wpcrm_media_uploader() {
    check_ajax_referer( 'wpcrm_upload' );

    $request = ACRM()->request;

    /**
     * @var $file \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    $file = $request->files->get( 'file' );

    if ( is_null( $file ) ) {
        wp_die();
    }

    $base64 = base64_encode( file_get_contents( $file->getRealPath() ) );

    $target = ASDK()->entity( $request->request->get( 'target_entity' ) );
    $target->id = $request->request->get( 'target_record' );

    $newAnnotation = ASDK()->entity( 'annotation' );

    $newAnnotation->objectid = $target;
    $newAnnotation->subject      = 'Attachment file ' . $file->getClientOriginalName();
    $newAnnotation->documentbody = $base64;
    $newAnnotation->mimetype     = $file->getMimeType();
    $newAnnotation->filename     = $file->getClientOriginalName();

    $imageId = ASDK()->create( $newAnnotation );

    echo $imageId;

    wp_die();
}

function wpcrm_media_remove() {
    check_ajax_referer( 'wpcrm_upload_remove' );

    $request = ACRM()->request;

    $annotationId = $request->request->get( 'annotationId' );

    if ( is_null( $annotationId ) ) {
        wp_send_json_error();
    }

    try {
        $annotation = ASDK()->entity( 'annotation' );
        $annotation->id = $annotationId;
        ASDK()->delete( $annotation );
    } catch ( Exception $e ) {
        wp_send_json_error();
    }

    wp_send_json_success();
}
