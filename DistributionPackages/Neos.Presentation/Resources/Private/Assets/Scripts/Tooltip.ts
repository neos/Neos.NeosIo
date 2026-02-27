import { Alpine as AlpineType } from 'alpinejs';
import { computePosition, autoUpdate, flip, offset, shift, arrow, hide } from '@floating-ui/dom';

// x-tooltip adds a tooltip to an element, width x-tooltip="placement" (top, left, right, bottom, etc) will set the placement
// x-tooltips will add a tooltip to all elements with a data-tooltip, aria-label or title attribute. You can also set the placement here.
// The modifier stay-on-click will keep the tooltip open after clicking on the element (e.g. x-tooltip.stay-on-click)
// The modifier focus will show the tooltip on focus and hide it on blur (e.g. x-tooltip.focus)
// If inside a x-tooltips element an element has already x-tooltip it will not be overwritten

// If you want to fetch content you can set the data-tooltip-fetch attribute to a URL, e.g. x-tooltip data-tooltip-fetch="{url: 'internal/site', text: "Loading…" }"
// If you want to show html content you can set the data-tooltip-html, e.g. x-tooltip data-tooltip-html="CONTENT"

// Element tooltip be absolute positioned (with left:0 and top: 0) and have opacity 0
/*
The trigger element could look like this
<a href="#" x-tooltip aria-label="Tooltip Content">Link</a>
<a href="#" x-tooltip.stay-on-click aria-label="Tooltip Content">Link</a>

Or, to activate a bunch of tooltips at once
<div x-tooltips>
    <a href="#" aria-label="Tooltip Content">Link</a>
    <a href="#" aria-label="Tooltip Content 2">Link 2</a>
</div>

The tooltip element should look like this
<div id="tooltip" role="tooltip" aria-hidden="true">
    <span id="tooltip-content"></span>
    <div id="tooltip-arrow"></div>
</div>

I you remove the div with the id tooltip-arrow the arrow will be disabled

Per default the id of the tooltip is tooltip, but you can change it with the id modifier

If fixed is added as a modifier, the tooltip will be fixed positioned instead of absolute positioned
If fixed is set, the tooltip id will be fixed-tooltip (unless you change it with the id modifier)

With the offset modifier you can set the offset of the tooltip to the trigger element. Defaults to 6

*/
const xTooltipAttribute = 'x-tooltip';
const fetchAttribute = 'data-tooltip-fetch';
const contentAttribute = 'data-tooltip-html';
const attributes = [fetchAttribute, contentAttribute, 'data-tooltip', 'aria-label', 'title'];
const stayModifier = 'stay-on-click';
const focusModifier = 'focus';
const offsetModifier = 'offset';
const idModifier = 'id';
const fixedModifier = 'fixed';

const padding = 5;

let tooltipText: string | null;
let referenceEl: Element;
let placement = 'top';
let cleanup: () => void;
let timeout = 0;

export default function (Alpine: AlpineType) {
    // Directive: x-tooltip
    Alpine.directive('tooltip', (element, { expression, modifiers }, { evaluate }) => {
        const strategy = modifiers.includes(fixedModifier) ? 'fixed' : 'absolute';
        const id = modifiers.includes(idModifier)
            ? modifiers[modifiers.indexOf(idModifier) + 1]
            : strategy == 'fixed'
              ? 'tooltip-fixed'
              : 'tooltip';

        const { floatingEl, arrowElement, tooltipContent } = getElements(id);

        if (!floatingEl) {
            console.warn(`Tooltip with the id '${id}' element not found`);
            return;
        }
        if (!tooltipContent) {
            console.warn(`Target element for content of the tooltip with the id '${id}-content' element not found`);
            return;
        }

        const offsetValue = modifiers.includes(offsetModifier)
            ? (evaluate(modifiers[modifiers.indexOf(offsetModifier) + 1]) as number)
            : 6;
        const stayOnClick = modifiers.includes(stayModifier);
        const focusAction = modifiers.includes(focusModifier);
        let hasContent = true;

        const middleware = [offset(offsetValue), flip(), shift({ padding })];
        if (arrowElement) {
            middleware.push(arrow({ element: arrowElement }));
        }
        middleware.push(hide());

        function updateContent() {
            const attribute = attributes.find((attribute) => referenceEl.hasAttribute(attribute));
            if (!attribute) {
                console.warn('No tooltip content found');
                return;
            }

            tooltipText = referenceEl.getAttribute(attribute);
            if (!tooltipText || !tooltipContent) {
                hasContent = false;
                hideTooltip();
                return;
            }

            if (attribute === contentAttribute) {
                tooltipContent.innerHTML = tooltipText;
                return;
            }

            if (attribute !== fetchAttribute) {
                tooltipContent.textContent = tooltipText;
                return;
            }

            const { url, text } = evaluate(tooltipText) as { url?: string; text?: string };
            if (!url) {
                hasContent = false;
                hideTooltip();
                return;
            }
            tooltipContent.innerHTML = text || '';
            fetch(url)
                .then((response) => response.text())
                .then((html) => {
                    if (html) {
                        tooltipContent.innerHTML = html;
                        return;
                    }
                    hasContent = false;
                    hideTooltip();
                });
        }

        function updatePosition() {
            // @ts-ignore
            computePosition(referenceEl, floatingEl, {
                // @ts-ignore
                placement,
                middleware,
                strategy,
            }).then(({ x, y, placement, middlewareData }) => {
                // @ts-ignore
                Object.assign(floatingEl.style, {
                    transform: `translate(${roundByDPR(x)}px,${roundByDPR(y)}px)`,
                });

                // @ts-ignore
                if (middlewareData.hide.referenceHidden) {
                    hideTooltip();
                }

                if (!middlewareData.arrow || !arrowElement) {
                    return;
                }

                const { x: arrowX, y: arrowY } = middlewareData.arrow;

                const staticSide = {
                    top: 'bottom',
                    right: 'left',
                    bottom: 'top',
                    left: 'right',
                }[placement.split('-')[0]];

                Object.assign(arrowElement.style, {
                    left: arrowX != null ? `${arrowX}px` : '',
                    top: arrowY != null ? `${arrowY}px` : '',
                    right: '',
                    bottom: '',
                    // @ts-ignore
                    [staticSide]: '-4px',
                });
            });
        }

        function showTooltip(element: Element, expression: string) {
            if (!hasContent) {
                return;
            }
            referenceEl = element;
            placement = expression || 'top';
            // @ts-ignore
            floatingEl.style.opacity = '1';

            clearTimeout(timeout);
            // No timeout given, so we show it without a transition
            if (!timeout) {
                // @ts-ignore
                floatingEl.style.transition = 'none';
                timeout = window.setTimeout(() => {
                    // @ts-ignore
                    floatingEl.style.transition = null;
                }, 10);
            }

            updateContent();
            // @ts-ignore
            cleanup = autoUpdate(referenceEl, floatingEl, updatePosition);
        }

        function hideTooltip() {
            // @ts-ignore
            if (floatingEl.style.opacity == '0') {
                return;
            }
            // @ts-ignore
            floatingEl.style.opacity = '0';
            cleanup();
            timeout = window.setTimeout(() => {
                tooltipText = '';
                // @ts-ignore
                floatingEl.style.transition = 'none';
                timeout = 0;
            }, 500);
        }

        Alpine.bind(element, {
            '@mouseenter'() {
                showTooltip(element, expression);
            },
            '@mouseleave'() {
                hideTooltip();
            },
            '@click'() {
                if (!stayOnClick) {
                    hideTooltip();
                    return;
                }
                (this as any).$nextTick(() => {
                    updateContent();
                });
            },
            '@focus'() {
                if (focusAction) {
                    showTooltip(element, expression);
                }
            },
            '@blur'() {
                if (focusAction) {
                    hideTooltip();
                }
            },
        });
    });

    // Directive: x-tooltips
    Alpine.directive('tooltips', (element, { expression, modifiers }) => {
        const modifier = modifiers.length ? `.${modifiers.join('.')}` : '';
        Alpine.bind(element, {
            'x-init'() {
                (this as any).$nextTick(() => {
                    const elements = [...element.querySelectorAll(`:where([${attributes.join('],[')}])`)];
                    elements.forEach((element) => {
                        if (!tooltipIsSet(element)) {
                            element.setAttribute(xTooltipAttribute + modifier, expression);
                        }
                    });
                });
            },
        });
    });
}

const possibleXTooltipAttributes = [
    xTooltipAttribute,
    `${xTooltipAttribute}.${stayModifier}`,
    `${xTooltipAttribute}.${focusModifier}`,
    `${xTooltipAttribute}.${stayModifier}.${focusModifier}`,
    `${xTooltipAttribute}.${focusModifier}.${stayModifier}`,
];

function tooltipIsSet(element: Element) {
    return possibleXTooltipAttributes.some((attribute) => !!element.hasAttribute(attribute));
}

function getElements(id = 'tooltip') {
    const floatingEl: HTMLElement | null = document.querySelector(`#${id}`);
    const arrowElement: HTMLElement | null = document.querySelector(`#${id}-arrow`);
    const tooltipContent: HTMLElement | null = document.querySelector(`#${id}-content`);

    if (floatingEl) {
        floatingEl.style.maxWidth = `calc(100vw - ${padding * 2}px)`;
    }
    return { floatingEl, arrowElement, tooltipContent };
}

function roundByDPR(value: number) {
    const dpr = window.devicePixelRatio || 1;
    return Math.round(value * dpr) / dpr;
}
