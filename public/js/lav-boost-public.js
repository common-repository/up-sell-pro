(function ($) {
    'use strict';

    $(document).ready(function () {

        //
        // Action Button Gift
        //
        const actionButton = document.getElementById('up-sell-gift-action-btn');

        const toggleActionButton = () => {
            if (actionButton.classList.contains('actionsBoxOpen')) {
                actionButton.classList.remove('actionsBoxOpen');
            } else {
                actionButton.classList.add('actionsBoxOpen');
            }
        }
        if (actionButton) {
            actionButton.addEventListener('click', toggleActionButton);
        }
        //
        // Track Search
        //
        const addSearchQuery = (searchQuery) => {
            const search = JSON.parse(Cookies.get('lav-boost-search'));
            const queries = new Set([...search, searchQuery]);
            Cookies.set('lav-boost-search', JSON.stringify(Array.from(queries)));
        }

        const escape = (value) => value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        const trackSearch = () => {
            $('form[role="search"]').submit(function () {
                if (Array.isArray($(this).serializeArray()) && $(this).serializeArray()[0].value) {
                    addSearchQuery(escape($(this).serializeArray()[0].value));
                }
            });
        };

        if (Cookies.get('lav-boost-search')) {
            trackSearch();
        }
        //
        // Update upsell in product
        //
        const addLink = document.querySelector('.up-sell-products .lav-btn');
        const addButton = document.querySelector('.up-sell-products .single_add_to_cart_button');
        const addCheckboxes = document.querySelectorAll('.up-sell-products .box');
        const cards = document.querySelectorAll('.up-sell-products .card');
        const priceFull = document.querySelector('.up-sell-products .price-full');
        const priceFullValue = document.querySelector('.up-sell-products .price-full .value-p');
        const fullPriceLine = document.querySelector('.up-sell-products .full-price-line');

        const addDisableToProduct = (id, prefix) => {
            const card = document.querySelector(`${prefix} .related-product-id-${id}`);
            card.classList.add('disabled');
        }

        const removeDisableToProduct = (id, prefix) => {
            const card = document.querySelector(`${prefix} .related-product-id-${id}`);
            card.classList.remove('disabled');
        }

        const addDisableToButton = (el) => {
            el.disabled = true;
        }

        const hideFullPriceLine = () => {
            if(fullPriceLine){
                fullPriceLine.style.display = 'none';
            }
        }
        const showFullPriceLine = () => {
            if(fullPriceLine){
                fullPriceLine.style.display = 'inline';
            }
        }

        const removeDisableToButton = (el) => {
            el.removeAttribute('disabled');
        }

        const getFullPrice = () => {
            let fullPrice = null;
            if (cards.length) {
                cards.forEach(elem => {
                    if (!elem.classList.contains('disabled')) {
                        fullPrice += +elem.dataset.price;
                    }
                })
            }
            return fullPrice;
        }

        const updateNumber = (num, thousandSep, decimalSep, numDecimals) => {
            // Convert to float with specified number of decimals
            num = parseFloat(num).toFixed(numDecimals);
            // Convert to string and split into integer and decimal parts
            let parts = num.toString().split('.');
            // Add thousand separator to integer part
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
            // Join integer and decimal parts with decimal separator
            return parts.join(decimalSep);

        }
        const updatePrice = (value) => {
            if (priceFull && priceFullValue){
                priceFullValue.textContent = updateNumber(value, priceFull.dataset.thousand, priceFull.dataset.decimal, priceFull.dataset.num);
            }
        }

        const addAdditionalProduct = (id) => {
            const url = new URL(addLink.getAttribute('href'));
            url.searchParams.set('add-to-cart', [url.searchParams.get('add-to-cart'), id].join(','));
            addLink.setAttribute('href', url.href.replace(/%2C/g, ","));
            removeDisableToProduct(id, '.up-sell-products');
            if (url.searchParams.get('add-to-cart').split(',').length > 1) {
                removeDisableToButton(addButton);
                showFullPriceLine();
            }
        }

        const removeAdditionalProduct = (id) => {
            const url = new URL(addLink.getAttribute('href'));
            const hrefAttr = url.searchParams.get('add-to-cart').split(',').filter((item) => item !== id);
            url.searchParams.set('add-to-cart', hrefAttr.join(','));
            addLink.setAttribute('href', url.href.replace(/%2C/g, ","));
            addDisableToProduct(id, '.up-sell-products');
            if (url.searchParams.get('add-to-cart').split(',').length === 1) {
                addDisableToButton(addButton);
                hideFullPriceLine();
            }
        }

        const additionalProducts = (event) => {
            if (event.currentTarget.checked === true) {
                addAdditionalProduct(event.currentTarget.getAttribute('data-id'))
            } else {
                removeAdditionalProduct(event.currentTarget.getAttribute('data-id'))
            }
            updatePrice(getFullPrice());
        }

        addCheckboxes.forEach((elem) => {
            elem.addEventListener('click', additionalProducts);
        });


        //
        // Update accessories
        //
        const accAddLink = document.querySelector('.accessories-products .lav-btn');
        const accAddButton = document.querySelector('.accessories-products .single_add_to_cart_button');
        const accAddCheckboxes = document.querySelectorAll('.accessories-products .box');

        const addAdditionalAccessor = (id) => {
            const url = new URL(accAddLink.getAttribute('href'));
            url.searchParams.set('add-to-cart', [url.searchParams.get('add-to-cart'), id].join(','));
            accAddLink.setAttribute('href', url.href.replace(/%2C/g, ","));
            removeDisableToProduct(id, '.accessories-products');
            if (url.searchParams.get('add-to-cart').split(',').length > 1) {
                removeDisableToButton(accAddButton);
            }
        }

        const removeAdditionalAccessor = (id) => {
            const url = new URL(accAddLink.getAttribute('href'));
            const hrefAttr = url.searchParams.get('add-to-cart').split(',').filter((item) => item !== id);
            url.searchParams.set('add-to-cart', hrefAttr.join(','));
            accAddLink.setAttribute('href', url.href.replace(/%2C/g, ","));
            addDisableToProduct(id, '.accessories-products');
            if (url.searchParams.get('add-to-cart').split(',').length === 1) {
                addDisableToButton(accAddButton);
            }
        }


        const additionalAccessor = (event) => {
            if (event.currentTarget.checked === true) {
                addAdditionalAccessor(event.currentTarget.getAttribute('data-id'));
            } else {
                removeAdditionalAccessor(event.currentTarget.getAttribute('data-id'));
            }
        }

        accAddCheckboxes.forEach((elem) => {
            elem.addEventListener('click', additionalAccessor);
        });

        //
        // Social Proof Message
        //
        /* global lavBoost */
        /* global iziToast */
        if (typeof lavBoost !== 'undefined') {
            const proofs = lavBoost.proofs;

            if (proofs && proofs.length) {
                const interval = lavBoost.interval;
                const icon = lavBoost.icon;
                const verified = lavBoost.verified;
                const text = lavBoost.text || '';
                const theme = lavBoost.theme;
                const place = lavBoost.place;
                let intervalId = setInterval(showProof, interval);

                function showProof() {
                    if (proofs.length) {
                        const randomProof = proofs.reverse().pop();
                        const verify = verified ? `<img src="${icon}" alt="verified"> ${verified}</span></div>` : '';
                        const message = `<strong>${randomProof.product}</strong><div class="proof-verified"><span class="proof-date">${randomProof.date}</span> <span class="proof-text">${verify}`

                        iziToast.show({
                            theme: theme || 'light',
                            layout: 2,
                            class: 'lav-boost social-proofs',
                            maxWidth: 320,
                            imageWidth: 100,
                            close: true,
                            progressBar: true,
                            image: randomProof.img || randomProof.placeholder,
                            title: `${randomProof.name} ${text}`,
                            message: message,
                            position: place || 'bottomLeft', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
                            progressBarColor: '#0ABC82',
                            onOpened: function (instance, toast) {
                                clearInterval(intervalId);
                            },
                            onClosed: function (instance, toast, closedBy) {
                                intervalId = setInterval(showProof, interval);
                            }
                        });
                    } else {
                        clearInterval(intervalId);
                    }
                }
            }
        }

        //pop-up-slider
        const accessoriesSlider = new Swiper(".accessories-slider ", {
            slidesPerView: 2,
            spaceBetween: 5,
            navigation: {
                nextEl: ".accessories-slider .swiper-button-next",
                prevEl: ".accessories-slider .swiper-button-prev",
            },
            pagination: {
                el: ".accessories-products .button-row .swiper-pagination",
                dynamicBullets: true,
            },
            breakpoints: {
                640: {
                    slidesPerView: 1,
                    spaceBetween: 10,
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
            },
        });

        // Pop up Ajax button
        const popUpShow = (data) => {
            const body = document.querySelector('body');
            if (!data['markup'].includes('up-sell-products')) {
                return false;
            }
            popupS.window({
                mode: 'alert',
                title: data['title'],
                content: data['markup'],
                className: 'lav-boost-pop-up',
                additionalBaseClass: 'lav-boost',
                labelOk: data['continue'],
                onOpen: () => {
                    body.classList.add('lav-boost-pop-up-open');
                    localStorage.removeItem('addedToCartLavBoost');
                },
                onClose: () => {
                    body.classList.remove('lav-boost-pop-up-open');
                },
                onSubmit: () => {
                    body.classList.remove('lav-boost-pop-up-open');
                },
            });
        }

        /* global lavBoostPopUp */
        /* global wc_add_to_cart_params */
        if (typeof lavBoostPopUp !== 'undefined' && typeof lavBoostPopUp !== 'undefined') {
            const body = document.querySelector('body');

            $('body').on('added_to_cart', function (event, fragments, cart_hash, button) {
                const product_id = button.data('product_id');
                if (typeof wc_add_to_cart_params === 'undefined' && !body.classList.contains('lav-boost-not-ajax')) {
                    return false;
                }

                if (!product_id) {
                    return false;
                }

                if (body.classList.contains('lav-boost-pop-up-open')) {
                    return false;
                }

                $.ajax({
                    type: 'POST',
                    url: lavBoostPopUp.ajaxurl,
                    data: {
                        action: 'popUpResponse',
                        nonce: lavBoostPopUp.nonce,
                        id: product_id,
                    },
                    success: function (response) {

                        if (!response) {
                            return false;
                        }

                        if (wc_add_to_cart_params.cart_redirect_after_add === 'yes') {
                            return false;
                        }

                        if (!body.classList.contains('lav-boost-pop-up-open') && !body.classList.contains('woocommerce-cart')) {
                            popUpShow(response);
                        }
                    },
                    error: function (response) {
                        return false;
                    }
                });

            });

            // Pop up without AJAX
            $('.woocommerce-shop.lav-boost-not-ajax .add_to_cart_button').on('click', function () {
                const product_id = $(this).data('product_id');
                localStorage.setItem('addedToCartLavBoost', product_id);
            })

            const addedToCart = localStorage.getItem('addedToCartLavBoost');
            if (addedToCart) {

                $.ajax({
                    type: 'POST',
                    url: lavBoostPopUp.ajaxurl,
                    data: {
                        action: 'popUpResponse',
                        nonce: lavBoostPopUp.nonce,
                        id: addedToCart,
                    },
                    success: function (response) {
                        if (!response) {
                            return false;
                        }

                        if (!body.classList.contains('lav-boost-pop-up-open') && !body.classList.contains('woocommerce-cart')) {
                            popUpShow(response);
                        }

                    },
                    error: function (response) {
                        return false;
                    }
                });
            }
        }

        // Services
        /* global wc_add_to_cart_params */
        /* global lavBoostServices */
        if (typeof lavBoostServices !== 'undefined') {

            $(document).on('change', '.service-checkbox', function () {

                const promoProductId = $(this).data('product-id');
                const currentProductId = $(this).data('current-product-id');

                if(!promoProductId){
                    return false;
                }

                if ($(this).prop('checked')) {
                    addToCart(promoProductId);
                } else {
                    removeFromCart(promoProductId);
                }

                // Function to add a product to cart via AJAX
                function addToCart(productId) {
                    $.ajax({
                        type: 'POST',
                        url: lavBoostServices.ajaxurl,
                        data: {
                            action: 'service_add_to_cart',
                            product_id: productId,
                            current_id: currentProductId,
                            nonce: lavBoostServices.nonce,
                        },
                        success: function (response) {
                            if (!response) {
                                return;
                            }
                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }

                // Function to remove a product from cart via AJAX
                function removeFromCart(productId) {
                    $.ajax({
                        type: 'POST',
                        url: lavBoostServices.ajaxurl,
                        data: {
                            action: 'service_remove_cart_item',
                            nonce: lavBoostServices.nonce,
                            product_id: productId,
                            current_id: currentProductId,
                            cart_item_key: `lav_boost_services_is_in_cart-${currentProductId}-${productId}`
                        },
                        success: function (response) {
                            if (!response) {
                                return false;
                            }
                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }
            })
        }

        /* global lavBoostPromo */
        // Add promo product to cart
        if (typeof wc_add_to_cart_params !== 'undefined' && typeof lavBoostPromo !== 'undefined') {

            /* global wc_add_to_cart_params */
            if (typeof wc_add_to_cart_params === 'undefined' || !wc_add_to_cart_params.ajax_url) {
                return false;
            }

            $(document).on('change', '#promo-checkbox', function () {
                const promoProductId = $(this).data('product-id');
                const discount = $(this).data('discount');
                const discountType = $(this).data('discount-type');

                if ($(this).prop('checked')) {
                    if (promoProductId && discountType) {
                        addToCart(promoProductId);
                    }
                } else {
                    removeFromCart();
                }

                // Function to add a product to cart via AJAX
                function addToCart(productId) {
                    $.ajax({
                        type: 'POST',
                        url: wc_add_to_cart_params.ajax_url,
                        data: {
                            action: 'promo_add_to_cart',
                            product_id: productId,
                            discount: discount,
                            discount_type: discountType,
                            nonce: lavBoostPromo.nonce,
                        },
                        success: function (response) {
                            if (!response) {
                                return false;
                            }

                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }

                // Function to remove a product from cart via AJAX
                function removeFromCart() {
                    $.ajax({
                        type: 'POST',
                        url: wc_add_to_cart_params.ajax_url,
                        data: {
                            action: 'promo_remove_cart_item',
                            nonce: lavBoostPromo.nonce,
                            cart_item_key: 'lav_boost_promo_is_in_cart'
                        },
                        success: function (response) {
                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }
            })
        }

        /* global lavBoostDonate */
        /* global wc_add_to_cart_params */
        // Add donate to cart
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.ajax_url && typeof lavBoostDonate !== 'undefined') {

            $(document).on('change', '.donate-checkbox', function () {
                const promoProductId = $(this).data('product-id');

                if(!promoProductId){
                    return false;
                }

                if ($(this).prop('checked')) {
                    addToCart(promoProductId);
                } else {
                    removeFromCart(promoProductId);
                }

                // Function to add a product to cart via AJAX
                function addToCart(productId) {
                    $.ajax({
                        type: 'POST',
                        url: wc_add_to_cart_params.ajax_url,
                        data: {
                            action: 'donate_add_to_cart',
                            product_id: productId,
                            nonce: lavBoostDonate.nonce,
                        },
                        success: function (response) {
                            if (!response) {
                                return;
                            }
                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }

                // Function to remove a product from cart via AJAX
                function removeFromCart(productId) {
                    $.ajax({
                        type: 'POST',
                        url: wc_add_to_cart_params.ajax_url,
                        data: {
                            action: 'donate_remove_cart_item',
                            nonce: lavBoostDonate.nonce,
                            product_id: productId,
                            cart_item_key: 'lav_boost_donate_is_in_cart'
                        },
                        success: function (response) {
                            $(document.body).trigger('wc_fragment_refresh');
                            $('body').trigger('update_checkout');
                        },
                        error: function (response) {
                            return false;
                        }
                    });
                }
            })
        }
    });

})(jQuery);

