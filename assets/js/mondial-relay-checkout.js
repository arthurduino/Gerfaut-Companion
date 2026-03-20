(function(init) {
    function ready($) {
        init($);
    }

    if (typeof window.jQuery === 'undefined') {
        var interval = setInterval(function() {
            if (typeof window.jQuery !== 'undefined') {
                clearInterval(interval);
                ready(window.jQuery);
            }
        }, 50);
    } else {
        ready(window.jQuery);
    }
})(function($) {
    const config = window.gerfautMondialRelay || {};
    const ajaxUrl = config.ajaxUrl;
    const nonce = config.nonce;
    const shippingMethodId = config.shippingMethodId;

    const leafletCssUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    const leafletJsUrl = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

    const selectors = {
        container: '.gerfaut-mondial-relay-container',
        selected: '.gerfaut-mondial-relay-selected',
        button: '.gerfaut-mondial-relay-open-map',
        input: '.gerfaut-mondial-relay-point',
    };

    function isShippingMethodSelected() {
        let selected = false;
        $("input[name^='shipping_method']").each(function() {
            const val = $(this).val();
            if (val && val.indexOf(shippingMethodId) === 0 && $(this).is(':checked')) {
                selected = true;
                return false;
            }
        });
        return selected;
    }

    function updateContainerVisibility() {
        $(selectors.container).each(function() {
            const $container = $(this);
            if (isShippingMethodSelected()) {
                $container.show();
            } else {
                $container.hide();
            }
        });
    }

    function ensureLeaflet() {
        console.log('Gerfaut Relay: ensureLeaflet called');
        return new Promise((resolve, reject) => {
            if (typeof L !== 'undefined') {
                console.log('Gerfaut Relay: Leaflet already loaded');
                return resolve();
            }

            // Load Leaflet CSS if not already present
            if (!$("link[href*='leaflet.css']").length) {
                $("head").append('<link rel="stylesheet" href="' + leafletCssUrl + '" />');
            }

            // If Leaflet script already in DOM, wait for it to load
            let existing = document.querySelector("script[src*='leaflet']");
            if (existing) {
                if (existing.getAttribute('data-loaded') === '1' || typeof L !== 'undefined') {
                    return resolve();
                }

                existing.addEventListener('load', () => resolve());
                existing.addEventListener('error', () => reject(new Error('Impossible de charger Leaflet.')));
                return;
            }

            const script = document.createElement('script');
            script.src = leafletJsUrl;
            script.async = true;
            script.onload = () => {
                script.setAttribute('data-loaded', '1');
                resolve();
            };
            script.onerror = () => reject(new Error('Impossible de charger Leaflet.'));
            document.head.appendChild(script);
        });
    }

    function showError(message) {
        $(selectors.container).each(function() {
            $(this).find(selectors.selected).text('Erreur : ' + message);
        });
    }

    function showModal() {
        console.log('Gerfaut Relay: openModal called');
        let $modal, $map, $results, $query;
        let map;
        let markers = [];
        let selectedPoint = null;

        function clearMarkers() {
            if (!map) {
                return;
            }
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
        }

        function renderResults(points) {
            if (!$results) {
                return;
            }

            $results.empty();
            if (!points || !points.length) {
                $results.append('<div class="gerfaut-mondial-relay-loading">Aucun point relais trouvé.</div>');
                return;
            }

            clearMarkers();

            points.forEach(point => {
                const $item = $(
                    '<div class="gerfaut-mondial-relay-result" data-point-id="' + point.id + '">' +
                        '<strong>' + point.name + '</strong><br>' +
                        '<span>' + point.address + '</span><br>' +
                        '<span>' + point.postcode + ' ' + point.city + '</span><br>' +
                        '<small>' + (point.distance ? point.distance + ' km' : '') + '</small>' +
                    '</div>'
                );

                $item.on('click', () => {
                    selectPoint(point);
                    $results.find('.gerfaut-mondial-relay-result').removeClass('gerfaut-mondial-relay-result--selected');
                    $item.addClass('gerfaut-mondial-relay-result--selected');
                });

                $results.append($item);

                if (map && point.lat && point.lng) {
                    const marker = L.marker([point.lat, point.lng]);
                    marker.bindPopup('<strong>' + point.name + '</strong><br>' + point.address + '<br>' + point.postcode + ' ' + point.city);
                    marker.on('click', function() {
                        selectPoint(point);
                        $results.find('.gerfaut-mondial-relay-result').removeClass('gerfaut-mondial-relay-result--selected');
                        $item.addClass('gerfaut-mondial-relay-result--selected');
                    });

                    marker.addTo(map);
                    markers.push(marker);
                }
            });

            const first = points[0];
            if (map && first && first.lat && first.lng) {
                map.setView([first.lat, first.lng], 12);
            }
        }

        function selectPoint(point) {
            selectedPoint = point;
            const $container = $(selectors.container).first();
            $container.find(selectors.input).val(JSON.stringify(point));
            $container.find(selectors.selected).text(point.name + ' — ' + point.address + ' ' + point.postcode + ' ' + point.city);

            $.post(ajaxUrl, {
                action: 'gerfaut_mondial_relay_save_point',
                nonce: nonce,
                point: JSON.stringify(point),
            });

            closeModal();
        }

        function fetchPoints(query) {
            if (!$results) {
                return;
            }

            $results.html('<div class="gerfaut-mondial-relay-loading">Chargement…</div>');

            $.getJSON(ajaxUrl, {
                action: 'gerfaut_mondial_relay_get_points',
                nonce: nonce,
                postcode: query,
                city: query,
            }).done((response) => {
                if (!response.success || !response.data) {
                    $results.html('<div class="gerfaut-mondial-relay-loading">' + (response.data || 'Erreur lors de la récupération des points.') + '</div>');
                    return;
                }
                renderResults(response.data);
            }).fail(() => {
                $results.html('<div class="gerfaut-mondial-relay-loading">Erreur réseau.</div>');
            });
        }

        function closeModal() {
            if ($modal) {
                $modal.remove();
            }
        }

        ensureLeaflet().then(() => {
            $modal = $(
                '<div class="gerfaut-mondial-relay-modal" role="dialog" aria-modal="true">' +
                    '<div class="gerfaut-mondial-relay-modal__content">' +
                        '<div class="gerfaut-mondial-relay-modal__header">' +
                            '<h2 class="gerfaut-mondial-relay-modal__title">' +
                                'Choisir un point relais / locker' +
                            '</h2>' +
                            '<button type="button" class="gerfaut-mondial-relay-modal__close" aria-label="Fermer">&times;</button>' +
                        '</div>' +
                        '<div class="gerfaut-mondial-relay-modal__body">' +
                            '<div class="gerfaut-mondial-relay-search">' +
                                '<input type="text" class="gerfaut-mondial-relay-query" placeholder="Code postal ou ville" />' +
                                '<button type="button" class="button gerfaut-mondial-relay-search-button">Rechercher</button>' +
                            '</div>' +
                            '<div class="gerfaut-mondial-relay-map" style="height:320px;"></div>' +
                            '<div class="gerfaut-mondial-relay-results"></div>' +
                        '</div>' +
                    '</div>' +
                '</div>'
            );

            $('body').append($modal);

            $map = $modal.find('.gerfaut-mondial-relay-map');
            $results = $modal.find('.gerfaut-mondial-relay-results');
            $query = $modal.find('.gerfaut-mondial-relay-query');

            map = L.map($map.get(0)).setView([46.5, 2.5], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            $modal.on('click', function(event) {
                if ($(event.target).is($modal)) {
                    closeModal();
                }
            });

            $modal.find('.gerfaut-mondial-relay-modal__close').on('click', closeModal);
            $modal.find('.gerfaut-mondial-relay-search-button').on('click', function() {
                const query = $query.val().trim();
                if (!query) {
                    return;
                }
                fetchPoints(query);
            });

            // Pre-fill query with the current delivery postcode if available
            const postcode = $('#shipping_postcode, #billing_postcode').first().val();
            if (postcode) {
                $query.val(postcode);
                fetchPoints(postcode);
            }

            // If a point is already selected from session, highlight it
            fetchSelectedPoint().then(point => {
                if (point && point.id) {
                    selectedPoint = point;
                }
            });
        }).catch((err) => {
            showError(err && err.message ? err.message : 'Impossible de charger la carte.');
        });
    }

    function fetchSelectedPoint() {
        return new Promise((resolve) => {
            $.getJSON(ajaxUrl, {
                action: 'gerfaut_mondial_relay_get_selected_point',
                nonce: nonce,
            }).done((response) => {
                if (response.success && response.data) {
                    resolve(response.data);
                } else {
                    resolve(null);
                }
            }).fail(() => resolve(null));
        });
    }

    function onUpdateShippingMethod() {
        updateContainerVisibility();
    }

    // Expose a global helper so inline onclick can open the modal even if event delegation fails.
    window.gerfautMondialRelay = window.gerfautMondialRelay || {};
    window.gerfautMondialRelay.openModal = showModal;

    $(function() {
        console.log('Gerfaut Relay: script loaded');
        updateContainerVisibility();

        // Update when shipping method changes
        $(document.body).on('change', "input[name^='shipping_method']", onUpdateShippingMethod);

        // Open picker when button clicked
        $(document.body).on('click', selectors.button, function(e) {
            e.preventDefault();
            showModal();
        });

        // When page loads, if method already selected, show summary
        fetchSelectedPoint().then(point => {
            if (point && point.name) {
                const $container = $(selectors.container).first();
                $container.find(selectors.selected).text(point.name + ' — ' + point.address + ' ' + point.postcode + ' ' + point.city);
                $container.find(selectors.input).val(JSON.stringify(point));
            }
        });
    });
})(jQuery);
