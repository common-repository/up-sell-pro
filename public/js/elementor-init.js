(function ($) {
    'use strict';
////////////
//// Flash Sale
////////////
const aliceFlashSale = ($scope, $) => {
    if ($scope && $scope.length && $scope[0].dataset.id) {
        const slider = document.querySelector(`.blog-slider-${$scope[0].dataset.id}`);
        const rootId = $scope[0].dataset.id
        new Swiper(`.blog-slider-${rootId}`, {
            spaceBetween: 30,
            effect: 'fade',
            loop: true,
            pagination: {
                el: '.blog-slider__pagination',
                clickable: true,
            },
            autoplay: (slider.dataset.autorotate === 'yes') ? {
                delay: +slider.dataset.autorotateDelay,
                disableOnInteraction: false,
            } : false,
        });
        ////////////
        // Countdown
        //////////////
        const target = document.querySelectorAll(`.blog-slider-${rootId} .alice-countdown`);
        if (target && target.length) {
            target.forEach((entry,i ) => {
                const id = $(entry).data('id');
                $(`.blog-slider-${rootId} #alice-countdown-${id}`).flipper('init');
            });
        }
    }
};

// Make sure you run this code under Elementor.
$(window).on('elementor/frontend/init', () => {
    elementorFrontend.hooks.addAction('frontend/element_ready/lav-boost-flash-sale.default', aliceFlashSale);
});

})(jQuery);
