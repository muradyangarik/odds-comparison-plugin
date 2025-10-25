/**
 * Gutenberg Blocks JavaScript
 *
 * Live Events block for odds comparison plugin.
 *
 * @package OddsComparison
 * @since 1.0.0
 */

(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment, useEffect, useRef } = wp.element;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl } = wp.components;
    const { __, sprintf } = wp.i18n;

    // Safe wrapper for getBoundingClientRect
    function safeGetRect(el) {
        if (!el) return null;
        try {
            return el.getBoundingClientRect();
        } catch (e) {
            return null;
        }
    }

    // Safe drag handler wrapper
    function safeDragHandler(handler) {
        return function(e) {
            try {
                return handler(e);
            } catch (err) {
                return false;
            }
        };
    }

    // Register the block
    
    registerBlockType('odds-comparison/live-events', {
        apiVersion: 2,
        title: __('Live Events', 'odds-comparison'),
        description: __('Display live sporting events with odds comparison.', 'odds-comparison'),
        icon: 'calendar-alt',
        category: 'widgets',
        keywords: [
            __('events', 'odds-comparison'),
            __('live', 'odds-comparison'),
            __('sports', 'odds-comparison')
        ],
        supports: {
            html: false,
            align: ['wide', 'full'],
            anchor: true,
            customClassName: true
        },
        
        attributes: {
            sport: {
                type: 'string',
                default: ''
            },
            limit: {
                type: 'number',
                default: 10
            },
            showSport: {
                type: 'boolean',
                default: true
            },
            showTime: {
                type: 'boolean',
                default: true
            },
            showBookmakers: {
                type: 'boolean',
                default: true
            },
            layout: {
                type: 'string',
                default: 'grid'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { sport, limit, showSport, showTime, showBookmakers, layout } = attributes;
            
            // Check for undefined attributes that could break serialization
            const hasUndefined = Object.values(attributes).some(val => val === undefined);
            if (hasUndefined) {
                // Fix undefined attributes with defaults
                const safeAttributes = {
                    sport: sport || '',
                    limit: limit || 10,
                    showSport: showSport !== undefined ? showSport : true,
                    showTime: showTime !== undefined ? showTime : true,
                    showBookmakers: showBookmakers !== undefined ? showBookmakers : true,
                    layout: layout || 'grid'
                };
                setAttributes(safeAttributes);
            }
            
            // Prevent infinite re-renders with proper useEffect
            useEffect(() => {
                // Only run once on mount
                return () => {
                    // Cleanup on unmount
                };
            }, []); // Empty dependency array - runs only once
            
            // Ensure stable DOM structure with defensive checks
            const blockProps = useBlockProps({
                className: 'wp-block-odds-comparison-live-events',
                'data-wp-block': 'odds-comparison/live-events',
                'data-client-id': clientId,
                style: {
                    position: 'relative',
                    minHeight: '100px'
                }
            });

            const sportOptions = [
                { label: __('All Sports', 'odds-comparison'), value: '' },
                { label: __('Premier League', 'odds-comparison'), value: 'soccer_epl' },
                { label: __('NBA', 'odds-comparison'), value: 'basketball_nba' },
                { label: __('Tennis', 'odds-comparison'), value: 'tennis_atp' },
                { label: __('MLB', 'odds-comparison'), value: 'baseball_mlb' }
            ];

            const layoutOptions = [
                { label: __('Grid', 'odds-comparison'), value: 'grid' },
                { label: __('List', 'odds-comparison'), value: 'list' }
            ];

            // Defensive check to ensure we have valid props
            if (!blockProps || !clientId) {
                return el('div', { className: 'wp-block-odds-comparison-live-events' }, 
                    el('p', {}, __('Block loading...', 'odds-comparison'))
                );
            }

            return el('div', blockProps, [
                el(InspectorControls, { key: 'inspector' }, [
                    el(PanelBody, {
                        title: __('Event Settings', 'odds-comparison'),
                        initialOpen: true
                    }, [
                        el(SelectControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Sport Filter', 'odds-comparison'),
                            value: sport,
                            options: sportOptions,
                            onChange: (value) => setAttributes({ sport: value })
                        }),
                        
                        el(RangeControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Number of Events', 'odds-comparison'),
                            value: limit,
                            onChange: (value) => setAttributes({ limit: value }),
                            min: 1,
                            max: 50,
                            step: 1
                        }),
                        
                        el(SelectControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Layout', 'odds-comparison'),
                            value: layout,
                            options: layoutOptions,
                            onChange: (value) => setAttributes({ layout: value })
                        })
                    ]),

                    el(PanelBody, {
                        title: __('Display Options', 'odds-comparison'),
                        initialOpen: false
                    }, [
                        el(ToggleControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Show Sport Tags', 'odds-comparison'),
                            checked: showSport,
                            onChange: (value) => setAttributes({ showSport: value })
                        }),
                        
                        el(ToggleControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Show Event Times', 'odds-comparison'),
                            checked: showTime,
                            onChange: (value) => setAttributes({ showTime: value })
                        }),
                        
                        el(ToggleControl, {
                            __next40pxDefaultSize: true,
                            __nextHasNoMarginBottom: true,
                            label: __('Show Bookmaker Count', 'odds-comparison'),
                            checked: showBookmakers,
                            onChange: (value) => setAttributes({ showBookmakers: value })
                        })
                    ])
                ]),

                el('div', {
                    key: 'preview',
                    className: 'live-events-block-preview',
                    style: {
                        padding: '20px',
                        border: '1px solid #ddd',
                        borderRadius: '8px',
                        background: '#f9f9f9',
                        margin: '10px 0',
                        position: 'relative',
                        pointerEvents: 'auto'
                    },
                    onDragOver: safeDragHandler(function(e) {
                        // Safe drag handling with additional checks
                        const target = e.currentTarget;
                        if (!target || !target.getBoundingClientRect) {
                            return;
                        }
                        
                        const rect = safeGetRect(target);
                        if (rect && rect.width > 0 && rect.height > 0) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    }),
                    onDragEnter: safeDragHandler(function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }),
                    onDragLeave: safeDragHandler(function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    })
                }, [
                    el('h3', {
                        style: { margin: '0 0 15px 0', color: '#333' }
                    }, __('Live Events', 'odds-comparison')),
                    
                    el('p', {
                        style: { color: '#666', fontSize: '14px', margin: '0 0 15px 0' }
                    }, sprintf(
                        __('Showing %d events from %s', 'odds-comparison'),
                        limit,
                        sport || __('all sports', 'odds-comparison')
                    )),
                    
                    el('div', {
                        style: {
                            display: layout === 'list' ? 'flex' : 'grid',
                            flexDirection: layout === 'list' ? 'column' : 'auto',
                            gridTemplateColumns: layout === 'grid' ? 'repeat(auto-fit, minmax(300px, 1fr))' : 'auto',
                            gap: '15px'
                        }
                    }, [
                        el('div', {
                            key: 'event1',
                            style: {
                                background: '#fff',
                                padding: '15px',
                                border: '1px solid #ddd',
                                borderRadius: '6px',
                                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                            }
                        }, [
                            showSport && el('div', { 
                                style: { 
                                    background: '#0073aa', 
                                    color: 'white', 
                                    padding: '4px 8px', 
                                    borderRadius: '4px', 
                                    fontSize: '12px', 
                                    display: 'inline-block', 
                                    marginBottom: '10px' 
                                } 
                            }, sport ? sportOptions.find(opt => opt.value === sport)?.label || sport : __('All Sports', 'odds-comparison')),
                            
                            el('div', { 
                                style: { 
                                    display: 'flex', 
                                    alignItems: 'center', 
                                    justifyContent: 'space-between', 
                                    margin: '10px 0' 
                                } 
                            }, [
                                el('div', { 
                                    style: { fontWeight: 'bold', flex: 1 } 
                                }, 'Manchester United'),
                                el('div', { 
                                    style: { margin: '0 10px', color: '#666', fontWeight: 'bold' } 
                                }, 'VS'),
                                el('div', { 
                                    style: { fontWeight: 'bold', flex: 1, textAlign: 'right' } 
                                }, 'Liverpool')
                            ]),
                            
                            showTime && el('div', { 
                                style: { 
                                    fontSize: '14px', 
                                    color: '#666', 
                                    margin: '5px 0',
                                    display: 'flex',
                                    alignItems: 'center'
                                } 
                            }, [
                                el('span', { style: { marginRight: '5px' } }, '🕒'),
                                'Oct 21, 2025 15:00'
                            ]),
                            
                            showBookmakers && el('div', { 
                                style: { 
                                    fontSize: '14px', 
                                    color: '#0073aa', 
                                    margin: '5px 0' 
                                } 
                            }, '15 bookmakers offering odds'),
                            
                            el('div', {
                                style: { marginTop: '10px' }
                            }, [
                                el('a', {
                                    href: '#',
                                    style: {
                                        background: '#0073aa',
                                        color: 'white',
                                        padding: '8px 16px',
                                        borderRadius: '4px',
                                        textDecoration: 'none',
                                        display: 'inline-block',
                                        fontSize: '14px'
                                    }
                                }, __('View Odds', 'odds-comparison'))
                            ])
                        ]),
                        
                        el('div', {
                            key: 'event2',
                            style: {
                                background: '#fff',
                                padding: '15px',
                                border: '1px solid #ddd',
                                borderRadius: '6px',
                                boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
                            }
                        }, [
                            showSport && el('div', { 
                                style: { 
                                    background: '#0073aa', 
                                    color: 'white', 
                                    padding: '4px 8px', 
                                    borderRadius: '4px', 
                                    fontSize: '12px', 
                                    display: 'inline-block', 
                                    marginBottom: '10px' 
                                } 
                            }, sport ? sportOptions.find(opt => opt.value === sport)?.label || sport : __('All Sports', 'odds-comparison')),
                            
                            el('div', { 
                                style: { 
                                    display: 'flex', 
                                    alignItems: 'center', 
                                    justifyContent: 'space-between', 
                                    margin: '10px 0' 
                                } 
                            }, [
                                el('div', { 
                                    style: { fontWeight: 'bold', flex: 1 } 
                                }, 'Arsenal'),
                                el('div', { 
                                    style: { margin: '0 10px', color: '#666', fontWeight: 'bold' } 
                                }, 'VS'),
                                el('div', { 
                                    style: { fontWeight: 'bold', flex: 1, textAlign: 'right' } 
                                }, 'Chelsea')
                            ]),
                            
                            showTime && el('div', { 
                                style: { 
                                    fontSize: '14px', 
                                    color: '#666', 
                                    margin: '5px 0',
                                    display: 'flex',
                                    alignItems: 'center'
                                } 
                            }, [
                                el('span', { style: { marginRight: '5px' } }, '🕒'),
                                'Oct 21, 2025 17:30'
                            ]),
                            
                            showBookmakers && el('div', { 
                                style: { 
                                    fontSize: '14px', 
                                    color: '#0073aa', 
                                    margin: '5px 0' 
                                } 
                            }, '18 bookmakers offering odds'),
                            
                            el('div', {
                                style: { marginTop: '10px' }
                            }, [
                                el('a', {
                                    href: '#',
                                    style: {
                                        background: '#0073aa',
                                        color: 'white',
                                        padding: '8px 16px',
                                        borderRadius: '4px',
                                        textDecoration: 'none',
                                        display: 'inline-block',
                                        fontSize: '14px'
                                    }
                                }, __('View Odds', 'odds-comparison'))
                            ])
                        ])
                    ])
                ])
            ]);
        },

        save: function() {
            // Return null for dynamic blocks - content is rendered server-side
            return null;
        }
    });

})();