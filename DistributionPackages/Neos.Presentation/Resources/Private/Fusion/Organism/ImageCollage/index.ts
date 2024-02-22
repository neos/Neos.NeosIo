import Alpine from 'alpinejs';
import Atropos from 'atropos';

// function that returns a random number
function getRandomNumber(min, max, substract = 0) {
    return Math.round(min + Math.random() * (max - min) - substract);
}

// get the size of an element
function getSize(element) {
    return { x: element.clientWidth, y: element.clientHeight };
}

Alpine.data('collage', () => ({
    atropos: null,
    figure: null,
    positions: [],
    rendered: [],
    elements: [],
    maxX: 0,
    maxY: 0,
    padding: 30,
    objectMargin: 10,
    maxAttempts: 50,
    placeElement(element, size, attempts = 0) {
        if (attempts >= this.maxAttempts) {
            // console.error('Max attempts reached');
            return;
        }

        const x = getRandomNumber(this.padding, this.maxX - this.padding, size.x / 2);
        const y = getRandomNumber(this.padding, this.maxY - this.padding - size.y);

        if (this.isOverlap(x, y, size, element.tagName)) {
            attempts++;
            this.placeElement(element, size, attempts);
            return;
        }

        element.style.setProperty('left', x + 'px');
        element.style.setProperty('top', y + 'px');
        this.positions.push({ x, y, size, type: element.tagName });

        // Push another element-box to prevent objects from different types to overlap entirely
        this.positions.push({
            x: x + size.x * 0.25,
            y: y + size.y * 0.25,
            size: { x: size.x / 2, y: size.y / 2 },
            type: '*',
        });

        this.rendered.push(element);
        element.classList.remove('opacity-0');
    },
    isOverlap(x, y, size, type) {
        // return true if overlapping another element of the same type
        for (const p of this.positions.filter((p) => p.type === '*' || p.type === type)) {
            if (x - this.objectMargin > p.x + p.size.x || p.x > x + this.objectMargin + size.x) continue;
            if (y - this.objectMargin > p.y + p.size.y || p.y > y + this.objectMargin + size.y) continue;
            return true;
        }

        return false;
    },
    processElements() {
        this.maxX = this.figure.clientWidth;
        this.maxY = this.figure.clientHeight;
        this.positions = [];
        this.rendered = [];
        this.elements.forEach((element) => {
            element.classList.add('opacity-0');
            if (element.tagName !== 'IMG' || element.complete) {
                this.placeElement(element, getSize(element));
            } else {
                element.addEventListener('load', () => {
                    this.placeElement(element, getSize(element));
                });
            }
        });
    },
    init() {
        this.figure = this.$el.querySelector('figure');
        this.elements = [...(this.figure?.children ?? [])];

        // Add randomized z offset for atropos (-5 to 5) if none is defined already
        this.elements.forEach((e) => (e.dataset.atroposOffset = e.dataset.atroposOffset ?? Math.random() * 5 - 10));

        // Init atropos
        this.atropos = Atropos({
            el: this.$el,
        });

        this.processElements();
    },
}));
