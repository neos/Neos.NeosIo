import { component } from '@reduct/component';
import propTypes from '@reduct/nitpick';
import lighten from 'lightness';
import { loadJs } from '../Utilities';

const dependencies = [
    'https://www.amcharts.com/lib/3/amcharts.js',
    'https://www.amcharts.com/lib/3/serial.js',
    'https://www.amcharts.com/lib/3/themes/light.js',
    'https://www.amcharts.com/lib/3/gantt.js'
];
const themeColor = '#26224C';
const config = {
    type: 'gantt',
    theme: 'light',
    period: 'YYYY',
    dataDateFormat: 'YYYY-MM',
    columnWidth: 0.65,
    precision: 1,
    graph: {
        fillAlphas: 1,
        balloonText: '<b>[[task]]</b>: [[open]]-[[value]]'
    },
    rotate: true,
    categoryField: 'version',
    segmentsField: 'segments',
    colorField: 'color',
    startDateField: 'start',
    endDateField: 'end',
    valueScrollbar: {
        autoGridCount: true,
        color: '#555555'
    },
    chartCursor: {
        cursorColor: themeColor,
        valueBalloonsEnabled: false,
        cursorAlpha: 0,
        valueLineAlpha: 0.5,
        valueLineBalloonEnabled: true,
        valueLineEnabled: true,
        zoomable: false,
        valueZoomable: true
    },
    export: {
        enabled: true,
        divId: 'exportContainer',
        position: 'bottom-right',
        fileName: 'typo3-support-times',
        menu: ['PNG', 'PDF', 'SVG']
    }
};

@component({
    dataSelector: propTypes.string.isRequired
})
export default class AmChart {
    constructor() {
        // Hence AmCharts relies on element id's, we generate a random one.
        this.el.setAttribute('id', `amChart__${Math.random() * 1000}`);

        this.loadDependencies()
            .then(() => this.initializeGlobals())
            .then(() => this.render());
    }

    getDefaultProps() {
        return {
            dataSelector: '[data-json]'
        };
    }

    loadDependencies() {
        return new Promise(resolve => {
            dependencies.reduce((prev, cur) => {
                const isLast = cur === dependencies[dependencies.length - 1];

                return prev
                    .then(() => loadJs(cur))
                    .then(() => {
                        if (isLast) {
                            resolve();
                        }
                    });
            }, Promise.resolve());
        });
    }

    initializeGlobals() {
        // AmCharts isn't published on npm, thus we load it via their CDN and setup the global - Yeah...
        try {
            window.AmCharts.useUTC = true;
        } catch (e) {}

        return Promise.resolve();
    }

    render() {
        const dataProvider = this.parseData();
        const today = new Date();

        window.AmCharts.makeChart(
            this.el.getAttribute('id'),
            Object.assign({}, config, {
                valueAxis: {
                    type: 'date',
                    autoGridCount: false,
                    gridCount: 24,
                    guides: [
                        {
                            value: today,
                            toValue: today,
                            lineAlpha: 1,
                            lineThickness: 1,
                            inside: true,
                            labelRotation: 90,
                            label: 'Today',
                            above: true
                        }
                    ]
                },
                dataProvider
            })
        );
    }

    parseData() {
        const data = JSON.parse(this.find(this.props.dataSelector).innerHTML);

        // To reduce the places where colors are defined, especially in the data itself,
        // we automatically set colors for the chart depending on the index of the segment.
        return data.map(obj => {
            return Object.assign({}, obj, {
                segments: obj.segments.map((segment, index) => {
                    const color = lighten(themeColor, index * 10);

                    return Object.assign({}, segment, {
                        color
                    });
                })
            });
        });
    }
}
