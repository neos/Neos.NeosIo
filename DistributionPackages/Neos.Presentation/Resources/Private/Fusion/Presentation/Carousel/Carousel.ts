import Swiper from 'swiper';
import { Autoplay } from 'swiper/modules';

export function initCarousels() {
    document.querySelectorAll('.n-carousel .swiper').forEach((swiperElement) => {
        if (swiperElement instanceof HTMLElement) {
            const autoplayEnabled = swiperElement.dataset.autoplay === 'true';
            const swiper = new Swiper(swiperElement, {
                modules: [Autoplay],
                breakpoints: {
                    0: {
                        slidesPerView: 1.2,
                        spaceBetween: 16,
                        centeredSlides: true,
                    },
                    [640]: {
                        slidesPerView: 2.5,
                        spaceBetween: 16,
                        centeredSlides: false,
                    }
                },
                autoplay: autoplayEnabled ? {
                    delay: 5000,
                    pauseOnMouseEnter: true,
                    disableOnInteraction: true,
                } : false,
            });

            // init navigation
            const navPrev = swiperElement.querySelector('.button-prev');
            const navNext = swiperElement.querySelector('.button-next');

            navPrev.addEventListener('click', () => { swiper.slidePrev() });
            navNext.addEventListener('click', () => { swiper.slideNext() });

            // init disabled state of nav buttons
            swiper.isBeginning && navPrev.setAttribute('disabled', 'disabled');
            swiper.isEnd && navNext.setAttribute('disabled', 'disabled');

            swiper.on('slideChange', () => {
                if (swiper.isBeginning) {
                    navPrev.setAttribute('disabled', 'disabled');
                } else {
                    navPrev.removeAttribute('disabled');
                }

                if (swiper.isEnd) {
                    navNext.setAttribute('disabled', 'disabled');
                } else {
                    navNext.removeAttribute('disabled');
                }
            });
        }
    });
}
