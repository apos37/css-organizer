( function( wp ) {
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editor;
    const { TextareaControl, PanelBody } = wp.components;
    const { createElement, Fragment, useMemo, useEffect, useState } = wp.element;
    const { useEntityProp } = wp.coreData;

    const CssOrganizerSidebar = () => {
        const config = window.css_organizer_data || {};
        const sections = config[ 'sections' ] || [];
        const textDomain = config[ 'text_domain' ] || 'css-organizer';
        const makeSettingKey = ( section ) => `${ textDomain }-css-${ section[ 'key' ] }`;

        const [ settings ] = useEntityProp( 'root', 'site', 'settings' );

        const [ localValues, setLocalValues ] = useState( () => {
            const initial = {};

            sections.forEach( ( section ) => {
                initial[ makeSettingKey( section ) ] = section[ 'value' ] || '';
            } );

            return initial;
        } );

        const updateSetting = ( key, value ) => {
            setLocalValues( ( prev ) => ( { ...prev, [ key ]: value } ) );
            wp.data.dispatch( 'core' ).editEntityRecord(
                'root',
                'site',
                undefined,
                { [ key ]: value }
            );
        };

        const combinedCss = useMemo( () => {
            return sections.reduce( ( acc, section ) => {
                const key = makeSettingKey( section );
                const value = localValues[ key ];

                return value ? acc + `/* ${ section[ 'label' ] } */\n${ value }\n` : acc;
            }, '' );
        }, [ localValues, sections, textDomain ] );

        useEffect( () => {
            if ( ! settings ) {
                return;
            }

            setLocalValues( ( prev ) => {
                const next = { ...prev };

                sections.forEach( ( section ) => {
                    const key = makeSettingKey( section );

                    if ( typeof settings[ key ] === 'string' ) {
                        next[ key ] = settings[ key ];
                    }
                } );

                return next;
            } );
        }, [ settings, sections, textDomain ] );

        useEffect( () => {
            let retryId = null;
            let attempts = 0;
            const maxAttempts = 40;

            const inject = ( doc ) => {
                if ( ! doc || ! doc.head ) {
                    return false;
                }

                const existing = doc.getElementById( 'css-organizer-preview' );

                if ( ! combinedCss.trim() ) {
                    if ( existing ) {
                        existing.remove();
                    }
                    return true;
                }

                const style = existing || doc.createElement( 'style' );
                style.id = 'css-organizer-preview';
                style.textContent = combinedCss;

                if ( ! existing ) {
                    doc.head.appendChild( style );
                }

                return true;
            };

            const applyPreview = () => {
                inject( document );

                let appliedToCanvas = false;
                const iframes = document.querySelectorAll( 'iframe[name="editor-canvas"]' );

                iframes.forEach( ( frame ) => {
                    if ( inject( frame.contentDocument ) ) {
                        appliedToCanvas = true;
                    }
                } );

                if ( appliedToCanvas || attempts >= maxAttempts ) {
                    return;
                }

                attempts += 1;
                retryId = window.setTimeout( applyPreview, 250 );
            };

            applyPreview();

            return () => {
                if ( retryId ) {
                    window.clearTimeout( retryId );
                }
            };
        }, [ combinedCss ] );

        return createElement(
            Fragment,
            {},
            createElement(
                PluginSidebarMoreMenuItem,
                { target: 'css-organizer-sidebar', icon: 'editor-code' },
                config[ 'custom_label' ]
            ),
            createElement(
                PluginSidebar,
                { name: 'css-organizer-sidebar', title: config[ 'custom_label' ], icon: 'editor-code' },
                createElement(
                    'div',
                    { style: { padding: '16px' } },
                    sections.map( ( section ) => {
                        const key = makeSettingKey( section );

                        return createElement(
                            PanelBody,
                            { title: section[ 'label' ], key: section[ 'key' ], initialOpen: false },
                            createElement( TextareaControl, {
                                value: localValues[ key ] || '',
                                __nextHasNoMarginBottom: true,
                                rows: 12,
                                onChange: ( value ) => updateSetting( key, value )
                            } )
                        );
                    } )
                )
            )
        );
    };

    registerPlugin( 'css-organizer', { render: CssOrganizerSidebar } );
} )( window.wp );