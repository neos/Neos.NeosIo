import { computePosition, autoUpdate, flip, offset, shift, arrow } from '@floating-ui/dom';

// This is basically the same as the Alpine anchor directive, but it adds an arrowClass option
// use it like this x-anchor.arrow.arrowClassName.arrowPadding (e.g. x-anchor.arrow.arrow-anchor.0)
// the fix it to the mouse use target as mouse

export default function (Alpine) {
    Alpine.magic('anchor', (el) => {
        if (!el._x_anchor) throw 'Alpine: No x-anchor directive found on element using $anchor...';

        return el._x_anchor;
    });

    Alpine.interceptClone((from, to) => {
        if (from && from._x_anchor && !to._x_anchor) {
            to._x_anchor = from._x_anchor;
        }
    });

    Alpine.directive(
        'anchor',
        Alpine.skipDuringClone(
            (el, { expression, modifiers }, { cleanup, evaluate }) => {
                let { placement, offsetValue, unstyled, arrowOptions } = getOptions(el, modifiers);

                el._x_anchor = Alpine.reactive({ x: 0, y: 0 });

                const middleware = [flip(), shift({ padding: 5 }), offset(offsetValue)];
                if (arrowOptions) {
                    middleware.push(arrow(arrowOptions));
                }

                if (expression == 'mouse') {
                    const mouseEventFunction = (position) => {
                        const reference = createVirtualElement(position);
                        initComputePosition({ reference, el, placement, middleware, unstyled, arrowOptions });
                    };

                    document.addEventListener('mousemove', mouseEventFunction);
                    cleanup(() => {
                        document.removeEventListener('mousemove', mouseEventFunction);
                    });
                    return;
                }

                const reference = evaluate(expression);
                if (!reference) {
                    throw 'Alpine: no element provided to x-anchor...';
                }

                let compute = () => {
                    initComputePosition({
                        reference,
                        el,
                        placement,
                        middleware,
                        unstyled,
                        arrowOptions,
                    });
                };

                let release = autoUpdate(reference, el, () => compute());

                cleanup(() => release());
            },

            // When cloning (or "morphing"), we will graft the style and position data from the live tree...
            (el, { expression, modifiers, value }, { cleanup, evaluate }) => {
                let { unstyled } = getOptions(el, modifiers);

                if (el._x_anchor) {
                    unstyled || setStyles(el, el._x_anchor.x, el._x_anchor.y);
                }
            },
        ),
    );
}

function setStyles(el, x, y) {
    Object.assign(el.style, {
        left: x + 'px',
        top: y + 'px',
        position: 'absolute',
    });
}

function getOptions(el, modifiers) {
    let positions = [
        'top',
        'top-start',
        'top-end',
        'right',
        'right-start',
        'right-end',
        'bottom',
        'bottom-start',
        'bottom-end',
        'left',
        'left-start',
        'left-end',
    ];
    let placement = positions.find((i) => modifiers.includes(i));
    let offsetValue = 0;
    let arrowOptions = null;
    if (modifiers.includes('arrow')) {
        let idx = modifiers.findIndex((i) => i === 'arrow');
        const arrowClass = modifiers[idx + 1] !== undefined ? `.${modifiers[idx + 1]}` : null;
        const arrowPadding = modifiers[idx + 2] !== undefined ? Number(modifiers[idx + 2]) : 0;
        const arrowElement = arrowClass ? el.querySelector(arrowClass) : null;
        const arrowLength = arrowElement.offsetWidth;
        offsetValue = Math.sqrt(2 * arrowLength ** 2) / 2;
        arrowOptions = {
            element: arrowElement,
            padding: arrowPadding,
            length: arrowLength,
        };
    }
    if (modifiers.includes('offset')) {
        let idx = modifiers.findIndex((i) => i === 'offset');

        offsetValue = modifiers[idx + 1] !== undefined ? Number(modifiers[idx + 1]) : offsetValue;
    }
    let unstyled = modifiers.includes('no-style');

    return { placement, offsetValue, unstyled, arrowOptions };
}

function initComputePosition({
    reference,
    el,
    placement,
    middleware,
    unstyled,
    arrowOptions,
    callback = (data) => {},
}) {
    let previousValue;
    computePosition(reference, el, {
        placement,
        middleware,
    }).then(({ x, y, middlewareData, placement }) => {
        unstyled || setStyles(el, x, y);
        if (middlewareData.arrow && arrowOptions) {
            const { x, y } = middlewareData.arrow;
            const side = placement.split('-')[0];

            const staticSide = {
                top: 'bottom',
                right: 'left',
                bottom: 'top',
                left: 'right',
            }[side];

            Object.assign(arrowOptions.element.style, {
                left: x != null ? `${x}px` : '',
                top: y != null ? `${y}px` : '',
                // Ensure the static side gets unset when
                // flipping to other placements' axes.
                right: '',
                bottom: '',
                [staticSide]: `${-arrowOptions.length / 2}px`,
                transform: 'rotate(45deg)',
                position: 'absolute',
            });
        }

        // Only trigger Alpine reactivity when the value actually changes...
        if (JSON.stringify({ x, y }) !== previousValue) {
            el._x_anchor.x = x;
            el._x_anchor.y = y;
        }

        previousValue = JSON.stringify({ x, y });

        callback({ x, y, middlewareData, placement });
    });
}

function createVirtualElement({ clientX, clientY }) {
    return {
        getBoundingClientRect() {
            return {
                width: 0,
                height: 0,
                x: clientX,
                y: clientY,
                left: clientX,
                right: clientX,
                top: clientY,
                bottom: clientY,
            };
        },
    };
}
