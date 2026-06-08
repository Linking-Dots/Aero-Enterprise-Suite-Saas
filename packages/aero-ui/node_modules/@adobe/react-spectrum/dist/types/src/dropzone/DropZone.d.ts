import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import { DropZoneProps } from 'react-aria-components/DropZone';
import React, { ReactNode } from 'react';
export interface SpectrumDropZoneProps extends Omit<DropZoneProps, 'onHoverStart' | 'onHoverChange' | 'onHoverEnd' | 'isDisabled' | 'className' | 'style' | 'render'>, DOMProps, StyleProps, AriaLabelingProps {
    /** The content to display in the drop zone. */
    children: ReactNode;
    /** Whether the drop zone has been filled. */
    isFilled?: boolean;
    /** The message to replace the default banner message that is shown when the drop zone is filled. */
    replaceMessage?: string;
}
/**
 * A drop zone is an area into which one or multiple objects can be dragged and dropped.
 */
export declare const DropZone: React.ForwardRefExoticComponent<SpectrumDropZoneProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
