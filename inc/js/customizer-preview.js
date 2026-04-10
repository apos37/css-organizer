jQuery( $ => {
    // console.log( 'CSS Organizer Customizer Preview JS Loaded...' );
    
    // The sections
    var sections = css_organizer.sections;

    // Iter the sections
    sections.forEach( function( section ) {
        wp.customize( 'css-organizer-css-' + section, function( value ) {

            value.bind( function( to ) {
                $( '#css-organizer-' + section ).html( to );
            } );
        } );
    } );
} )