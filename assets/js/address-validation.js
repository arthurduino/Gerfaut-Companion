(function($) {
    const config = window.gerfautAddressValidation || {};
    const apiBase = config.apiBase || 'https://api-adresse.data.gouv.fr/search/';
    const limit = config.limit || 5;
    const minChars = config.minChars || 4;
    const minScore = config.minScore || 0.5;
    const debounceMs = config.debounceMs || 300;
    const messages = config.messages || {};

    const state = {
        billing: createState('billing'),
        shipping: createState('shipping'),
    };

    function createState(prefix) {
        return {
            prefix,
            lastQuery: '',
            lastResults: [],
            selected: null,
            forced: false,
            debounceTimer: null,
        };
    }

    function getFields(prefix) {
        return {
            address1: $('#' + prefix + '_address_1'),
            postcode: $('#' + prefix + '_postcode'),
            city: $('#' + prefix + '_city'),
            fieldWrapper: $('#' + prefix + '_address_1_field'),
        };
    }

    function getQuery(prefix) {
        const fields = getFields(prefix);
        const address1 = fields.address1.val() ? fields.address1.val().trim() : '';
        const postcode = fields.postcode.val() ? fields.postcode.val().trim() : '';
        const city = fields.city.val() ? fields.city.val().trim() : '';
        return [address1, postcode, city].filter(Boolean).join(' ');
    }

    function ensureWrapper(prefix) {
        const fields = getFields(prefix);
        if (!fields.address1.length || !fields.fieldWrapper.length) {
            return;
        }

        if (!fields.fieldWrapper.hasClass('gerfaut-address-wrapper')) {
            fields.fieldWrapper.addClass('gerfaut-address-wrapper');
        }

        if (fields.fieldWrapper.find('.gerfaut-address-suggestions').length === 0) {
            fields.fieldWrapper.append('<div class="gerfaut-address-suggestions" style="display:none;"></div>');
        }

        if (fields.fieldWrapper.find('.gerfaut-address-hint').length === 0) {
            fields.fieldWrapper.append('<div class="gerfaut-address-hint" style="display:none;"></div>');
        }
    }

    function setHint(prefix, type, text) {
        const fields = getFields(prefix);
        const hint = fields.fieldWrapper.find('.gerfaut-address-hint');
        if (!hint.length) {
            return;
        }
        hint.removeClass('gerfaut-address-hint--valid gerfaut-address-hint--invalid gerfaut-address-hint--forced');
        if (type === 'valid') {
            hint.addClass('gerfaut-address-hint--valid');
        } else if (type === 'forced') {
            hint.addClass('gerfaut-address-hint--forced');
        } else if (type === 'invalid') {
            hint.addClass('gerfaut-address-hint--invalid');
        }
        hint.text(text || '').show();
    }

    function clearHint(prefix) {
        const fields = getFields(prefix);
        const hint = fields.fieldWrapper.find('.gerfaut-address-hint');
        if (hint.length) {
            hint.text('').hide();
        }
    }

    function hideSuggestions(prefix) {
        const fields = getFields(prefix);
        fields.fieldWrapper.find('.gerfaut-address-suggestions').hide().empty();
    }

    function renderSuggestions(prefix, features) {
        const fields = getFields(prefix);
        const container = fields.fieldWrapper.find('.gerfaut-address-suggestions');
        if (!container.length) {
            return;
        }

        if (!features || !features.length) {
            container.html('<div class="gerfaut-address-empty">' + (messages.noResults || 'Aucune adresse proposée') + '</div>');
            container.show();
            return;
        }

        const list = $('<ul class="gerfaut-address-list"></ul>');
        features.forEach((feature) => {
            const label = feature.properties && feature.properties.label ? feature.properties.label : 'Adresse';
            const item = $('<li class="gerfaut-address-item"></li>').text(label);
            item.on('click', function() {
                applySuggestion(prefix, feature);
            });
            list.append(item);
        });
        container.empty().append(list).show();
    }

    function applySuggestion(prefix, feature) {
        const fields = getFields(prefix);
        if (!feature || !feature.properties) {
            return;
        }
        const props = feature.properties;
        const addressLine = props.name || props.label || '';
        fields.address1.val(addressLine).trigger('change');
        if (props.postcode) {
            fields.postcode.val(props.postcode).trigger('change');
        }
        if (props.city) {
            fields.city.val(props.city).trigger('change');
        }

        state[prefix].selected = feature;
        state[prefix].forced = false;

        // Vérifier si c'est une rue sans numéro (mais pas un lieu-dit)
        if (props.type === 'street' && !props.housenumber) {
            setHint(prefix, 'forced', messages.warning || 'Attention : numéro de voie manquant');
        } else {
            setHint(prefix, 'valid', messages.valid || 'Adresse validée');
        }
        hideSuggestions(prefix);
    }

    function isSelectedMatch(prefix, feature) {
        if (!feature || !feature.properties) {
            return false;
        }
        const fields = getFields(prefix);
        const props = feature.properties;
        const addressLine = props.name || props.label || '';
        const address1 = fields.address1.val() ? fields.address1.val().trim() : '';
        const postcode = fields.postcode.val() ? fields.postcode.val().trim() : '';
        const city = fields.city.val() ? fields.city.val().trim() : '';

        if (!address1 || !postcode || !city) {
            return false;
        }

        return address1 === addressLine && postcode === props.postcode && city === props.city;
    }

    function fetchSuggestions(query) {
        return $.getJSON(apiBase, { q: query, limit: limit });
    }

    function suggest(prefix) {
        const query = getQuery(prefix);
        if (query.length < minChars) {
            hideSuggestions(prefix);
            clearHint(prefix);
            return;
        }

        state[prefix].lastQuery = query;
        fetchSuggestions(query)
            .done((data) => {
                const features = data && data.features ? data.features : [];
                state[prefix].lastResults = features;
                renderSuggestions(prefix, features);
                
                // Valider automatiquement la meilleure suggestion pour feedback immédiat
                const best = features[0];
                if (best && best.properties && typeof best.properties.score === 'number' && best.properties.score >= minScore) {
                    const props = best.properties;
                    if (isSelectedMatch(prefix, best)) {
                        state[prefix].selected = best;
                        if (props.type === 'street' && !props.housenumber) {
                            setHint(prefix, 'forced', messages.warning || 'Attention : numéro de voie manquant');
                        } else {
                            setHint(prefix, 'valid', messages.valid || 'Adresse validée');
                        }
                    } else {
                        setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
                    }
                } else if (features.length > 0) {
                    setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
                } else {
                    setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
                }
            })
            .fail(() => {
                renderSuggestions(prefix, []);
                setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
            });
    }

    function debounceSuggest(prefix) {
        clearTimeout(state[prefix].debounceTimer);
        state[prefix].debounceTimer = setTimeout(() => suggest(prefix), debounceMs);
    }

    function validatePrefix(prefix, forSubmit) {
        const query = getQuery(prefix);
        const deferred = $.Deferred();

        if (query.length < minChars) {
            setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
            renderSuggestions(prefix, []);
            if (forSubmit) {
                deferred.resolve(false);
            } else {
                deferred.resolve(false);
            }
            return deferred.promise();
        }

        if (state[prefix].selected && isSelectedMatch(prefix, state[prefix].selected)) {
            setHint(prefix, 'valid', messages.valid || 'Adresse validée');
            deferred.resolve(true);
            return deferred.promise();
        }

        fetchSuggestions(query)
            .done((data) => {
                const features = data && data.features ? data.features : [];
                state[prefix].lastResults = features;
                renderSuggestions(prefix, features);

                const best = features[0];
                if (best && best.properties && typeof best.properties.score === 'number' && best.properties.score >= minScore) {
                    state[prefix].selected = best;
                    state[prefix].forced = false;
                    
                    // Vérifier si c'est une rue sans numéro (mais pas un lieu-dit)
                    const props = best.properties;
                    if (props.type === 'street' && !props.housenumber) {
                        setHint(prefix, 'forced', messages.warning || 'Attention : numéro de voie manquant');
                        renderSuggestions(prefix, features);
                        
                        if (forSubmit) {
                            const confirmNoNumber = window.confirm(messages.confirmNoNumber || 'Cette adresse ne comporte pas de numéro de voie.\n\nVoulez-vous continuer ?');
                            if (confirmNoNumber) {
                                deferred.resolve(true);
                            } else {
                                deferred.resolve(false);
                            }
                        } else {
                            deferred.resolve(true);
                        }
                        return;
                    }
                    
                    setHint(prefix, 'valid', messages.valid || 'Adresse validée');
                    deferred.resolve(true);
                    return;
                }

                setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');

                if (forSubmit) {
                    const confirmProceed = window.confirm(messages.confirmProceed || 'Adresse non validée. Voulez-vous continuer ?');
                    if (confirmProceed) {
                        state[prefix].forced = true;
                        setHint(prefix, 'forced', messages.forced || 'Adresse non validée (confirmée par l’utilisateur)');
                        deferred.resolve(true);
                    } else {
                        deferred.resolve(false);
                    }
                } else {
                    deferred.resolve(false);
                }
            })
            .fail(() => {
                setHint(prefix, 'invalid', messages.invalid || 'Adresse non validée');
                if (forSubmit) {
                    const confirmProceed = window.confirm(messages.confirmProceed || 'Adresse non validée. Voulez-vous continuer ?');
                    if (confirmProceed) {
                        state[prefix].forced = true;
                        setHint(prefix, 'forced', messages.forced || 'Adresse non validée (confirmée par l’utilisateur)');
                        deferred.resolve(true);
                    } else {
                        deferred.resolve(false);
                    }
                } else {
                    deferred.resolve(false);
                }
            });

        return deferred.promise();
    }

    function initPrefix(prefix) {
        const fields = getFields(prefix);
        if (!fields.address1.length) {
            return;
        }

        ensureWrapper(prefix);

        if (!fields.address1.data('gerfaut-bound')) {
            fields.address1.data('gerfaut-bound', true);
            fields.address1.on('input', function() {
                state[prefix].selected = null;
                state[prefix].forced = false;
                clearHint(prefix);
                // Effacer les classes de validation WooCommerce
                fields.address1.removeClass('woocommerce-validated');
                fields.fieldWrapper.removeClass('woocommerce-validated');
                debounceSuggest(prefix);
            });
            fields.address1.on('blur', function() {
                validatePrefix(prefix, false);
            });
        }

        if (fields.postcode.length && !fields.postcode.data('gerfaut-bound')) {
            fields.postcode.data('gerfaut-bound', true);
            fields.postcode.on('input', function() {
                state[prefix].selected = null;
                state[prefix].forced = false;
                clearHint(prefix);
                fields.address1.removeClass('woocommerce-validated');
                fields.fieldWrapper.removeClass('woocommerce-validated');
                debounceSuggest(prefix);
            });
            fields.postcode.on('blur', function() {
                validatePrefix(prefix, false);
            });
        }

        if (fields.city.length && !fields.city.data('gerfaut-bound')) {
            fields.city.data('gerfaut-bound', true);
            fields.city.on('input', function() {
                state[prefix].selected = null;
                state[prefix].forced = false;
                clearHint(prefix);
                fields.address1.removeClass('woocommerce-validated');
                fields.fieldWrapper.removeClass('woocommerce-validated');
                debounceSuggest(prefix);
            });
            fields.city.on('blur', function() {
                validatePrefix(prefix, false);
            });
        }
    }

    function getActivePrefixes() {
        const prefixes = ['billing'];
        const shipDifferent = $('#ship-to-different-address-checkbox');
        if (shipDifferent.length && shipDifferent.is(':checked')) {
            prefixes.push('shipping');
        }
        return prefixes;
    }

    function initAll() {
        initPrefix('billing');
        initPrefix('shipping');
    }

    function interceptSubmit() {
        const form = $('form.checkout');
        if (!form.length) {
            return;
        }

        if (form.data('gerfaut-submit-bound')) {
            return;
        }

        form.data('gerfaut-submit-bound', true);
        let bypass = false;

        form.on('submit', function(e) {
            if (bypass) {
                return true;
            }

            e.preventDefault();

            const prefixes = getActivePrefixes();
            const validations = prefixes.map((prefix) => validatePrefix(prefix, true));

            $.when.apply($, validations).done(function() {
                const results = Array.from(arguments).map((result) => result === true);
                const allValid = results.every(Boolean);
                if (allValid) {
                    bypass = true;
                    if (form[0] && typeof form[0].submit === 'function') {
                        form[0].submit();
                    }
                }
            });
        });
    }

    $(document).ready(function() {
        initAll();
        interceptSubmit();
    });

    $(document.body).on('updated_checkout', function() {
        initAll();
        interceptSubmit();
    });

    $(document).on('click', function(event) {
        const target = $(event.target);
        if (!target.closest('.gerfaut-address-wrapper').length) {
            hideSuggestions('billing');
            hideSuggestions('shipping');
        }
    });

})(jQuery);
