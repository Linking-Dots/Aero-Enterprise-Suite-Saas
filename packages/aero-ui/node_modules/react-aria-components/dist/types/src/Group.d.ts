import { AriaLabelingProps, DOMProps } from '@react-types/shared';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { HoverProps } from 'react-aria/useHover';
import React, { HTMLAttributes } from 'react';
export interface GroupRenderProps {
    /**
     * Whether the group is currently hovered with a mouse.
     *
     * @selector [data-hovered]
     */
    isHovered: boolean;
    /**
     * Whether an element within the group is focused, either via a mouse or keyboard.
     *
     * @selector [data-focus-within]
     */
    isFocusWithin: boolean;
    /**
     * Whether an element within the group is keyboard focused.
     *
     * @selector [data-focus-visible]
     */
    isFocusVisible: boolean;
    /**
     * Whether the group is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the group is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
}
export interface GroupProps extends AriaLabelingProps, Omit<HTMLAttributes<HTMLElement>, 'children' | 'className' | 'style' | 'render' | 'role' | 'slot'>, DOMProps, HoverProps, RenderProps<GroupRenderProps>, SlotProps {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-Group'
     */
    className?: ClassNameOrFunction<GroupRenderProps>;
    /** Whether the group is disabled. */
    isDisabled?: boolean;
    /** Whether the group is invalid. */
    isInvalid?: boolean;
    /** Whether the group is read only. */
    isReadOnly?: boolean;
    /**
     * An accessibility role for the group. By default, this is set to `'group'`.
     * Use `'region'` when the contents of the group is important enough to be
     * included in the page table of contents. Use `'presentation'` if the group
     * is visual only and does not represent a semantic grouping of controls.
     *
     * @default 'group'
     */
    role?: 'group' | 'region' | 'presentation';
}
export declare const GroupContext: React.Context<ContextValue<GroupProps, HTMLDivElement>>;
/**
 * A group represents a set of related UI controls, and supports interactive states for styling.
 */
export declare const Group: (props: GroupProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
