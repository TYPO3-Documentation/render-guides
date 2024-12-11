import { useState, useEffect } from 'react';

export const useSearchScopes = () => {
    const [scopes, setScopes] = useState([]);
    const [currentScope, setCurrentScope] = useState(null);

    useEffect(() => {
        const select = document.getElementById('searchscope');

        const scope = (event) => {
            const slug = event.target.value.split('/').filter(Boolean).join('/');
            setCurrentScope(slug);
        };

        select?.addEventListener('change', scope);
        setCurrentScope(select?.value);

        return () => {
            select?.removeEventListener('change', scope);
        };
    }, []);

    useEffect(() => {
        const initialScopes = [];

        if (currentScope) {
            const packageName = currentScope.split('/').slice(1, 3).join('/');
            const version = currentScope.split('/').slice(3, 4)[0]?.split('.')[0];
            initialScopes.push({ type: 'manual', title: packageName, slug: currentScope });
            initialScopes.push({ type: 'version', title: version });
        } else {
            const url = new URL(window.location.href);
            url.searchParams?.forEach((value, key) => {
                if (key === 'scope') {
                    const slug = decodeURIComponent(value).split('/').filter(Boolean).join('/');
                    const packageName = slug.split('/').slice(1, 3).join('/');
                    initialScopes.push({ type: 'manual', title: packageName, slug });
                } else if (key.startsWith('filters[')) {
                    const filterExp = new RegExp(/filters\[(.*?)\]\[(.*?)\]/);
                    const [, type, filterValue] = key.match(filterExp);
                    initialScopes.push({
                        type: type === 'optionsaggs' ? 'option' : type,
                        title: filterValue
                    });
                }
            });
        }

        setScopes(initialScopes);
    }, [currentScope]);

    return [scopes, setScopes];
};
