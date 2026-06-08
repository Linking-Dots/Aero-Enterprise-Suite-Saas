import { AriaDisclosureProps } from 'react-aria/useDisclosure';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { DisclosureGroupState, DisclosureGroupProps as StatelyDisclosureGroupProps } from 'react-stately/useDisclosureGroupState';
import { DisclosureState } from 'react-stately/useDisclosureState';
import { DOMProps, GlobalDOMAttributes, Key } from '@react-types/shared';
import { LabelAriaProps } from 'react-aria/useLabel';
import React, { ReactNode } from 'react';
export interface DisclosureGroupProps extends StatelyDisclosureGroupProps, RenderProps<DisclosureGroupRenderProps>, DOMProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DisclosureGroup'
     */
    className?: ClassNameOrFunction<DisclosureGroupRenderProps>;
}
export interface DisclosureGroupRenderProps {
    /**
     * Whether the disclosure group is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the disclosure group.
     */
    state: DisclosureGroupState;
}
export declare const DisclosureGroupStateContext: React.Context<DisclosureGroupState | null>;
/**
 * A DisclosureGroup is a grouping of related disclosures, sometimes called an accordion.
 * It supports both single and multiple expanded items.
 */
export declare const DisclosureGroup: React.ForwardRefExoticComponent<DisclosureGroupProps & React.RefAttributes<HTMLDivElement>>;
export interface DisclosureProps extends Omit<AriaDisclosureProps, 'children'>, RenderProps<DisclosureRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Disclosure'
     */
    className?: ClassNameOrFunction<DisclosureRenderProps>;
    /**
     * An id for the disclosure when used within a DisclosureGroup, matching the id used in
     * `expandedKeys`.
     */
    id?: Key;
}
export interface DisclosureRenderProps {
    /**
     * Whether the disclosure is expanded.
     *
     * @selector [data-expanded]
     */
    isExpanded: boolean;
    /**
     * Whether the disclosure has keyboard focus.
     *
     * @selector [data-focus-visible-within]
     */
    isFocusVisibleWithin: boolean;
    /**
     * Whether the disclosure is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * State of the disclosure.
     */
    state: DisclosureState;
}
export declare const DisclosureContext: React.Context<ContextValue<DisclosureProps, HTMLDivElement>>;
export declare const DisclosureStateContext: React.Context<DisclosureState | null>;
/**
 * A disclosure is a collapsible section of content. It is composed of a a header with a heading and
 * trigger button, and a panel that contains the content.
 */
export declare const Disclosure: (props: DisclosureProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface DisclosurePanelRenderProps {
    /**
     * Whether keyboard focus is within the disclosure panel.
     *
     * @selector [data-focus-visible-within]
     */
    isFocusVisibleWithin: boolean;
}
export interface DisclosurePanelProps extends RenderProps<DisclosurePanelRenderProps>, DOMProps, LabelAriaProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DisclosurePanel'
     */
    className?: ClassNameOrFunction<DisclosurePanelRenderProps>;
    /**
     * The accessibility role for the disclosure's panel.
     *
     * @default 'group'
     */
    role?: 'group' | 'region';
    /**
     * The children of the component.
     */
    children: ReactNode;
}
/**
 * A DisclosurePanel provides the content for a disclosure.
 */
export declare const DisclosurePanel: (props: DisclosurePanelProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
