import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import { DisclosureGroupProps, DisclosurePanelProps, DisclosureProps } from 'react-aria-components/DisclosureGroup';
import React from 'react';
export interface SpectrumAccordionProps extends Omit<DisclosureGroupProps, 'className' | 'style' | 'render' | 'children'>, StyleProps, DOMProps {
    /** Whether the Accordion should be displayed with a quiet style. */
    isQuiet?: boolean;
    /** The disclosures within the accordion group. */
    children: React.ReactNode;
}
/** A group of disclosures that can be expanded and collapsed. */
export declare const Accordion: (props: SpectrumAccordionProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SpectrumDisclosureProps extends Omit<DisclosureProps, 'className' | 'style' | 'render' | 'children'>, AriaLabelingProps, StyleProps {
    /** Whether the Disclosure should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * The contents of the disclosure. The first child should be the header, and the second child
     * should be the panel.
     */
    children: React.ReactNode;
}
/** A collapsible section of content composed of a heading that expands and collapses a panel. */
export declare const Disclosure: (props: SpectrumDisclosureProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SpectrumDisclosurePanelProps extends Omit<DisclosurePanelProps, 'className' | 'style' | 'render' | 'children'>, DOMProps, AriaLabelingProps, StyleProps {
    /** The contents of the accordion panel. */
    children: React.ReactNode;
}
/** The panel that contains the content of the disclosure. */
export declare const DisclosurePanel: (props: SpectrumDisclosurePanelProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
export interface SpectrumDisclosureTitleProps extends DOMProps, AriaLabelingProps, StyleProps {
    /**
     * The heading level of the disclosure header.
     *
     * @default 3
     */
    level?: number;
    /** The contents of the disclosure header. */
    children: React.ReactNode;
}
/** The heading of the disclosure. */
export declare const DisclosureTitle: (props: SpectrumDisclosureTitleProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLHeadingElement>>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
