import { AriaLinkProps } from 'react-aria/useLink';
import { JSX, ReactNode } from 'react';
import { StyleProps } from '@react-types/shared';
export interface SpectrumLinkProps extends Omit<AriaLinkProps, 'onClick'>, StyleProps {
    /** The content to display in the link. */
    children: ReactNode;
    /**
     * The [visual style](https://spectrum.adobe.com/page/link/#Options) of the link.
     *
     * @default 'primary'
     */
    variant?: 'primary' | 'secondary' | 'overBackground';
    /** Whether the link should be displayed with a quiet style. */
    isQuiet?: boolean;
}
/**
 * Links allow users to navigate to a different location.
 * They can be presented inline inside a paragraph or as standalone text.
 */
export declare function Link(props: SpectrumLinkProps): JSX.Element;
