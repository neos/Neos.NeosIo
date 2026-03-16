type IconElement = HTMLElement & {originalCenter: {x: number, y: number}};

export function initStage() {
    // do not initialize the effect on devices that request reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    document.querySelectorAll('[data-component="hero-stage-icon-box-container"]').forEach((container) => {
        const containerRect = container.getBoundingClientRect();
        const icons = container.querySelectorAll<IconElement>('.square-icon');

        icons.forEach(icon => {
            // calculate the original center of the icon relative to the container
            const iconRect = icon.getBoundingClientRect();
            icon.originalCenter = {
                x: iconRect.x + iconRect.width / 2 - containerRect.x,
                y: iconRect.y + iconRect.height / 2 - containerRect.y
            };

            icon.style.transition = 'transform 0.3s ease-out';
        })


        container.parentElement.addEventListener('mousemove', (event) => {
            const pointerPosition = {
                x: Math.max(0, event.pageX - containerRect.x),
                y: Math.max(0, event.pageY - containerRect.y),
            };

            icons.forEach(icon => {
                const distanceVector = {
                    x: pointerPosition.x - icon.originalCenter.x,
                    y: pointerPosition.y - icon.originalCenter.y,
                };

                const distance = Math.sqrt(distanceVector.x * distanceVector.x + distanceVector.y * distanceVector.y);
                // reduce the distance to create a more subtle effect, and to prevent icons from moving too much when the mouse is far away
                const scaledDistance = distance / Math.sqrt(distance);

                const translateVector = {
                    x: distanceVector.x / scaledDistance,
                    y: distanceVector.y / scaledDistance,
                };

                icon.style.transform = `translate(${translateVector.x}px, ${translateVector.y}px)`;
            });
        });

        container.parentElement.addEventListener('mouseleave', (event) => {
            icons.forEach(icon => {
                icon.style.transform = '';
            });
        });
    });
}
