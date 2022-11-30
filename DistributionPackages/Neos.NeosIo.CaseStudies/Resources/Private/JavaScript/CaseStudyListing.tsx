import {h} from 'preact';
import * as React from "preact/compat";
import {useContext, useEffect, useMemo, useState} from "preact/hooks";
import CasesData from "./Context/CasesData";
import TranslationData from "./Context/TranslationData";
import CaseStudyTableRow from "./Components/CaseStudyTableRow";
import CaseStudyGridItem from "./Components/CaseStudyGridItem";
import {SortDirection, sortObjects} from "./Helper/Sorter";
import LazyLoad from "vanilla-lazyload";
import getProjectVolume, {PROJECT_VOLUME_MAP} from "./Helper/ProjectVolume";

export default function CaseStudyListing() {
    const casesData: CaseStudy[] = useContext(CasesData);
    const translationData: string[] = useContext(TranslationData);
    const lazyLoad = new LazyLoad({});

    // Filter entries
    const industries: string[] = useMemo(() => casesData.reduce((carry: string[], caseStudy: CaseStudy) => {
        carry.push(caseStudy.projectType);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i).sort(), [casesData]);
    const projectVolumes: number[] = useMemo(() => casesData.reduce((carry: number[], caseStudy: CaseStudy) => {
        carry.push(caseStudy.projectVolume);
        return carry;
    }, []).sort((a, b) => a-b).filter((v, i, a) => !i || v != a[i -1]), [casesData]);

    // State hooks
    const [searchWord, setSearchWord] = useState('');
    const [industryFilter, setIndustryFilter] = useState('');
    const [projectVolumeFilter, setProjectVolumeFilter] = useState(0);
    const [sorting, setSorting] = useState('featured');
    const [sortingDirection, setSortingDirection] = useState(SortDirection.Desc);
    const [caseStudies, setCaseStudies] = useState(casesData);
    const [grid, setGrid] = useState(true);

    // Callbacks
    const search = (word: string) => setSearchWord(word.toLowerCase());
    const filterByIndustry = (industry: string) => setIndustryFilter(industry);
    const filterByProjectVolume = (projectVolume: number) => setProjectVolumeFilter(projectVolume);
    const sortBy = (property: string) => {
        if (property !== sorting) {
            setSortingDirection(SortDirection.Asc);
            setSorting(property);
        } else {
            setSortingDirection(sortingDirection === SortDirection.Asc ? SortDirection.Desc : SortDirection.Asc);
        }
    };

    const switchToGrid = (state: boolean) => {setGrid(state)};

    useEffect(() => {
        let filteredCases = casesData.filter(caseStudy => {
            return (!searchWord || caseStudy.searchText.includes(searchWord))
                && (!industryFilter || caseStudy.projectType == industryFilter)
                && (!projectVolumeFilter || caseStudy.projectVolume == projectVolumeFilter);
        });
        if (sorting) {
            filteredCases = sortObjects(
                filteredCases,
                sorting,
                sortingDirection,
                sorting === 'projectVolume' ? PROJECT_VOLUME_MAP : null
            );
        }
        setCaseStudies(filteredCases);
        lazyLoad.update();
    }, [searchWord, industryFilter, projectVolumeFilter, sorting, sortingDirection, lazyLoad]);

    return (
        <div>
            <div>
                <header class="cases__grid-tableview">
                    <div class="cases__grid-row remove-border">
                        <div class="cases__header cases__grid-cell">
                            <strong>{translationData['numberOfEntriesShown']}:&nbsp;{caseStudies.length}</strong>
                        </div>
                        <div class="cases__header cases__grid-cell">
                        </div>
                        <div class="cases__header cases__grid-cell">
                        </div>
                        <div
                            class="cases__header cases__grid-cell cases__header--sortable pull-right hide-md-down"
                            onClick={() => sortBy('launchDate')}>
                            {translationData['sortBy']['launchDate']}&nbsp;<i
                            className={'fas ' + (sorting == 'launchDate' ? (sortingDirection == SortDirection.Asc ? 'fa-sort-down ' : ' fa-sort-up') : 'fa-sort')}/>
                        </div>
                    </div>
                    <div class="cases__grid-row form form--inline">
                        <div class="cases__grid-cell hide-md-down">
                            <div className="form__item">
                                <i className={'grid-switcher fas fa-th-large' + (grid ? ' selected' : '')}
                                   onClick={() => switchToGrid(true)}
                                   title={translationData['gridView']}></i>
                            </div>
                            <div className="form__item">
                                <i className={'grid-switcher fas fa-th-list' + (grid ? '' : ' selected')}
                                   onClick={() => switchToGrid(false)}
                                   title={translationData['tableView']}></i>
                            </div>
                        </div>
                        <div class="cases__grid-cell">
                            <div className="form__item">
                                <input type="text"
                                       id="cases-search"
                                       placeholder={translationData['search']}
                                       class="textInput cases-search"
                                       onKeyUp={e => search(e.target['value'])}/>&nbsp;
                                <label for="cases-search"><i class="fas fa-search"/></label>
                            </div>
                        </div>
                        <div class="cases__grid-cell hide-md-down">
                            <div className="form__item">
                                <select id="filter-industries"
                                        class="textInput"
                                        onChange={e => filterByIndustry(e.target['value'])}>
                                    <option value="">{translationData['chooseIndustry']}</option>
                                    {industries.map(industry => <option key={industry} value={industry}>{industry}</option>)}
                                </select>
                            </div>
                        </div>
                        <div class="cases__grid-cell hide-md-down">
                            <select id="filter-volume"
                                    class="textInput"
                                    onChange={e => filterByProjectVolume(e.target['value'])}>
                                <option value="">{translationData['chooseProjectVolume']}</option>
                                {projectVolumes.filter((projectVolume) => projectVolume != 1).map(projectVolume => <option key={projectVolume} value={projectVolume}>{getProjectVolume(projectVolume)}</option>)}
                            </select>
                        </div>
                    </div>
                </header>
                <section className={grid ? 'cases__grid-gridview' : 'cases__grid-tableview'}>
                    {caseStudies.length ? caseStudies.map(caseStudy => (grid ? <CaseStudyGridItem caseStudy={caseStudy} /> : <CaseStudyTableRow caseStudy={caseStudy} />)) : (
                        <div className="cases__grid-row">
                            {translationData['noCasesFound']}
                        </div>
                    )}
                </section>
            </div>
        </div>
    )
}

