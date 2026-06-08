import { AriaMenuProps } from 'react-aria/useMenu';
import { DOMRef, StyleProps } from '@react-types/shared';
import React, { KeyboardEventHandler, ReactElement, ReactNode, RefObject } from 'react';
import { RootMenuTriggerState } from 'react-stately/useMenuTriggerState';
import { TreeState } from 'react-stately/useTreeState';
export interface SpectrumMenuProps<T> extends AriaMenuProps<T>, StyleProps {
}
/**
 * Menus display a list of actions or options that a user can choose.
 */
export declare const Menu: <T>(props: SpectrumMenuProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export declare function TrayHeaderWrapper(props: {
    children: ReactNode;
    isSubmenu?: boolean;
    hasOpenSubmenu?: boolean;
    parentMenuTreeState?: TreeState<any>;
    rootMenuTriggerState?: RootMenuTriggerState;
    onBackButtonPress?: () => void;
    wrapperKeyDown?: KeyboardEventHandler<HTMLDivElement> | undefined;
    menuRef?: RefObject<HTMLDivElement | null>;
}): ReactNode;
