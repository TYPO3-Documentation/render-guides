import { useState, useCallback } from 'react';
import debounce from 'lodash.debounce';
import { PROXY_URL } from '../search';

export const useSearchSuggestions = () => {
    const [fileSuggestions, setFileSuggestions] = useState([]);
    const [scopeSuggestions, setScopeSuggestions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);

    const buildRequestUrl = (scopes, searchQuery) => {
        const url = new URL(`${PROXY_URL}/search/suggest`);
        scopes.forEach(scope => {
            if (scope.type === 'manual') {
                url.searchParams.append(`filters[package]`, scope.title);
            } else if (scope.type === 'vendor') {
                url.searchParams.append(`filters[${scope.type}]`, scope.title);
            } else if (scope.type === 'option') {
                url.searchParams.append(`filters[optionsaggs][${scope.title}]`, true);
            } else {
                url.searchParams.append(`filters[${scope.type}][${scope.title}]`, true);
            }
        });
        url.searchParams.append('q', searchQuery);
        return url.href;
    };

    const fetchSuggestions = useCallback(async (scopes, searchQuery) => {
        if (scopes?.length === 0 && !searchQuery) {
            setFileSuggestions([]);
            setScopeSuggestions([]);
            return;
        }

        setIsLoading(true);
        try {
            const response = await fetch(buildRequestUrl(scopes, searchQuery), {
                headers: { 'Content-Type': 'application/json' },
            });
            
            if (!response.ok) throw new Error('Network response error');
            
            const data = await response.json();
            
            // Process file suggestions
            const files = data?.results?.map(result => ({
                title: result.snippet_title,
                packageName: result.manual_package,
                href: `${PROXY_URL}/${result.manual_slug}/${result.relative_url}#${result.fragment}`,
            })) || [];

            // Process scope suggestions
            const scopesSugg = Object.entries(data?.suggest?.suggestions ?? {}).flatMap(([scope, suggestions]) => {
                const type = scope.replace('manual_', '') === 'package' ? 'manual' : scope.replace('manual_', '');
                return suggestions.map(suggestion => ({
                    type,
                    title: type === 'version' ? suggestion.split('.')[0] : suggestion
                }));
            });

            setFileSuggestions(files);
            setScopeSuggestions(scopesSugg);
        } catch (error) {
            console.error(error);
            setFileSuggestions([]);
            setScopeSuggestions([]);
        } finally {
            setIsLoading(false);
        }
    }, []);

    const debouncedFetch = useCallback(debounce(fetchSuggestions, 300), []);

    return {
        fileSuggestions,
        scopeSuggestions,
        isLoading,
        fetchSuggestions: debouncedFetch
    };
}; 