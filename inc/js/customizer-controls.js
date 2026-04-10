jQuery( $ => {
    // console.log( 'CSS Organizer Customizer Controls JS Loaded...' );

    $( '.css-organizer-expand-btn' ).on( 'click', function( e ) {
        e.preventDefault();

        // Get the width from the button's data-width attribute
        var width = $( this ).data( 'width' );

        // Remove all expand-* classes
        $( '.wp-full-overlay-sidebar' ).css( 'width', width + '%' ).css( 'max-width', width + '%' );
        $( '.wp-full-overlay.expanded' ).attr( 'style', 'margin-left: ' + width + '% !important' );
        $( '#customize-footer-actions' ).css( 'width', width + '%' ).css( 'max-width', width + '%' );

        // Disable the active button, enable others
        $( '.css-organizer-expand-btn' ).prop( 'disabled', false ); // Enable all buttons
        $( this ).prop( 'disabled', true ); // Disable the clicked button

    } );
} )