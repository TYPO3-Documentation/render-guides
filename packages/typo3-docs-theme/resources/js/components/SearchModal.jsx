import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchScopes } from '../hooks/useSearchScopes';
import { useSearchSuggestions } from '../hooks/useSearchSuggestions';
import { PROXY_URL } from '../search';
import SuggestRow from './SuggestRow';

const SearchModal = ({ isOpen, onClose }) => {
    const [searchQuery, setSearchQuery] = useState('');
    const [scopes, setScopes] = useSearchScopes();
    const [activeIndex, setActiveIndex] = useState(-1);
    const suggestionsRef = useRef([]);
    const inputRef = useRef();

    const {
        fileSuggestions,
        scopeSuggestions,
        setScopeSuggestions,
        setFileSuggestions,
        isLoading,
        fetchSuggestions
    } = useSearchSuggestions();

    const buildHref = useCallback((scopes, query) => {
        const url = new URL('/search/search', PROXY_URL);

        if (!query && scopes.length === 1 && scopes[0].type === 'manual') {
            return (new URL(`/${scopes[0].slug}/`, PROXY_URL)).href;
        }

        scopes.forEach(scope => {
            if (scope.type === 'manual') {
                url.searchParams.append('scope', (`/${scope.slug}/`));
            } else if (scope.type === 'vendor') {
                url.searchParams.append('vendor', scope.title);
            } else if (scope.type === 'option') {
                url.searchParams.append(`filters[optionaggs][${scope.title}]`, true);
            } else {
                url.searchParams.append(`filters[${scope.type}][${scope.title}]`, true);
            }
        });
        url.searchParams.append('q', query);
        return url.href;
    }, []);

    const decomposedScopes = useMemo(() => {
        const decomposed = [];
        for (let i = scopes.length;i > 0;i--) {
            const currentScopes = scopes.slice(-i);
            if (currentScopes.length === 1 && currentScopes[0].type === 'manual') {
                decomposed.push({
                    scopes: currentScopes,
                    title: searchQuery,
                    tooltip: 'Search in this manual',
                    href: buildHref(currentScopes, searchQuery)
                });
                const vendorScope = [{
                    type: 'vendor',
                    title: currentScopes[0].title.split('/')[0],
                }];
                decomposed.push({
                    scopes: vendorScope,
                    title: searchQuery,
                    tooltip: 'Search in this vendor',
                    href: buildHref(vendorScope, searchQuery)
                });
            } else {
                decomposed.push({
                    scopes: currentScopes,
                    title: searchQuery,
                    tooltip: 'Search in this scope',
                    href: buildHref(currentScopes, searchQuery)
                });
            }
        }
        if (searchQuery) {
            decomposed.push({
                scopes: [],
                title: searchQuery,
                tooltip: 'Search all',
                href: buildHref([], searchQuery)
            });
        }
        return decomposed;
    }, [scopes, searchQuery, buildHref]);

    const handleScopeSelect = useCallback((title, type, slug) => {
        setScopes(prevScopes => {
            const newScopes = [...prevScopes];
            const existingScopeIndex = newScopes.findIndex(scope => scope.type === type);
            if (existingScopeIndex !== -1) {
                newScopes[existingScopeIndex] = { type, title, slug };
            } else {
                newScopes.push({ type, title, slug });
            }
            return newScopes;
        });
        setSearchQuery('');
        setActiveIndex(-1);
        setScopeSuggestions([]);
        setFileSuggestions([]);
        inputRef.current?.focus();
    }, [setScopes]);

    const handleSearchChange = useCallback((e) => {
        const newQuery = e.target.value;
        setSearchQuery(newQuery);
        if (newQuery !== '') {
            fetchSuggestions(scopes, newQuery);
        }
    }, [scopes, fetchSuggestions]);

    const handleKeyDown = useCallback((e) => {
        const totalItems = [...decomposedScopes, ...scopeSuggestions, ...fileSuggestions].length;

        switch (e.key) {
            case 'Backspace':
                if (inputRef.current?.selectionEnd === 0) {
                    setScopes(prevScopes => prevScopes.slice(0, -1));
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
                    window.location.href = buildHref(scopes, searchQuery);
                }
                break;
        }
    }, [decomposedScopes, scopeSuggestions, fileSuggestions, scopes, searchQuery, buildHref]);

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
                        onClick={() => setActiveIndex(-1)}>
                        <i className="fa fa-search search-modal__icon"></i>
                        {scopes.map((scope, index) => (
                            <div key={`scope-${index}`} className="search-modal__scope">
                                <p className="suggest-row__scope-type">{scope.type && `${scope.type}:`}</p>
                                <p className="search-modal__scope-title">{scope.title}</p>
                            </div>
                        ))}
                        <input
                            ref={inputRef}
                            autoComplete='off'
                            name='q'
                            autoFocus
                            type="text"
                            className="search-modal__input"
                            placeholder={scopes?.length > 0 ? 'search in this scope...' : 'Search documentation...'}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            onKeyDown={handleKeyDown}
                        />
                        {(searchQuery || scopes.length > 0) && (
                            <button
                                className="search-modal__clear"
                                onClick={() => {
                                    setSearchQuery('');
                                    setScopes([]);
                                    setActiveIndex(-1);
                                    inputRef.current?.focus();
                                }}
                            >
                                <i className="fa fa-circle-xmark"></i>
                            </button>
                        )}
                    </div>
                </div>

                <ul className="search-modal__body">
                    {decomposedScopes?.length > 0 && (
                        <li className="search-modal__section">
                            <div className="search-modal__items" role="group" aria-label="Decomposed scopes">
                                {decomposedScopes.map((item, index) => (
                                    <SuggestRow
                                        key={`decomposed-${index}`}
                                        scopes={item.scopes}
                                        title={item.title}
                                        tooltip={item.tooltip}
                                        isActive={activeIndex === index}
                                        ref={el => suggestionsRef.current[index] = el}
                                        href={item.href}
                                    />
                                ))}
                            </div>
                        </li>
                    )}
                    {isLoading ? (
                        <div className="search-modal__loading">
                            <div className="search-modal__spinner">
                                <i className="fa fa-spinner fa-spin"></i>
                            </div>
                            <p>Searching...</p>
                        </div>
                    ) : (
                        <>
                            {decomposedScopes?.length > 0 && scopeSuggestions?.length > 0 && (
                                <li className="search-modal__divider"></li>
                            )}
                            {scopeSuggestions?.length > 0 && (
                                <li className="search-modal__section">
                                    <div className="search-modal__items" role="group" aria-label="Scope suggestions">
                                        {scopeSuggestions.map(({ title, type, slug }, index) => (
                                            <SuggestRow
                                                key={`scope-${index}`}
                                                title={title}
                                                type={type}
                                                isActive={activeIndex === (index + decomposedScopes.length)}
                                                ref={el => suggestionsRef.current[index + decomposedScopes.length] = el}
                                                tooltip="Filter for this"
                                                onClick={() => handleScopeSelect(title, type, slug)}
                                            />
                                        ))}
                                    </div>
                                </li>
                            )}
                            {decomposedScopes?.length > 0 && fileSuggestions?.length > 0 && (
                                <li className="search-modal__divider"></li>
                            )}
                            {fileSuggestions?.length > 0 && (
                                <li className="search-modal__section">
                                    <div className="search-modal__items" role="group" aria-label="File suggestions">
                                        {fileSuggestions.map(({ title, packageName, href }, index) => (
                                            <SuggestRow
                                                key={`file-${index}`}
                                                title={title}
                                                packageName={packageName}
                                                isActive={activeIndex === (index + decomposedScopes.length + scopeSuggestions.length)}
                                                href={href}
                                                ref={el => suggestionsRef.current[index + decomposedScopes.length + scopeSuggestions.length] = el}
                                                tooltip="Open this page"
                                                icon="file"
                                            />
                                        ))}
                                    </div>
                                </li>
                            )}
                        </>
                    )}
                </ul>
            </div>
        </div>
    );
};

export default SearchModal;
