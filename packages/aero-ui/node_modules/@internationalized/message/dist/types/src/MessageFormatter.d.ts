import { FormatXMLElementFn, PrimitiveType } from 'intl-messageformat/src/formatters';
import { MessageDictionary } from './MessageDictionary';
/**
 * Formats ICU Message strings to create localized strings from a MessageDictionary.
 */
export declare class MessageFormatter {
    private locale;
    private messages;
    private cache;
    constructor(locale: string, messages: MessageDictionary);
    format<T = void>(key: string, variables: Record<string, PrimitiveType | T | FormatXMLElementFn<T, string | T | (string | T)[]>> | undefined): string | T | (string | T)[];
}
