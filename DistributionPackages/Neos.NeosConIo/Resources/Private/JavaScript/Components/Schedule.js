import {component} from '@reduct/component';
import propTypes from '@reduct/nitpick';

@component({
    daySelector: propTypes.string.isRequired,
    roomSelector: propTypes.string.isRequired,
    talkSelector: propTypes.string.isRequired
})
export default class Schedule {
    constructor() {
        this.talks = this.findAll(this.props.talkSelector);
        this.days = this.findAll(this.props.daySelector);
        this.rooms = this.findAll(this.props.roomSelector);

        this.activeDay = this.days[0];
        this.activeRoom = this.rooms[0];

        this.filterTalks();
        this.registerDaysClickEvent();
        this.registerRoomsClickEvent();
        this.registerTalkClickEvent();
        this.resetActiveDaysClass();
        this.resetActiveRoomClass();
    }

    registerDaysClickEvent() {
        this.days.forEach(day => {
            day.addEventListener('click', () => {
                this.activeDay = day;
                this.resetActiveDaysClass();
                this.closeAllTalks();
                this.filterTalks();
            });
        });
    }

    registerRoomsClickEvent() {
        this.rooms.forEach(room => {
            room.addEventListener('click', () => {
                this.activeRoom = room;
                this.resetActiveRoomClass();
                this.closeAllTalks();
                this.filterTalks();
            });
        });
    }

    registerTalkClickEvent() {
        this.talks.forEach(talk => {
            const header = talk.querySelector('.talk__header');
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

    filterTalks() {
        this.talks.forEach(talk => {
            talk.classList.remove('visible');
            if (
                talk.dataset.date === this.activeDay.dataset.date &&
                talk.dataset.roomId === this.activeRoom.dataset.roomId
            ) {
                talk.classList.add('visible');
            }
        })
    }

    resetActiveRoomClass() {
        this.rooms.forEach(room => {
            room.classList.remove('active');
            if (room === this.activeRoom) {
                room.classList.add('active');
            }
        });
    }

    closeAllTalks() {
        const descriptions = this.findAll('.talk__description');
        descriptions.forEach(description => {
            description.classList.remove('visible');
        });
    }

    resetActiveDaysClass() {
        this.days.forEach(day => {
            day.classList.remove('active');
            if (day === this.activeDay) {
                day.classList.add('active');
            }
        });
    }

    getDefaultProps() {
        return {
            daySelector: '.schedule .days__day',
            roomSelector: '.schedule .rooms__room',
            talkSelector: '.schedule .talk'
        };
    }
}