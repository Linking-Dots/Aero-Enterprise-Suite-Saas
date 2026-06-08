export type LocalizedStrings = {
    [lang: string]: {
        [key: string]: string;
    };
};
/**
 * Stores a mapping of localized strings. Can be used to find the
 * closest available string for a given locale.
 */
export declare class MessageDictionary {
    private messages;
    private defaultLocale;
    constructor(messages: LocalizedStrings, defaultLocale?: string);
    getStringForLocale(key: string, locale: string): string;
}
