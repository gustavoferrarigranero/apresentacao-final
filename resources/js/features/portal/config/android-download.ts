export interface AndroidDownloadLink {
    href: string;
    kind: 'apk' | 'store';
    local: boolean;
}
/** Only publish an actual APK or Play Store listing, never an arbitrary executable URL. */
export function androidDownloadLink(
    value: string | undefined,
): AndroidDownloadLink | null {
    const input = value?.trim();
    if (!input) return null;
    try {
        const base = 'https://deliveryhub.invalid';
        const url = new URL(input, base);
        const local =
            input.startsWith('/') &&
            !input.startsWith('//') &&
            url.origin === base;
        if (
            (!local && url.protocol !== 'https:') ||
            url.username ||
            url.password ||
            url.hash
        )
            return null;
        if (!local && !input.startsWith('https://')) return null;
        if (/\.apk$/i.test(url.pathname))
            return {
                href: local ? url.pathname + url.search : url.href,
                kind: 'apk',
                local,
            };
        if (
            url.hostname === 'play.google.com' &&
            url.pathname === '/store/apps/details' &&
            url.searchParams.get('id')
        )
            return { href: url.href, kind: 'store', local: false };
        return null;
    } catch {
        return null;
    }
}
