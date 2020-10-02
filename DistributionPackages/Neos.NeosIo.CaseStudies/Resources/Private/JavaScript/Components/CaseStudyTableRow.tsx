import {h} from 'preact';
import * as React from "preact/compat";

const projectVolumesValueMap = {
    1: 'n/a',
    5: '< 100 h',
    10: '100 - 499h',
    15: '500 - 999h',
    20: '1000 - 3000h',
    25: '> 3000h'
};

export default function CaseStudyTableRow({caseStudy}: {caseStudy: CaseStudy}) {
    return (
        <div key={caseStudy.identifier} className={'cases__grid-row' + (caseStudy.featured ? ' references__item--featured' : '')}>
            <div className="cases__grid-cell">
                <p>
                    <a href={caseStudy.url} title={caseStudy.title} target="_blank" rel="noopener">
                        {caseStudy.title}
                    </a>
                </p>
            </div>
            <div className="cases__grid-cell cases__overlay">
                <p>
                    {caseStudy.launchDateFormatted ? <i class="fas fa-rocket"></i> : ''} {caseStudy.launchDateFormatted}
                </p>
            </div>
            <div className="cases__grid-cell cases__overlay">
                <p>
                    <i class="fas fa-industry"></i> {caseStudy.projectType}
                </p>
            </div>
            <div className="cases__grid-cell cases__overlay">
                <p>
                    <i class="fas fa-users"></i> {projectVolumesValueMap[caseStudy.projectVolume]}
                </p>
            </div>
        </div>

    )
}
