import { AriaLabelingProps, CollectionBase, DOMProps, FocusableRef, Key, StyleProps } from '@react-types/shared';
import { SpectrumMenuTriggerProps } from './MenuTrigger';
import React, { ReactElement } from 'react';
export interface SpectrumActionMenuProps<T> extends CollectionBase<T>, Omit<SpectrumMenuTriggerProps, 'children'>, StyleProps, DOMProps, AriaLabelingProps {
    /** Whether the button is disabled. */
    isDisabled?: boolean;
    /**
     * Whether the button should be displayed with a [quiet
     * style](https://spectrum.adobe.com/page/action-button/#Quiet).
     */
    isQuiet?: boolean;
    /** Whether the element should receive focus on render. */
    autoFocus?: boolean;
    /** Handler that is called when an item is selected. */
    onAction?: (key: Key) => void;
}
/**
 * ActionMenu combines an ActionButton with a Menu for simple "more actions" use cases.
 */
export declare const ActionMenu: <T>(props: SpectrumActionMenuProps<T> & {
    ref?: FocusableRef<HTMLButtonElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
