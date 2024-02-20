import Alpine from 'alpinejs';

// function that returns a random number
function getRandomNumber(min, max, substract = 0) {
    return Math.round(min + (Math.random() * (max - min)) - substract);
}

// get the size of an element
function getSize(element) {
    return { x: element.clientWidth, y: element.clientHeight };
}

Alpine.data('collage', () => ({
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
        this.rendered.push(element);
        element.classList.remove('opacity-0');
    },
    isOverlap(x, y, size, type) {
        // return true if overlapping another element of the same type
        for (const position of this.positions.filter( p => p.type === type )) {
            if (
                (x + size.x + this.objectMargin) > position.x &&
                (x - this.objectMargin) < (position.x + position.size.x) &&
                (y + size.y + this.objectMargin) > position.y &&
                (y - this.objectMargin) < (position.y + position.size.y)
            ) {
                return true;
            }
        }
        // console.log({ x, y });
        // console.log(this.positions);
        return false;
    },
    processElements() {
        this.maxX = this.$el.clientWidth;
        this.maxY = this.$el.clientHeight;
        this.positions = [];
        this.rendered = [];
        this.elements.forEach((element) => {
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
        this.elements = [...this.$el.children];
        this.processElements();
    },
}));
