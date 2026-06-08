import { ReactNode } from 'react';
export declare function useSlotProps<T>(props: T & {
    id?: string;
}, defaultSlot?: string): T;
export declare function cssModuleToSlots(cssModule: {
    [cssmodule: string]: string;
}): {
    [slot: string]: {
        UNSAFE_className: string;
    };
};
export declare function SlotProvider(props: {
    slots?: {
        [slot: string]: object;
    };
    children?: ReactNode;
}): ReactNode;
export declare function ClearSlots(props: {
    children?: ReactNode;
}): ReactNode;
