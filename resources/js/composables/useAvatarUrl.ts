export function avatarUrl(filename?: string | null): string | undefined {
    if (!filename) {
        return undefined;
    }

    if (filename.startsWith('/') || filename.startsWith('http://') || filename.startsWith('https://')) {
        return filename;
    }

    return `/storage/avatars/${filename}`;
}

export function useAvatarUrl() {
    return { avatarUrl };
}
