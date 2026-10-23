export const date = (value: string) => {
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? '—'
        : parsed.toLocaleDateString('pt-PT');
};
