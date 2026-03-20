/**
 * Gerfaut Embed Blocks - Editor Scripts
 */

(function(blocks, element, blockEditor, components) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { InspectorControls } = blockEditor;
    const { PanelBody, TextControl } = components;
    
    // Icône formulaire
    const formIcon = el('svg', { width: 24, height: 24, viewBox: '0 0 24 24' },
        el('path', { 
            d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z',
            fill: 'currentColor'
        })
    );
    
    // Bloc Formulaire SAV
    registerBlockType('gerfaut/sav-form', {
        title: 'Formulaire SAV Gerfaut',
        icon: formIcon,
        category: 'embed',
        attributes: {
            height: {
                type: 'string',
                default: 'auto'
            }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'gerfaut-block-placeholder' },
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Paramètres' },
                        el(TextControl, {
                            label: 'Hauteur minimale',
                            value: attributes.height,
                            onChange: (value) => setAttributes({ height: value }),
                            help: 'Ex: 600px, 80vh, ou auto'
                        })
                    )
                ),
                el('div', { 
                    style: { 
                        padding: '40px 20px',
                        background: '#f0f0f1',
                        border: '2px dashed #ccc',
                        textAlign: 'center',
                        borderRadius: '4px'
                    }
                },
                    el('svg', { 
                        width: 48, 
                        height: 48, 
                        viewBox: '0 0 24 24',
                        style: { marginBottom: '10px', opacity: 0.5 }
                    },
                        el('path', { 
                            d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z',
                            fill: '#666'
                        })
                    ),
                    el('h3', { style: { margin: '0 0 8px' } }, 'Formulaire SAV Gerfaut'),
                    el('p', { style: { margin: 0, color: '#666' } }, 'Le formulaire sera affiché ici sur la page publiée.')
                )
            );
        },
        save: function() {
            return null; // Rendu dynamique côté serveur
        }
    });
    
    // Bloc Formulaire Contact
    registerBlockType('gerfaut/contact-form', {
        title: 'Formulaire Contact Gerfaut',
        icon: formIcon,
        category: 'embed',
        attributes: {
            height: {
                type: 'string',
                default: 'auto'
            }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            
            return el('div', { className: 'gerfaut-block-placeholder' },
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Paramètres' },
                        el(TextControl, {
                            label: 'Hauteur minimale',
                            value: attributes.height,
                            onChange: (value) => setAttributes({ height: value }),
                            help: 'Ex: 600px, 80vh, ou auto'
                        })
                    )
                ),
                el('div', { 
                    style: { 
                        padding: '40px 20px',
                        background: '#f0f0f1',
                        border: '2px dashed #ccc',
                        textAlign: 'center',
                        borderRadius: '4px'
                    }
                },
                    el('svg', { 
                        width: 48, 
                        height: 48, 
                        viewBox: '0 0 24 24',
                        style: { marginBottom: '10px', opacity: 0.5 }
                    },
                        el('path', { 
                            d: 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z',
                            fill: '#666'
                        })
                    ),
                    el('h3', { style: { margin: '0 0 8px' } }, 'Formulaire Contact Gerfaut'),
                    el('p', { style: { margin: 0, color: '#666' } }, 'Le formulaire sera affiché ici sur la page publiée.')
                )
            );
        },
        save: function() {
            return null; // Rendu dynamique côté serveur
        }
    });

    // Bloc Sticker personnalisé
    registerBlockType('gerfaut/sticker-builder', {
        title: 'Sticker personnalisé Gerfaut',
        icon: formIcon,
        category: 'embed',
        attributes: {
            productId: { type: 'number', default: 0 },
            orientation: { type: 'string', default: 'portrait' },
            dimen: { type: 'number', default: 62 }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { productId, orientation, dimen } = attributes;

            return el('div', { className: 'gerfaut-block-placeholder' },
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Paramètres sticker' },
                        el(TextControl, {
                            label: 'ID produit WooCommerce',
                            type: 'number',
                            value: productId,
                            onChange: (value) => setAttributes({ productId: Number(value) || 0 })
                        }),
                        el('label', { style: { display: 'block', marginTop: '10px' } }, 'Orientation'),
                        el('select', {
                            value: orientation,
                            onChange: (event) => setAttributes({ orientation: event.target.value })
                        },
                            el('option', { value: 'portrait' }, 'Portrait (62mm largeur)'),
                            el('option', { value: 'landscape' }, 'Paysage (62mm hauteur)')
                        ),
                        el(TextControl, {
                            label: orientation === 'portrait' ? 'Hauteur (mm)' : 'Largeur (mm)',
                            type: 'number',
                            value: dimen,
                            min: 10,
                            onChange: (value) => setAttributes({ dimen: Number(value) || 62 })
                        })
                    )
                ),
                el('div', { style: { padding: '20px', background: '#f9fafb', border: '1px dashed #ccc' } },
                    el('h3', {}, 'Sticker personnalisé Gerfaut'),
                    el('p', {}, 'Le formulaire sera rendu sur la page avec le shortcode [gerfaut_sticker].'),
                    el('p', {}, 'Produit ID: ' + productId),
                    el('p', {}, 'Orientation: ' + orientation),
                    el('p', {}, 'Donnée dimension: ' + dimen + 'mm')
                )
            );
        },
        save: function() {
            return null;
        }
    });

})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components
);
