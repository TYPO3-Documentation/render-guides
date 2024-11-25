import debounce from 'lodash.debounce';
import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import SuggestRow from './SuggestRow';
import api from './api.json';

const SearchModal = ({ isOpen, onClose }) => {
    const [searchQuery, setSearchQuery] = useState('');
    const [scope, setScope] = useState(null);
    const [fileSuggestions, setFileSuggestions] = useState([]);
    const [scopeSuggestions, setScopeSuggestions] = useState([]);
    const [querySuggestions, setQuerySuggestions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const suggestionsRef = useRef([]);

    const decomposedScope = useMemo(() => {
        if (!scope) return [];
        const decomposed = [];
        if (scope.type === 'package') {
            decomposed.push({ type: scope.type, title: scope.title, tooltip: 'Search in this package' });
            decomposed.push({ type: 'vendor', title: scope.title.split('/')[0], tooltip: 'Search in this vendor' });
        } else {
            decomposed.push({ type: scope.type, title: `${scope.title} ${searchQuery}`, tooltip: 'Search in this scope' });
        }
        return decomposed;
    }, [scope, searchQuery]);

    const handleScopeSelect = (title, type) => {
        setScope({ type, title });
        setSearchQuery('');
        setScopeSuggestions([]);
        setActiveIndex(-1);
        document.querySelector('.search-modal__input')?.focus();
    };

    const debouncedFetch = useCallback(
        debounce(async (query) => {
            if (!query || query.length < 2) return;

            setIsLoading(true);
            try {
                const response = await fetch(`https://docs.typo3.org/search/suggest?q=${query}`, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Access-Control-Allow-Origin': '*'
                    }
                });
                const data = await response.json();
                applyData(data);
            } catch (e) {
                applyData(api);
            } finally {
                setIsLoading(false);
            }
        }, 300),
        []
    );

    const applyData = (data) => {
        const fileSuggestions = data?.results?.map(result => ({
            title: result.snippet_title,
            packageName: result.manual_package,
            href: `${result.manual_slug}/${result.relative_url}`
        }));
        const scopeSuggestions = Object.entries(data?.suggest?.suggestions).flatMap(([scope, suggestions]) => {
            const type = scope.replace('manual_', '');
            return suggestions.map(suggestion => ({
                type,
                title: suggestion,
            }));
        });
        setScopeSuggestions(scopeSuggestions);
        setFileSuggestions(fileSuggestions);
    };

    const handleSearchChange = (e) => {
        const newQuery = e.target.value;
        setSearchQuery(newQuery);
        if (newQuery !== '') {
            debouncedFetch(newQuery);
            setQuerySuggestions([{ title: newQuery, tooltip: 'Search all' }]);
        } else {
            setFileSuggestions([]);
            setScopeSuggestions([]);
            setQuerySuggestions([]);
        }
    };

    const handleKeyDown = (e) => {
        const totalItems = [...decomposedScope, ...querySuggestions, ...scopeSuggestions, ...fileSuggestions].length;

        switch (e.key) {
            case 'Backspace':
                if (searchQuery === '') {
                    setScope(null);
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                setActiveIndex(prev => (prev < totalItems - 1 ? prev + 1 : -1));
                break;

            case 'ArrowUp':
                e.preventDefault();
                setActiveIndex(prev => (prev > -1 ? prev - 1 : totalItems - 1));
                break;

            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0) {
                    suggestionsRef.current[activeIndex]?.click();
                } else {
                    window.location.href = `/search/search?q=${encodeURIComponent(searchQuery)}`;
                }
                break;
        }
    };

    useEffect(() => {
        if (activeIndex >= 0) {
            suggestionsRef.current[activeIndex]?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'nearest'
            });
        }
    }, [activeIndex]);

    useEffect(() => {
        console.log("SUGG", fileSuggestions);
    }, [fileSuggestions]);

    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };

        if (isOpen) {
            document.addEventListener('keydown', handleEscape);
            document.body.style.overflow = 'hidden';
        }

        return () => {
            document.removeEventListener('keydown', handleEscape);
            document.body.style.overflow = 'unset';
        };
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div className="search-modal">
            <div className="search-modal__overlay" onClick={onClose}></div>
            <div className="search-modal__content">
                <div className="search-modal__header">
                    <div className="search-modal__input-wrapper"
                        onClick={() => {
                            setActiveIndex(-1);
                        }}>
                        <i className="fa fa-search search-modal__icon"></i>
                        {scope && (
                            <span className="search-modal__scope">
                                {scope.type}:<p className="search-modal__scope-title">{scope.title}</p>
                            </span>
                        )}
                        <input
                            autoComplete='off'
                            name='q'
                            autoFocus
                            type="text"
                            className="search-modal__input"
                            placeholder={scope ? 'Search in this scope...' : 'Search documentation...'}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            onKeyDown={handleKeyDown}
                        />
                        {(searchQuery || scope) && (
                            <button
                                className="search-modal__clear"
                                onClick={() => {
                                    setSearchQuery('');
                                    setFileSuggestions([]);
                                    setQuerySuggestions([]);
                                    setScopeSuggestions([]);
                                    setScope(null);
                                    setActiveIndex(-1);
                                }}
                            >
                                <i className="fa fa-circle-xmark"></i>
                            </button>
                        )}
                    </div>
                </div>

                <ul className="search-modal__body">
                    {decomposedScope?.length > 0 && <li className="search-modal__section">
                        <div className="search-modal__items" role="group" aria-label="Decomposed scope">
                            {decomposedScope.map((item, index) => (
                                <SuggestRow
                                    key={`decomposed-${index}`}
                                    scopeName={item.title}
                                    title={searchQuery}
                                    type={item.type}
                                    tooltip={item.tooltip}
                                    isActive={activeIndex === index}
                                    ref={el => suggestionsRef.current[index] = el}
                                />
                            ))}
                        </div>
                    </li>}
                    {querySuggestions?.length > 0 && <li className="search-modal__section">
                        <div className="search-modal__items" role="group" aria-label="Query suggestions">
                            {querySuggestions.map((item, index) => (
                                <SuggestRow
                                    key={`query-${index}`}
                                    title={item.title}
                                    tooltip={item.tooltip}
                                    href={`search/search?query=${searchQuery}`}
                                    isActive={activeIndex === index + decomposedScope.length}
                                    ref={el => suggestionsRef.current[index + decomposedScope.length] = el}
                                />
                            ))}
                        </div>
                    </li>}
                    {isLoading ? (
                        <div className="search-modal__loading">
                            <div className="search-modal__spinner">
                                <i className="fa fa-spinner fa-spin"></i>
                            </div>
                            <p>Searching...</p>
                        </div>
                    ) : (<>
                        {querySuggestions?.length > 0 && scopeSuggestions?.length > 0 && <li className="search-modal__divider"></li>}
                        {scopeSuggestions?.length > 0 && <li className="search-modal__section">
                            <div className="search-modal__items" role="group" aria-label="Scope suggestions">
                                {scopeSuggestions.map(({ title, type }, index) => (
                                    <SuggestRow
                                        key={`scope-${index}`}
                                        title={title}
                                        type={type}
                                        isActive={activeIndex === (index + querySuggestions.length + decomposedScope.length)}
                                        ref={el => suggestionsRef.current[index + querySuggestions.length + decomposedScope.length] = el}
                                        tooltip="Filter for this"
                                        onClick={() => handleScopeSelect(title, type)}
                                    />
                                ))}
                            </div>
                        </li>}
                        {querySuggestions?.length > 0 && fileSuggestions?.length > 0 && <li className="search-modal__divider"></li>}
                        {fileSuggestions?.length > 0 && <li className="search-modal__section">
                            <div className="search-modal__items" role="group" aria-label="File suggestions">
                                {fileSuggestions.map(({ title, packageName, href }, index) => (
                                    <SuggestRow
                                        key={`file-${index}`}
                                        title={title}
                                        packageName={packageName}
                                        isActive={activeIndex === (index + decomposedScope.length + querySuggestions.length + scopeSuggestions.length)}
                                        href={href}
                                        ref={el => suggestionsRef.current[index + decomposedScope.length + querySuggestions.length + scopeSuggestions.length] = el}
                                        tooltip="Open this page"
                                        icon="file"
                                    />
                                ))}
                            </div>
                        </li>}
                    </>
                    )}
                </ul>
            </div>
        </div>
    );
};

export default SearchModal;