/** Preserves backend filters (for example restaurant id) when adding pagination. */
export function listUrl(
    endpoint: string,
    page: number,
    perPage: number,
    search: string,
) {
    const [path, query = ''] = endpoint.split('?');
    const params = new URLSearchParams(query);
    params.set('page', String(page));
    params.set('per_page', String(perPage));
    if (search) params.set('name', search);
    else params.delete('name');
    return `${path}?${params}`;
}
