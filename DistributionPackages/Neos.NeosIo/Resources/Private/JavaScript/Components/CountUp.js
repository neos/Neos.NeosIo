import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

class CountUpComponent extends BaseComponent {

    constructor(el) {
        super(el);
        const onVisible = () => setTimeout(this.animate, 400);

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    onVisible();
                    observer.unobserve(el);
                }
            });
        });

        observer.observe(el);
    }

    animate = () => {
        let { el, to } = this;
        to = parseInt(to, 10);
        let delay = 2;
        let count = to - 62 > 0 ? to - 62 : 0;

        function ease() {
            count += 1;

            if (count > to - 40) {
                delay += 5;
            } else {
                delay += 0.3;
            }

            if (count < to) {
                el.innerHTML = count;
                setTimeout(ease, delay);
            } else {
                el.innerHTML = to;
            }
        }

        ease();
    }
}

CountUpComponent.prototype.props = {
    to: 0,
}

export default CountUpComponent;
