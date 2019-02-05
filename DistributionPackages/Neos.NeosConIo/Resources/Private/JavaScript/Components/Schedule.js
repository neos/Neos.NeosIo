import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

@component({
    talkSelector: propTypes.string.isRequired
})
export default class Schedule {
    constructor() {
        this.talks = this.findAll(this.props.talkSelector);
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
        const descriptions = this.findAll('.talk__description');
        descriptions.forEach(description => {
            description.classList.remove('visible');
        });
    }

    getDefaultProps() {
        return {
            talkSelector: '.schedule .talk'
        };
    }
}