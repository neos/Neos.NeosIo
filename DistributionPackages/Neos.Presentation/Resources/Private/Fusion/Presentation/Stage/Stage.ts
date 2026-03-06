type IconElement = HTMLElement & {originalX: number, originalY: number};

/**
 * TODO: The calculations are not correct.
 *       We need to take the position of the container into account because the animation should be based on the mouse position relative to the container, not the entire viewport.
 */

export function initStage() {
    // do not initialize the effect on devices that request reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.querySelectorAll('[data-component="hero-stage-icon-box-container"]').forEach((container) => {
        const icons = container.querySelectorAll<IconElement>('.square-icon');

        icons.forEach(icon => {
            const rect = icon.getBoundingClientRect();
            icon.originalX = rect.left + rect.width / 2;
            icon.originalY = rect.top + rect.height / 2;
            icon.style.transition = 'transform 0.3s ease-out';
        })

        container.parentElement.addEventListener('mousemove', (event) => {
            // TODO: event is possibly on a child element like the Headline, Subline or Image
            //       So we need to get the rect of the container and compute the relative mouse position to that container
            icons.forEach(icon => {
                // move icon towards the mouse position with a slight offset depending on the distance
                const offsetX = calcOffset(event.offsetX, icon.originalX);
                const offsetY = calcOffset(event.offsetX, icon.originalY);

                icon.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
            });
        });

        container.parentElement.addEventListener('mouseleave', (event) => {
            icons.forEach(icon => {
                icon.style.transform = '';
            });
        });
    });
}

function calcOffset(target: number, origin: number) {
    const distance = target - origin;
    const effectStrength = 0.2; // adjust this value to increase/decrease the overall effect

    // reduce the offset based on the distance, so that icons further away move less
    return  distance / Math.log(1 + Math.abs(distance) * effectStrength) * effectStrength;
}
