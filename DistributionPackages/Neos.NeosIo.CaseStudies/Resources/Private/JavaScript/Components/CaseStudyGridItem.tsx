import {Fragment, h} from 'preact';
import * as React from "preact/compat";
import getProjectVolume from "../Helper/ProjectVolume";

const CaseStudyGridItem = ({caseStudy}: {caseStudy: CaseStudy}) => {
    const projectVolume = getProjectVolume(caseStudy.projectVolume);

    return (
        <a key={caseStudy.identifier} class={'imageTeaser imageTeaser--isLink' + (caseStudy.featured ? ' references__item--featured' : '')} href={caseStudy.url} target="_blank" rel="noopener">
            {caseStudy.image
                ?
                <img src={caseStudy.image} class="imageTeaser__image" loading="lazy" title={caseStudy.title} alt={caseStudy.title} />
                : ''
            }
                <div class="imageTeaser__contents u-invertText">
                    <h4 class="imageTeaser__contents__heading">{caseStudy.title}</h4>
                    <footer class="references__data">
                        {projectVolume && (
                            <Fragment>
                                <span><i class="fas fa-users"></i>&nbsp;{projectVolume}</span><br />
                            </Fragment>
                        )}
                        {caseStudy.projectType && (
                            <Fragment>
                                <span><i class="fas fa-industry"></i>&nbsp;{caseStudy.projectType}</span>
                            </Fragment>
                        )}
                    </footer>
                </div>
        </a>
    )
}

export default React.memo(CaseStudyGridItem);
