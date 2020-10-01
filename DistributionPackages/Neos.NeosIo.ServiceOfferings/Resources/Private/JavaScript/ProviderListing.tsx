import {h} from 'preact';
import * as React from "preact/compat";
import {useContext, useEffect, useMemo, useState} from "preact/hooks";
import ProviderData from "./Context/ProviderData";
import ProviderListEntry from "./Components/ProviderListEntry";
import {SortDirection, sortObjects} from "./Helper/Sorter";

const sizeValueMap = {
    '': 99,
    '1': 1,
    '2-10': 2,
    '11-50': 3,
    '51-100': 4,
    '100+': 5
};

export default function ProviderListing() {
    const providerData: Provider[] = useContext(ProviderData);

    // Filter entries
    const countries: string[] = useMemo(() => providerData.reduce((carry: string[], provider: Provider) => {
        carry.push(provider.country);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i), [providerData]);
    const sizes: string[] = useMemo(() => providerData.reduce((carry: string[], provider: Provider) => {
        carry.push(provider.size);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i), [providerData]);

    // State hooks
    const [searchWord, setSearchWord] = useState('');
    const [countryFilter, setCountryFilter] = useState('');
    const [sizeFilter, setSizeFilter] = useState('');
    const [sorting, setSorting] = useState('');
    const [sortingDirection, setSortingDirection] = useState(SortDirection.Asc);
    const [providers, setProviders] = useState(providerData);
    const [grid, setGrid] = useState(false);

    // Callbacks
    const search = (word: string) => setSearchWord(word.toLowerCase());
    const filterByCountry = (country: string) => setCountryFilter(country);
    const filterBySize = (size: string) => setSizeFilter(size);
    const sortBy = (property: string) => {
        if (property !== sorting) {
            setSortingDirection(SortDirection.Asc);
            setSorting(property);
        } else {
            setSortingDirection(sortingDirection === SortDirection.Asc ? SortDirection.Desc : SortDirection.Asc);
        }
    };

    const switchToGrid = (state: boolean) => { setGrid(state)};

    useEffect(() => {
        let filteredProviders = providerData.filter(provider => {
            return (!searchWord || provider.searchText.includes(searchWord))
                && (!countryFilter || provider.country == countryFilter)
                && (!sizeFilter || provider.size == sizeFilter);
        });
        if (sorting) {
            filteredProviders = sortObjects(
                filteredProviders,
                sorting,
                sortingDirection,
                sorting === 'size' ? sizeValueMap : null
            );
        }
        setProviders(filteredProviders);
    }, [searchWord, countryFilter, sizeFilter, sorting, sortingDirection]);

    return (
        <div>
            <div>
                <header class="service-providers__grid-tableview">
                    <div class="service-providers__grid-row remove-border">
                        <div class="service-providers__grid-cell">
                        </div>
                        <div class="service-providers__header service-providers__grid-cell">
                            <span className="service-providers__header--sortable" onClick={() => sortBy('title')}>Name&nbsp;<i className={'fas ' + (sorting == 'title' ? (sortingDirection == SortDirection.Asc ? 'fa-sort-down ' : ' fa-sort-up') : 'fa-sort') } /></span>
                        </div>
                        <div class="service-providers__header service-providers__grid-cell ">
                            <span className="service-providers__header--sortable" onClick={() => sortBy('city')}>Location&nbsp;<i className={'fas ' + (sorting == 'city' ? (sortingDirection == SortDirection.Asc ? 'fa-sort-down ' : ' fa-sort-up') : 'fa-sort') } /></span>
                        </div>
                        <div class="service-providers__header service-providers__grid-cell service-providers__header--sortable">
                            <span className="service-providers__header--sortable" onClick={() => sortBy('size')}>Size&nbsp;<i className={'fas ' + (sorting == 'size' ? (sortingDirection == SortDirection.Asc ? 'fa-sort-down ' : ' fa-sort-up') : 'fa-sort') } /></span>
                        </div>
                    </div>
                    <div class="service-providers__grid-row remove-border form form--inline">
                        <div class="service-providers__grid-cell">
                            <div className="form__item">
                                <i className={'grid-switcher fas fa-th-list' + (grid ? '' : ' selected')}
                                   onclick={e => switchToGrid(false)}></i>
                            </div>
                            <div className="form__item">
                                <i className={'grid-switcher fas fa-th-large' + (grid ? ' selected' : '')}
                                   onclick={e => switchToGrid(true)}></i>
                            </div>
                        </div>
                        <div class="service-providers__grid-cell">
                            <div className="form__item">
                                <input type="text"
                                       id="service-provider-search"
                                       placeholder="Search..."
                                       class="textInput"
                                       onkeyup={e => search(e.target['value'])}/>&nbsp;
                                <label for="service-provider-search"><i class="fas fa-search"/></label>
                            </div>
                        </div>
                        <div class="service-providers__grid-cell">
                            <div className="form__item">
                                <select id="redirects-filter-status-code"
                                        class="textInput"
                                        onchange={e => filterByCountry(e.target['value'])}>
                                    <option value="">All countries</option>
                                    {countries.map(country => <option key={country} value={country}>{country}</option>)}
                                </select>
                            </div>
                        </div>
                        <div class="service-providers__grid-cell">
                            <select id="redirects-filter-size"
                                    class="textInput"
                                    onchange={e => filterBySize(e.target['value'])}>
                                <option value="">Any size</option>
                                {sizes.map(size => <option key={size} value={size}>{size}</option>)}
                            </select>

                        </div>
                    </div>
                </header>
                <section className={grid ? 'service-providers__grid-gridview' : 'service-providers__grid-tableview'}>
                    {providers.length ? providers.map(provider => <ProviderListEntry provider={provider}/>) : (
                        <div>
                            <div colSpan={4}>No matching providers found</div>
                        </div>
                    )}
                </section>
            </div>
        </div>
    )
}

