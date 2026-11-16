import { Alpine as AlpineType, ElementWithXAttributes } from 'alpinejs';

export default function (Alpine: AlpineType) {
    Alpine.directive('counter', (el: ElementWithXAttributes, { expression, modifiers }, { evaluate }) => {
        expression = evaluate(expression);
        let duration = 0;
        const modifierValue = modifiers[1];
        if (modifiers[0] == 'duration' && modifierValue) {
            if (modifierValue.endsWith('ms')) {
                duration = parseInt(modifierValue);
            } else if (modifierValue.endsWith('s')) {
                duration = parseInt(modifierValue) * 1000;
            }
        }

        Alpine.bind(el, {
            'x-data'() {
                return {
                    current: 0,
                    countTo: 0,
                };
            },
            'x-init'() {
                if (typeof expression !== 'number') {
                    return;
                }
                (this as any).countTo = expression;
                (this as any).useEase = expression > 10;

                // How long you want the animation to take, in ms
                if (!duration) {
                    duration = (this as any).useEase ? 2000 : 1000;
                }
                (this as any).totalFrames = Math.round(duration / frameDuration);
            },

            'x-intersect.full.once'() {
                let frame = 0;
                // Start the animation running 60 times per second
                const counter = rafInterval(() => {
                    frame++;
                    // Calculate our progress as a value between 0 and 1
                    // Pass that value to our easing function to get our
                    // progress on a curve
                    const progressValue = frame / (this as any).totalFrames;

                    const progress = (this as any).useEase ? ease(progressValue) : progressValue;
                    // Use the progress value to calculate the current count
                    const currentCount = Math.round((this as any).countTo * progress);

                    // If the current count has changed, update the element
                    if ((this as any).current !== currentCount) {
                        (this as any).current = currentCount;
                    }

                    // If we’ve reached our last frame, stop the animation
                    if (frame === (this as any).totalFrames) {
                        counter.clear();
                    }
                }, frameDuration);
            },
            'x-text'() {
                return formatThousands((this as any).current);
            },
        });
    });
}

// Calculate how long each 'frame' should last if we want to update the animation 60 times per second
const frameDuration = 1000 / 60;

const dateNow = Date.now;
const raf = window.requestAnimationFrame;

function rafInterval(callback: Function, delay: number) {
    let start = dateNow();
    let stop = false;
    const intervalFunc = () => {
        dateNow() - start < delay || ((start += delay), callback());
        stop || raf(intervalFunc);
    };
    raf(intervalFunc);
    return {
        clear: () => (stop = true),
    };
}

function formatThousands(number: number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "'");
}

// An ease-out function that slows the count as it progresses
// https://easings.net/de#easeOutExpo
function ease(x: number) {
    return x === 1 ? 1 : 1 - Math.pow(2, -10 * x);
}
