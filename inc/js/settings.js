jQuery( $ => {
    // console.log( 'CSS Organizer Settings JS Loaded...' );

    const l10n = css_organizer_settings;


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

    function slugifyKey( value ) {
        return value
            .toLowerCase()
            .replace( /[^a-z0-9]+/g, '_' )
            .replace( /^_+|_+$/g, '' );
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
            newRow.find( 'input' ).val( '' ).attr( 'value', '' ).prop( 'disabled', false );
            newRow.find( 'input[data-type="key"]' ).removeData( 'autofill' );
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
                    warningMessage.show().text( l10n.duplicateLabel );
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
                    warningMessage.show().text( l10n.duplicateKey );
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

    // Auto-fill key from label only when key is empty and editable.
    $( '#fields_container_css-organizer-sections' ).on( 'input', 'input[data-type="label"]', function () {
        const row = $( this ).closest( '.text-plus-row' );
        const keyInput = row.find( 'input[data-type="key"]' );
        const keyIsAutofill = keyInput.data( 'autofill' ) === true;

        if ( ! keyInput.length || keyInput.prop( 'disabled' ) ) {
            return;
        }

        if ( keyInput.val().trim() !== '' && ! keyIsAutofill ) {
            return;
        }

        keyInput.val( slugifyKey( $( this ).val() ) ).data( 'autofill', true );
        checkForDuplicates();
    } );
    
    // Prevent slug from being uppercase or having special characters/spaces
    $( '#fields_container_css-organizer-sections' ).on( 'input', 'input.metakey', function () {
        if ( this === document.activeElement ) {
            $( this ).data( 'autofill', false );
        }

        $( this ).val( slugifyKey( $( this ).val() ) );
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
    
    
    /**
     * Show/hide fields
     */
    $( '#css-organizer-force-wp-customizer' ).on( 'change', function() {
        if ( $( this ).is( ':checked' ) ) {
            $( '.css-organizer-admin-wrap.settings-page' ).removeClass( 'customizer-inactive' ).addClass( 'customizer-active' );
        } else {
            $( '.css-organizer-admin-wrap.settings-page' ).removeClass( 'customizer-active' ).addClass( 'customizer-inactive' );
        }
    } );


    /**
     * Toggle button loading state
     */
    const toggleBtnLoading = ( $btn, isLoading, originalText ) => {
        if ( isLoading ) {
            $btn.prop( 'disabled', true ).html( '<span class="co-spinner"></span>' + originalText );
        } else {
            $btn.prop( 'disabled', false ).text( originalText );
        }
    };


    /**
     * Export Logic
     */
    $( '#co-export-btn' ).on( 'click', function( e ) {
        e.preventDefault();
        const $btn = $( this );

        toggleBtnLoading( $btn, true, l10n.exporting );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'css_organizer_export',
                nonce: l10n.nonce
            },
            success: function( response ) {
                if ( response.success ) {
                    const jsonString = JSON.stringify( response.data.data, null, 4 );
                    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent( jsonString );
                    
                    const downloadAnchorNode = document.createElement( 'a' );
                    downloadAnchorNode.setAttribute( "href", dataStr );
                    downloadAnchorNode.setAttribute( "download", response.data.filename );
                    document.body.appendChild( downloadAnchorNode );
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                } else {
                    alert( response.data.message );
                }
            },
            complete: function() {
                toggleBtnLoading( $btn, false, l10n.exportBtn );
            }
        } );
    } );


    /**
     * Import Logic
     */
    $( '#co-import-form' ).on( 'submit', function( e ) {
        e.preventDefault();

        const fileInput = $( '#co-import-file' )[0];
        if ( fileInput.files.length === 0 ) {
            alert( l10n.selectFile );
            return;
        }

        if ( ! confirm( l10n.confirmImport ) ) {
            return;
        }

        const formData = new FormData();
        formData.append( 'action', 'css_organizer_import' );
        formData.append( 'nonce', l10n.nonce );
        formData.append( 'import_file', fileInput.files[0] );

        const $btn = $( this ).find( 'button[type="submit"]' );
        toggleBtnLoading( $btn, true, l10n.importing );

        $.ajax( {
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function( response ) {
                if ( response.success ) {
                    alert( response.data.message );
                    location.reload();
                } else {
                    alert( response.data.message );
                }
            },
            error: function() {
                alert( l10n.importError );
            },
            complete: function() {
                toggleBtnLoading( $btn, false, l10n.importBtn );
            }
        } );
    } );
} )