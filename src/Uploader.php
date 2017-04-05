<?php

namespace AlexaCRM\WordpressCRM;

use AlexaCRM\WordpressCRM\Image\AnnotationImage;
use AlexaCRM\WordpressCRM\Shortcode\View;

/**
 * Implements the uploader feature. [msdyncrm_uploader]
 *
 * @package AlexaCRM\WordpressCRM
 */
class Uploader extends Shortcode {

    /**
     * @param array $attributes
     * @param string $content
     * @param string $tagName
     *
     * @return string
     */
    public function shortcode( $attributes, $content = null, $tagName ) {
        if ( !ACRM()->connected() ) {
            return self::notConnected();
        }

        $attributes = shortcode_atts( [
            'name' => null, // Annotation view name
            'parameters' => null, // View parameter values
            'lookups' => [], // View lookups
            'target' => null, // Record to attach image annotations to

            'width' => 300, // Gallery column width
            'gutter' => 10, // Gallery column gutter
        ], $attributes );

        $target = explode( ':', $attributes['target'] );
        if ( $target[1] === 'currentrecord.id' ) {
            $currentRecord = ACRM()->getBinding()->getEntity();

            if ( $currentRecord === null ) {
                return '<p>' . __( 'Images can be uploaded after the record is saved!', 'integration-dynamics-uploader' ) . '</p>';
            }

            $target[1] = $currentRecord->ID;
        }

        $lookups = self::parseKeyValueArrayShortcodeAttribute( $attributes['lookups'] );
        $parameters = self::parseKeyArrayShortcodeAttribute( $attributes['parameters'] );

        $view = $this->retrieveView( 'annotation', $attributes['name'] );
        if ( !is_array( $view ) ) {
            return $view;
        }

        $fetchXML = $view['fetchxml'];
        $fetchXML = FetchXML::replacePlaceholderValuesByParametersArray( $fetchXML, $parameters );
        $fetchXML = FetchXML::replaceLookupConditionsByLookupsArray( $fetchXML, $lookups );
        if ( $fetchXML == $view['fetchxml'] ) {
            $fetchXML = FetchXML::constructFetchForLookups( $fetchXML, $lookups );
        }

        $images = ASDK()->retrieveMultiple( $fetchXML );

        $imageUrls = [];
        foreach ( $images->Entities as $annotation ) {
            $imageUrls[$annotation->id] = admin_url( 'admin-ajax.php?action=msdyncrm_image&id=' . $annotation->id . '&width=' . $attributes['width'] );
        }

        wp_enqueue_script( 'dropzone-js', '', [], false, true );
        wp_enqueue_script( 'isotope', '', [], false, true );

        $output = ACRM()->getTemplate()->printTemplate( 'uploader/form.php', [ 'target' => $target ] )
            . ACRM()->getTemplate()->printTemplate( 'uploader/gallery.php', [
                'imageUrls' => $imageUrls,
                'columnWidth' => $attributes['width'],
                'columnGutter' => $attributes['gutter'],
            ] );

        return $output;
    }

    /**
     * @param string $entity
     * @param string $name
     *
     * @return array|string
     */
    private function retrieveView( $entity, $name ) {
        $crmView = \AlexaCRM\WordpressCRM\View::getViewForEntity( strtolower( $entity ), $name );

        if ( $crmView == null ) {
            return self::returnError( sprintf( __( 'Unable to get the specified SavedQuery [%1$s] for the entity {%2$s}', 'integration-dynamics-premium' ), $name, $entity ) );
        }

        $view = [
            'fetchxml' => $crmView->fetchxml,
            'layoutxml' => $crmView->layoutxml,
        ];

        return $view;
    }

}
