import { DOMProps, FocusStrategy, HoverEvents, KeyboardEvents, PressEvents, RefObject } from '@react-types/shared';
import React, { HTMLAttributes } from 'react';
import { RootMenuTriggerState } from 'react-stately/useMenuTriggerState';
import { TreeState } from 'react-stately/useTreeState';
export interface MenuContextValue extends Omit<HTMLAttributes<HTMLElement>, 'autoFocus' | 'onKeyDown'>, Pick<KeyboardEvents, 'onKeyDown'> {
    onClose?: () => void;
    closeOnSelect?: boolean;
    shouldFocusWrap?: boolean;
    autoFocus?: boolean | FocusStrategy;
    ref?: RefObject<HTMLDivElement | null>;
    state?: RootMenuTriggerState;
    onBackButtonPress?: () => void;
    submenuLevel?: number;
}
export declare const MenuContext: React.Context<MenuContextValue>;
export declare function useMenuContext(): MenuContextValue;
export interface SubmenuTriggerContextValue extends DOMProps, Pick<PressEvents, 'onPressStart' | 'onPress'>, Pick<HoverEvents, 'onHoverChange'>, Pick<KeyboardEvents, 'onKeyDown'> {
    isUnavailable?: boolean;
    triggerRef?: RefObject<HTMLElement | null>;
    'aria-expanded'?: boolean | 'true' | 'false';
    'aria-controls'?: string;
    'aria-haspopup'?: 'dialog' | 'menu';
    isOpen?: boolean;
}
export declare const SubmenuTriggerContext: React.Context<SubmenuTriggerContextValue | undefined>;
export declare function useSubmenuTriggerContext(): SubmenuTriggerContextValue | undefined;
export interface MenuStateContextValue<T> {
    state: TreeState<T>;
    popoverContainer: HTMLElement | null;
    trayContainerRef: RefObject<HTMLElement | null>;
    menu: RefObject<HTMLDivElement | null>;
    submenu: RefObject<HTMLDivElement | null>;
    rootMenuTriggerState?: RootMenuTriggerState;
}
export declare const MenuStateContext: React.Context<MenuStateContextValue<any> | undefined>;
export declare function useMenuStateContext(): MenuStateContextValue<any> | undefined;
