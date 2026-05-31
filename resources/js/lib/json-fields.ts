export function isImageUploadFieldPath(path: string): boolean {
    const normalized = path.toLowerCase();

    if (normalized === 'logo') {
        return true;
    }

    return (
        normalized === 'image.src' ||
        normalized.endsWith('.image.src') ||
        normalized === 'thumbnail.src' ||
        normalized.endsWith('.thumbnail.src')
    );
}

export function getXsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}
