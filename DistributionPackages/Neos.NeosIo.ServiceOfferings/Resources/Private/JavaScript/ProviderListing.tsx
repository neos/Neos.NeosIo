import {h} from 'preact';
import * as React from "preact/compat";
import {useContext, useState, useEffect, useMemo} from "preact/hooks";
import ProviderData from "./Context/ProviderData";
import ProviderListEntry from "./Components/ProviderListEntry";

export default function ProviderListing() {
    const providerData: Provider[] = useContext(ProviderData);

    const countries: string[] = useMemo(() => providerData.reduce((carry: string[], provider: Provider) => {
        carry.push(provider.country);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i), [providerData]);
    const sizes: string[] = useMemo(() => providerData.reduce((carry: string[], provider: Provider) => {
        carry.push(provider.size);
        return carry;
    }, []).filter((v, i, a) => v && a.indexOf(v) === i), [providerData]);

    const [searchWord, setSearchWord] = useState('');
    const [countryFilter, setCountryFilter] = useState('');
    const [sizeFilter, setSizeFilter] = useState('');
    const [providers, setProviders] = useState(providerData);

    const search = (value: string) => setSearchWord(value.toLowerCase());
    const filterByCountry = (value: string) => setCountryFilter(value);
    const filterBySize = (value: string) => setSizeFilter(value);

    useEffect(() => {
        const filteredProviders = providerData.filter(provider => {
            return (!searchWord || provider.title.toLowerCase().includes(searchWord))
                && (!countryFilter || provider.country == countryFilter)
                && (!sizeFilter || provider.size == sizeFilter);
        });
        setProviders(filteredProviders);
    }, [searchWord, countryFilter, sizeFilter]);

    return (
        <div>
            <div class="form form--inline">
                <div className="form__item">
                    <label for="service-provider-search"><i class="fa fa-search"></i></label>
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
                        <th>Name</th>
                        <th>Location</th>
                        <th>Services</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    {providers.map(provider => <ProviderListEntry provider={provider}/>)}
                </tbody>
            </table>
        </div>
    )
}

