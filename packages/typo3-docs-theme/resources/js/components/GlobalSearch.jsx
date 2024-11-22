import React, { useState, useCallback } from 'react';
import debounce from 'lodash.debounce';
import Dropdown from './Dropdown';

const GlobalSearch = () => {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchScope, setSearchScope] = useState('');

    const categories = [
        {
            title: 'Core Documentation',
            items: [
                { value: 'core', label: 'TYPO3 Core' },
                { value: 'extensions', label: 'System Extensions' }
            ]
        },
        {
            title: 'User Documentation',
            items: [
                { value: 'guides', label: 'Guides' },
                { value: 'tutorials', label: 'Tutorials' },
                { value: '', label: 'All Documentation' }
            ]
        }
    ];

    // Create debounced search function
    const debouncedSearch = useCallback(
        debounce((query, scope) => {
            const searchParams = new URLSearchParams();
            if (query) searchParams.set('q', query);
            if (scope) searchParams.set('scope', scope);
            
            window.location.href = `https://docs.typo3.org/search/search?${searchParams.toString()}`;
        }, 300),
        []
    );

    const handleSearchChange = (e) => {
        const newQuery = e.target.value;
        setSearchQuery(newQuery);
        if (newQuery.length >= 2) {
            debouncedSearch(newQuery, searchScope);
        }
    };

    const handleScopeChange = (e) => {
        const newScope = e.target.value;
        setSearchScope(newScope);
        if (searchQuery.length >= 2) {
            debouncedSearch(searchQuery, newScope);
        }
    };

    return (
        <div className="search-container">
            <div className="sr-only">
                <label htmlFor="globalsearchinput">TYPO3 documentation...</label>
            </div>
            <div className="input-group mb-3 mt-sm-3">
                <Dropdown 
                    isActive={true}
                    categories={categories}
                    selectedValue={searchScope}
                    onSelect={value => handleScopeChange({ target: { value } })}
                    placeholder="Search all"
                />
                <input 
                    autoComplete="off" 
                    className="form-control shadow-none" 
                    id="globalsearchinput"
                    placeholder="TYPO3 documentation..." 
                    type="text"
                    value={searchQuery}
                    onChange={handleSearchChange}
                />
                <button 
                    className="btn btn-light search__submit" 
                    type="button"
                    onClick={() => debouncedSearch(searchQuery, searchScope)}
                >
                    <i className="fa fa-search"></i>
                    &nbsp;
                    <span className="d-none d-md-inline">Search</span>
                </button>
            </div>
        </div>
    );
};

export default GlobalSearch;