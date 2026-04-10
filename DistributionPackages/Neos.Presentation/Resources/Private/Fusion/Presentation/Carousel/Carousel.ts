import Swiper from 'swiper';

export function initCarousels() {
    document.querySelectorAll('.n-carousel.swiper').forEach((swiperElement) => {
        if (swiperElement instanceof HTMLElement) {
            const autplayEnabled = swiperElement.dataset.autoplay === 'true';
            const swiper = new Swiper(swiperElement, {
                slidesPerView: 2.5,
                spaceBetween: 16,
                autoplay: autplayEnabled,
            });

            const navPrev = swiperElement.querySelector('.button-prev');
            const navNext = swiperElement.querySelector('.button-next');

            navPrev.addEventListener('click', () => { swiper.slidePrev() });
            navNext.addEventListener('click', () => { swiper.slideNext() });
        }
    });
}
