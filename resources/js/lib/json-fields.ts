/** Field names that are always image paths when used as the full key or final path segment. */
const IMAGE_FIELD_NAMES = ['logo', 'backgroundimage', 'background', 'img', 'featureimage'] as const;

/** Parents allowed before `.src` (e.g. `image.src`, `thumbnail.src`). */
const IMAGE_SRC_PARENT_SEGMENTS = new Set([
    'image',
    'thumbnail',
    'img',
    'background',
    'backgroundimage',
    'featureimage',
]);

const matchesFieldName = (path: string, fieldName: string): boolean => {
    const normalized = path.toLowerCase();
    const name = fieldName.toLowerCase();

    return normalized === name || normalized.endsWith(`.${name}`);
};

const pathSegments = (path: string): string[] => path.split('.').filter((segment) => segment !== '');

export function isImageUploadFieldPath(path: string): boolean {
    const normalized = path.toLowerCase();

    for (const fieldName of IMAGE_FIELD_NAMES) {
        if (matchesFieldName(path, fieldName)) {
            return true;
        }
    }

    if (normalized === 'image.src' || normalized.endsWith('.image.src')) {
        return true;
    }

    if (normalized === 'thumbnail.src' || normalized.endsWith('.thumbnail.src')) {
        return true;
    }

    const segments = pathSegments(path);

    if (segments.length >= 2) {
        const parent = segments[segments.length - 2].toLowerCase();
        const last = segments[segments.length - 1].toLowerCase();

        if (last === 'src' && IMAGE_SRC_PARENT_SEGMENTS.has(parent)) {
            return true;
        }
    }

    return false;
}

export function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}
