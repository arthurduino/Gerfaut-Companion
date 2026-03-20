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

    // Bloc Sticker Personnalisé
    registerBlockType('gerfaut/sticker-builder', {
        title: 'Sticker personnalisé Gerfaut',
        icon: formIcon,
        category: 'embed',
        attributes: {
            productId: { type: 'number', default: 0 },
            width: { type: 'number', default: 62 },
            height: { type: 'number', default: 62 },
            orientation: { type: 'string', default: 'portrait' },
            imageUrl: { type: 'string', default: '' },
            quantity: { type: 'number', default: 1 },
            threshold: { type: 'number', default: 128 }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { productId, width, height, orientation, imageUrl, quantity, threshold } = attributes;
            const ratio = (height > 0 ? (width / height).toFixed(2) : '1.00');
            const isPortrait = orientation === 'portrait';

            return el('div', { className: 'gerfaut-block-placeholder' },
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Personnalisation Sticker' },
                        el(TextControl, {
                            label: 'Largeur (mm)',
                            type: 'number',
                            value: width,
                            min: 10,
                            onChange: (value) => setAttributes({ width: parseInt(value) || 62 })
                        }),
                        el(TextControl, {
                            label: 'Hauteur (mm)',
                            type: 'number',
                            value: height,
                            min: 10,
                            onChange: (value) => setAttributes({ height: parseInt(value) || 62 })
                        }),
                        el(components.TextControl, {
                            label: 'ID Produit WooCommerce',
                            value: productId,
                            type: 'number',
                            onChange: (value) => setAttributes({ productId: parseInt(value, 10) || 0 })
                        }),
                        el(components.SelectControl, {
                            label: 'Orientation',
                            value: orientation,
                            options: [
                                { label: 'Portrait (62mm x Xmm)', value: 'portrait' },
                                { label: 'Paysage (Xmm x 62mm)', value: 'landscape' }
                            ],
                            onChange: (value) => setAttributes({ orientation: value })
                        }),
                        el(TextControl, {
                            label: 'URL de l’image de sticker',
                            value: imageUrl,
                            onChange: (value) => setAttributes({ imageUrl: value })
                        }),
                        el(TextControl, {
                            label: 'Quantité',
                            type: 'number',
                            value: quantity,
                            min: 1,
                            onChange: (value) => setAttributes({ quantity: parseInt(value) || 1 })
                        }),
                        el('label', { style: { display: 'block', marginTop: '10px' } }, 'Seuil de noir (0-255)'),
                        el('input', {
                            type: 'range',
                            min: 0,
                            max: 255,
                            value: threshold,
                            onInput: (e) => setAttributes({ threshold: parseInt(e.target.value) })
                        }),
                        el('div', {}, 'Noir seuil : ' + threshold),
                        el('p', { style: { marginTop: '10px' } }, 'Ratio objectif 62mm x Xmm : ' + ratio)
                    )
                ),
                el('div', { style: { padding: '20px', background: '#f9fafb', border: '1px dashed #ccc' } },
                    el('h4', {}, 'Formulaire de personnalisation de sticker'),
                    imageUrl ? el('img', { src: imageUrl, style: { maxWidth: '100%', height: 'auto', marginBottom: '10px' } }) : el('div', { style: { marginBottom: '10px', color: '#666' } }, 'Aucune image fournie.'),
                    el('p', {}, 'Dimensions : ' + width + 'mm x ' + height + 'mm (' + orientation + ')'),
                    el('p', {}, 'Quantité : ' + quantity),
                    el('p', {}, 'Seuil de noir : ' + threshold)
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
