/**
 * Slug derived from a name (accents transliterated, non-alphanumeric collapsed to hyphens).
 */
export function slugFromName(name: string): string {
    const slug = name
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/ß/g, 'ss')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    return slug || `website-${crypto.randomUUID()}`;
}
