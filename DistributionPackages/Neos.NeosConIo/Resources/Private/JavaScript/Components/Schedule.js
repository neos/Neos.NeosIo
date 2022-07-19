import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

class Schedule extends BaseComponent {

    constructor(el) {
        super(el);
        this.talks = this.el.querySelectorAll(this.talkSelector);
        this.registerTalkClickEvent();
    }

    registerTalkClickEvent() {
        this.talks.forEach(talk => {
            const header = talk.querySelector('.talk__header');
            if (!header) {
                return;
            }
            const description = header.nextSibling;
            header.addEventListener('click', () => {
                if (description.classList.contains('visible')) {
                    description.classList.remove('visible');
                } else {
                    this.closeAllTalks();
                    description.classList.add('visible');
                }
            });
        });
    }

    closeAllTalks() {
        const descriptions = this.el.querySelectorAll('.talk__description');
        descriptions.forEach(description => {
            description.classList.remove('visible');
        });
    }
}

Schedule.prototype.props = {
    talkSelector: '.schedule .talk',
}

export default Schedule;
