import lighten from 'lightness';
import amCharts from "amcharts3/amcharts/amcharts";
import amChartsSerial from "amcharts3/amcharts/serial";
import amChartsLight from "amcharts3/amcharts/themes/light";
import amChartGantt from "amcharts3/amcharts/gantt";
import BaseComponent from "DistributionPackages/Neos.NeosIo/Resources/Private/JavaScript/Components/BaseComponent";

const themeColor = '#26224C';

const config = {
    type: 'gantt',
    theme: 'light',
    pathToImages: '/_Resources/Static/Packages/Neos.NeosIo/Images/AmCharts/',
    period: 'YYYY',
    dataDateFormat: 'YYYY-MM-DD',
    columnWidth: 0.65,
    precision: 1,
    graph: {
        fillAlphas: 1,
        balloonText: '[[task]]'
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
        fileName: 'neos-support-times',
        menu: ['PNG', 'PDF', 'SVG']
    }
};

class AmChart extends BaseComponent {

    constructor(el) {
        super(el);
        // Hence AmCharts relies on element id's, we generate a random one.
        el.setAttribute('id', `amChart__${Math.random() * 1000}`);
        this.initializeGlobals().then(() => this.render());
    }

    initializeGlobals = async () => {
        // AmCharts isn't published on npm, thus we load it via their CDN and setup the global - Yeah...
        try {
            window.AmCharts.useUTC = true;
        } catch (e) {}

        return Promise.resolve();
    }

    render = () => {
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

    parseData = () => {
        const data = JSON.parse(this.el.querySelector(this.dataSelector).innerHTML);

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

AmChart.prototype.props = {
    dataSelector: '[data-json]',
}

export default AmChart;
