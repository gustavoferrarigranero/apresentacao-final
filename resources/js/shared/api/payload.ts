/** Form values are validated by Laravel; only contract fields cross the API boundary. */
export type FormValues = Record<string, unknown>;
export function payloadFields(
    values: FormValues,
    fields: readonly string[],
): FormValues {
    return Object.fromEntries(
        fields
            .filter((key) => values[key] !== undefined)
            .map((key) => [key, values[key]]),
    );
}
