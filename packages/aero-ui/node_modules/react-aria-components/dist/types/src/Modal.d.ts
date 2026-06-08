import { AriaModalOverlayProps } from 'react-aria/useModalOverlay';
import { ClassNameOrFunction, ContextValue, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import { OverlayTriggerProps, OverlayTriggerState } from 'react-stately/useOverlayTriggerState';
import React from 'react';
export interface ModalOverlayProps extends AriaModalOverlayProps, OverlayTriggerProps, RenderProps<ModalRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-ModalOverlay'
     */
    className?: ClassNameOrFunction<ModalRenderProps>;
    /**
     * Whether the modal is currently performing an entry animation.
     */
    isEntering?: boolean;
    /**
     * Whether the modal is currently performing an exit animation.
     */
    isExiting?: boolean;
    /**
     * The container element in which the overlay portal will be placed. This may have unknown
     * behavior depending on where it is portalled to.
     *
     * @deprecated - Use a parent UNSAFE_PortalProvider to set your portal container instead.
     * @default document.body
     */
    UNSTABLE_portalContainer?: Element;
}
export declare const ModalContext: React.Context<ContextValue<ModalOverlayProps, HTMLDivElement>>;
export interface ModalRenderProps {
    /**
     * Whether the modal is currently entering. Use this to apply animations.
     *
     * @selector [data-entering]
     */
    isEntering: boolean;
    /**
     * Whether the modal is currently exiting. Use this to apply animations.
     *
     * @selector [data-exiting]
     */
    isExiting: boolean;
    /**
     * State of the modal.
     */
    state: OverlayTriggerState;
}
/**
 * A modal is an overlay element which blocks interaction with elements outside it.
 */
export declare const Modal: (props: ModalOverlayProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
/**
 * A ModalOverlay is a wrapper for a Modal which allows customizing the backdrop element.
 */
export declare const ModalOverlay: (props: ModalOverlayProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
