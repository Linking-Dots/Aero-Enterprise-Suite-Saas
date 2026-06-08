import { DOMRef, DOMRefValue, FocusableElement, FocusableRef, FocusableRefValue, RefObject } from '@react-types/shared';
export declare function createDOMRef<T extends HTMLElement = HTMLElement>(ref: RefObject<T | null>): DOMRefValue<T>;
export declare function createFocusableRef<T extends HTMLElement = HTMLElement>(domRef: RefObject<T | null>, focusableRef?: RefObject<FocusableElement | null>): FocusableRefValue<T>;
export declare function useDOMRef<T extends HTMLElement = HTMLElement>(ref: DOMRef<T>): RefObject<T | null>;
export declare function useFocusableRef<T extends HTMLElement = HTMLElement>(ref: FocusableRef<T>, focusableRef?: RefObject<FocusableElement | null>): RefObject<T | null>;
export declare function unwrapDOMRef<T extends HTMLElement>(ref: RefObject<DOMRefValue<T> | null>): RefObject<T | null>;
export declare function useUnwrapDOMRef<T extends HTMLElement>(ref: RefObject<DOMRefValue<T> | null>): RefObject<T | null>;
