jQuery( $ => {
    // console.log( 'CSS Organizer Customizer Controls JS Loaded...' );

    /**
     * Expand Buttons
     */
    $( '.css-organizer-expand-btn' ).on( 'click', function( e ) {
        e.preventDefault();

        var width = $( this ).data( 'width' );

        $( '.wp-full-overlay-sidebar' ).css( 'width', width + '%' ).css( 'max-width', width + '%' );
        $( '.wp-full-overlay.expanded' ).attr( 'style', 'margin-left: ' + width + '% !important' );
        $( '#customize-footer-actions' ).css( 'width', width + '%' ).css( 'max-width', width + '%' );

        $( '.css-organizer-expand-btn' ).prop( 'disabled', false );
        $( this ).prop( 'disabled', true );

    } );

    
    /**
     * Mobile Queries
     */
    $( document ).on( 'click', '.css-organizer-mq-btn', function( e ) {
        e.preventDefault();
        const $button = $( this );
        let width = $button.data( 'mq' ).toString();
        
        if ( ! width.includes( 'px' ) && ! width.includes( 'em' ) && ! width.includes( 'rem' ) ) {
            width += 'px';
        }

        const $section = $button.closest( '.accordion-section' );
        const editor = $section.find( '.CodeMirror' )[0].CodeMirror;
        const doc = editor.getDoc();
        const selection = doc.getSelection();

        if ( selection ) {
            const indented = selection.split( '\n' ).map( line => '\t' + line ).join( '\n' );
            doc.replaceSelection( `@media (max-width: ${width}) {\n${indented}\n}` );
        } else {
            const cursor = doc.getCursor();
            doc.replaceRange( `@media (max-width: ${width}) {\n\t\n}`, cursor );
            editor.setCursor( { line: cursor.line + 1, ch: 1 } );
        }
        
        editor.focus();
    } );

    
    /**
     * Local Variable Picker
     */
    $( document ).on( 'click', '.css-organizer-local-vars-btn', function( e ) {
        e.preventDefault();
        e.stopPropagation();

        const isExist = $( '.custom-local-vars-menu' ).length;

        $( '.custom-local-vars-menu, .custom-body-class-menu' ).remove();

        if ( isExist ) return;

        const $button = $( this );
        const $section = $button.closest( '.accordion-section' );
        let rawContent = '';

        if ( css_organizer_controls.saved_values ) {
            Object.values( css_organizer_controls.saved_values ).forEach( val => {
                rawContent += ' ' + val;
            } );
        }

        $( '.customize-control-code_editor .CodeMirror' ).each( function() {
            if ( this.CodeMirror ) {
                rawContent += ' ' + this.CodeMirror.getValue();
            }
        } );

        const varRegex = /(--[a-zA-Z0-9_-]+)\s*:\s*([^;}\n]+)/g;
        let varMap = {};
        let match;

        while ( ( match = varRegex.exec( rawContent ) ) !== null ) {
            const name = match[1].trim();
            const value = match[2].trim();
            varMap[ name ] = value;
        }

        const localVars = Object.keys( varMap ).sort();

        if ( localVars.length === 0 ) return;

        const $menu = $( '<div class="custom-local-vars-menu"><input type="text" class="vars-search" placeholder="Filter variables..." /><ul></ul></div>' );
        const $list = $menu.find( 'ul' );

        const populateList = ( filter = '' ) => {
            $list.empty();
            localVars.forEach( v => {
                if ( filter && ! v.toLowerCase().includes( filter.toLowerCase() ) ) return;

                const value = varMap[ v ];
                const $item = $( '<li></li>' );
                const $text = $( '<span>var(' + v + ')</span>' );
                
                const isColor = /^(#|rgb|hsl|linear-gradient|red|blue|green|black|white|orange|pink|purple|grey|gray)/i.test( value );
                
                if ( isColor ) {
                    const $swatch = $( '<span class="var-color-swatch"></span>' ).css( 'background', value );
                    $item.append( $swatch );
                }

                $item.append( $text );
                $item.attr( 'title', v + ': ' + value );

                $item.on( 'click', function() {
                    const editor = $section.find( '.CodeMirror' )[0].CodeMirror;
                    editor.replaceRange( 'var(' + v + ')', editor.getCursor() );
                    $menu.remove();
                    editor.focus();
                } );
                
                $list.append( $item );
            } );
        };

        populateList();
        $button.after( $menu );
        
        $menu.find( '.vars-search' )
            .focus()
            .on( 'keyup', function() { 
                populateList( $( this ).val() ); 
            } );

        $( document ).one( 'click', e => {
            if ( ! $( e.target ).closest( '.custom-local-vars-menu' ).length ) {
                $menu.remove();
            }
        } );
    } );

    
    /**
     * Body class dropdown
     */
    $( document ).on( 'click', '.css-organizer-body-tags-dropdown', function( e ) {
        e.preventDefault();
        e.stopPropagation();

        const isExist = $( '.custom-body-class-menu' ).length;

        $( '.custom-local-vars-menu, .custom-body-class-menu' ).remove();

        if ( isExist ) return;

        const $button = $( this );
        const $section = $button.closest( '.accordion-section' );
        const $previewIframe = $( '#customize-preview iframe' );
        const iframeBody = $previewIframe.contents().find( 'body' );
        
        if ( ! iframeBody.length ) return;

        let allInternalClasses = [ ...new Set( iframeBody.attr( 'class' ).split( /\s+/ ) ) ].filter( item => item.length > 0 );
        const importantPatterns = css_organizer_controls.important_body_tags || [];
        
        let importantBucket = [];
        let normalBucket = [];

        allInternalClasses.forEach( ( className ) => {
            const isImportant = importantPatterns.some( ( pattern ) => {
                return pattern.endsWith( '-' ) ? className.startsWith( pattern ) : className === pattern;
            } );
            isImportant ? importantBucket.push( className ) : normalBucket.push( className );
        } );

        importantBucket.sort();
        normalBucket.sort();

        const $menu = $( '<ul class="custom-body-class-menu"></ul>' );

        const addItemToMenu = ( className, isBold ) => {
            const $item = $( '<li>.' + className + '</li>' ).toggleClass( 'is-important', isBold );
            $item.attr( 'title', '.' + className );
            
            $item.on( 'click', function() {
                const editor = $section.find( '.CodeMirror' )[0].CodeMirror;
                editor.replaceRange( '.' + className + ' ', editor.getCursor() );
                $menu.remove();
            } );
            
            $menu.append( $item );
        };

        importantBucket.forEach( cls => addItemToMenu( cls, true ) );
        normalBucket.forEach( cls => addItemToMenu( cls, false ) );

        $button.after( $menu );

        // 6. Global click to close
        $( document ).one( 'click', () => {
            $menu.remove();
        } );
    } );
} )