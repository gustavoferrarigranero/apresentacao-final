import { useEffect, useState } from 'react';
export function useView(initial = 'portal') {
    const [view, setView] = useState(
        () =>
            (typeof window !== 'undefined'
                ? window.location.hash.replace(/^#\/?/, '')
                : '') || initial,
    );
    useEffect(() => {
        const change = () =>
            setView(
                (typeof window !== 'undefined'
                    ? window.location.hash.replace(/^#\/?/, '')
                    : '') || initial,
            );
        window.addEventListener('hashchange', change);
        return () => window.removeEventListener('hashchange', change);
    }, [initial]);
    return view;
}
