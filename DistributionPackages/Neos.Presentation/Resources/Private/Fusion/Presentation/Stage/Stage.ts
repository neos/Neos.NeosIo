type IconElement = HTMLElement & {originalX: number, originalY: number};

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

        document.addEventListener('mousemove', (event) => {
            icons.forEach(icon => {
                // move icon towards the mouse position with a slight offset depending on the distance
                const offsetX = calcOffset(event.clientX, icon.originalX);
                const offsetY = calcOffset(event.clientY, icon.originalY);

                icon.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
            });
        });

        document.addEventListener('mouseleave', (event) => {
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
