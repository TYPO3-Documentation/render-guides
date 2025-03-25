import { useEffect, useState } from 'react';

export const useSearchScopes = () => {
    const [scopes, setScopes] = useState([]);

    useEffect(() => {
        const initialScopes = [];
        const url = new URL(window.location.href);
        url.searchParams?.forEach((value, key) => {
            if (key === 'scope' && value) {
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

        setScopes(initialScopes);
    }, []);

    return [scopes, setScopes];
};
