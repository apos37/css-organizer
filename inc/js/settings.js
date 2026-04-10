jQuery( $ => {
    // console.log( 'CSS Organizer Settings JS Loaded...' );

    /**
     * TEXT+ FIELDS
     */
    // Variable to keep track of the current index
    var index = $( '.fields_container .text-plus-row' ).length;

    // Function to update the name attributes of all rows
    function updateIndexes() {
        $( '.fields_container .text-plus-row' ).each( function( i ) {
            $( this ).attr( 'data-row', i );

            $( this ).find( 'input, select' ).each( function() {
                var name = $( this ).attr( 'name' );
                // Update index in the name attribute
                var newName = name.replace( /\[\d+\]/, '[' + i + ']' );
                $( this ).attr( 'name', newName );
            } );
        } );
        index = $( '.fields_container .text-plus-row' ).length;
    }

    // Add new field
    $( '.add-new-field' ).on( 'click', function() {
        var name = $( this ).data( 'name' );
        var fieldsContainer = $( `#fields_container_${name}` );
        var lastRow = fieldsContainer.find( '.text-plus-row' ).last();
        if ( lastRow.data( 'row' ) == 0 && lastRow.is(':hidden') ) {
            lastRow.show();
        } else {
            var newRow = lastRow.clone();
            newRow.find( 'input' ).val( '' ).prop( 'disabled', false );
            newRow.find( 'select' ).prop( 'selectedIndex', 0 );
            fieldsContainer.append( newRow );
            updateIndexes();
        }
    } );

    // Remove field
    $( '.fields_container' ).on( 'click', '.remove-row', function() {
        var row = $( this ).closest( '.text-plus-row' );
        var rowNum = row.data( 'row' );
        if ( rowNum > 0 ) {
            row.remove();
            updateIndexes();
        } else {
            row.hide();
            row.find( 'input' ).val( '' );
            row.find( 'select' ).prop( 'selectedIndex', 0 );
        }
    } );

    // Make the fields container sortable
    $( '#fields_container_css-organizer-sections' ).sortable( {
        items: '.text-plus-row',
        handle: '.order',
        placeholder: 'placeholder',
        update: function ( event, ui ) {
            $( '#fields_container_css-organizer-sections .text-plus-row' ).each( function ( index ) {
                $( this ).attr( 'data-row', index );
                $( this ).find( 'input' ).each( function () {
                    const name = $( this ).attr( 'name' );
                    if ( name ) {
                        const newName = name.replace( /\[\d+\]/, `[${ index }]` );
                        $( this ).attr( 'name', newName );
                    }
                } );
            } );
        }
    } );
    
    // Prevent duplicates
    function checkForDuplicates () {
        const labels = {};
        const keys = {};
        let hasDuplicates = false;
    
        $( '#fields_container_css-organizer-sections .text-plus-row' ).each( function () {
            const labelInput = $( this ).find( 'input[data-type="label"]' );
            const keyInput = $( this ).find( 'input[data-type="key"]' );
            const warningMessage = $( this ).find( '.warning-message' );
    
            const labelValue = labelInput.val().trim();
            const keyValue = keyInput.val().trim();
    
            warningMessage.hide().text( '' );
    
            if ( labelValue ) {
                if ( labels[labelValue] ) {
                    hasDuplicates = true;
                    labelInput.addClass( 'duplicate' );
                    warningMessage.show().text( css_organizer.duplicateLabel );
                } else {
                    labels[labelValue] = true;
                    labelInput.removeClass( 'duplicate' );
                }
            } else {
                labelInput.removeClass( 'duplicate' );
            }
    
            if ( keyValue ) {
                if ( keys[keyValue] ) {
                    hasDuplicates = true;
                    keyInput.addClass( 'duplicate' );
                    warningMessage.show().text( css_organizer.duplicateKey );
                } else {
                    keys[keyValue] = true;
                    keyInput.removeClass( 'duplicate' );
                }
            } else {
                keyInput.removeClass( 'duplicate' );
            }
        } );
    
        toggleButtons( hasDuplicates );
        return hasDuplicates;
    }
    
    function toggleButtons ( hasWarnings ) {
        $( '#submit, .add-new-field' ).prop( 'disabled', hasWarnings );
    }
    
    $( '#fields_container_css-organizer-sections' ).on( 'input', 'input[data-type="label"], input[data-type="key"]', function () {
        checkForDuplicates();
    } );
    
    // Prevent slug from being uppercase or having special characters/spaces
    $( '#fields_container_css-organizer-sections' ).on( 'input', 'input.metakey', function () {
        let value = $( this ).val();
        value = value.toLowerCase();
        value = value.replace( /[^a-z0-9]+/g, '_' );
        $( this ).val( value );
    } );

    // Handle row deletion
    $( '#fields_container_css-organizer-sections' ).on( 'click', '.remove-row', function () {
        $( this ).closest( '.text-plus-row' ).remove();
        checkForDuplicates();
    } );

    // Enable keys before form submission
    $( 'form' ).on( 'submit', function () {
        $( 'input[data-type="key"]' ).prop( 'disabled', false );
    } );
    
} )