import {h} from 'preact';
import * as React from "preact/compat";
import getProjectVolume from "../Helper/ProjectVolume";

const CaseStudyTableRow = ({caseStudy}: {caseStudy: CaseStudy}) => {
    const projectVolume = getProjectVolume(caseStudy.projectVolume);

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
                <p title={`Published on ${caseStudy.datePublished} ${new Date(caseStudy.datePublished).toLocaleDateString()}`}>
                    {caseStudy.launchDateFormatted ? <i class="fas fa-rocket"></i> : ''} {caseStudy.launchDateFormatted}
                </p>
            </div>
            {caseStudy.projectType && (
                <div className="cases__grid-cell cases__overlay">
                    <p>
                        <i class="fas fa-industry"></i> {caseStudy.projectType}
                    </p>
                </div>
            )}
            {projectVolume && (
                <div className="cases__grid-cell cases__overlay">
                    <p>
                        <i class="fas fa-users"></i> {projectVolume}
                    </p>
                </div>
            )}
        </div>
    )
}

export default React.memo(CaseStudyTableRow);
