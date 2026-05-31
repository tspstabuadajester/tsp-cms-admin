export function scalarFieldErrorKeys(sectionIndex: number, fieldIndex: number): string[] {
    return [`sections.${sectionIndex}.fields.${fieldIndex}.path`, `sections.${sectionIndex}.fields.${fieldIndex}.value`];
}

export function arrayFieldErrorKey(
    sectionIndex: number,
    arrayIndex: number,
    itemIndex: number,
    fieldIndex: number,
): string {
    return `sections.${sectionIndex}.arrays.${arrayIndex}.items.${itemIndex}.fields.${fieldIndex}.value`;
}

export function fieldErrorMessage(errors: Record<string, string>, keys: string[]): string | undefined {
    for (const key of keys) {
        if (errors[key]) {
            return errors[key];
        }
    }

    return undefined;
}

export function collectSaveErrorMessages(errors: Record<string, string>): string[] {
    return [...new Set(Object.values(errors).filter((message) => message !== ''))];
}

export function hasSaveErrors(errors: Record<string, string>): boolean {
    return Object.keys(errors).length > 0;
}
