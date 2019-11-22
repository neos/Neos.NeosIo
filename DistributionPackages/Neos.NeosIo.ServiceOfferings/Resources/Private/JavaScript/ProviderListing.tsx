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
    '100+': 4
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
    const [providers, setProviders] = useState(providerData);

    // Callbacks
    const search = (word: string) => setSearchWord(word.toLowerCase());
    const filterByCountry = (country: string) => setCountryFilter(country);
    const filterBySize = (size: string) => setSizeFilter(size);
    const sortBy = (property: string) => setSorting(property);

    useEffect(() => {
        let filteredProviders = providerData.filter(provider => {
            return (!searchWord || provider.title.toLowerCase().includes(searchWord))
                && (!countryFilter || provider.country == countryFilter)
                && (!sizeFilter || provider.size == sizeFilter);
        });
        if (sorting) {
            filteredProviders = sortObjects(
                filteredProviders,
                sorting,
                SortDirection.Asc,
                sorting === 'size' ? sizeValueMap : null
            );
        }
        setProviders(filteredProviders);
    }, [searchWord, countryFilter, sizeFilter, sorting]);

    return (
        <div>
            <div class="form form--inline">
                <div className="form__item">
                    <label for="service-provider-search"><i class="fa fa-search"/></label>
                    <input type="text"
                           id="service-provider-search"
                           placeholder="Search..."
                           class="textInput"
                           onKeyUp={e => search(e.target['value'])}/>
                </div>

                <div className="form__item">
                    <select id="redirects-filter-status-code"
                            class="textInput"
                            onChange={e => filterByCountry(e.target['value'])}>
                        <option value="">All countries</option>
                        {countries.map(country => <option key={country} value={country}>{country}</option>)}
                    </select>
                </div>

                <div className="form__item">
                    <select id="redirects-filter-size"
                            class="textInput"
                            onChange={e => filterBySize(e.target['value'])}>
                        <option value="">Any size</option>
                        {sizes.map(size => <option key={size} value={size}>{size}</option>)}
                    </select>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>
                            <span class="service-providers__header service-providers__header--sortable"
                                  onClick={() => sortBy('title')}>Name <i class="fa fa-sort"/></span>
                        </th>
                        <th>
                            <span class="service-providers__header service-providers__header--sortable"
                                  onClick={() => sortBy('country')}>Location <i class="fa fa-sort"/></span>
                        </th>
                        <th>
                            <span class="service-providers__header">Services</span>
                        </th>
                        <th>
                            <span class="service-providers__header service-providers__header--sortable"
                                  onClick={() => sortBy('size')}>Size <i class="fa fa-sort"/></span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {providers.length ? providers.map(provider => <ProviderListEntry provider={provider}/>) : (
                        <tr><td colSpan={4}>No matching providers found</td></tr>
                    )}
                </tbody>
            </table>
        </div>
    )
}

