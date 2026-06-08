import { AriaTagGroupProps } from 'react-aria/useTagGroup';
import { DOMRef, SpectrumLabelableProps, StyleProps, Validation } from '@react-types/shared';
import { JSX, ReactNode } from 'react';
export interface SpectrumTagGroupProps<T> extends Omit<AriaTagGroupProps<T>, 'selectionMode' | 'disallowEmptySelection' | 'selectedKeys' | 'defaultSelectedKeys' | 'onSelectionChange' | 'selectionBehavior' | 'disabledKeys'>, StyleProps, Omit<SpectrumLabelableProps, 'isRequired' | 'necessityIndicator'>, Pick<Validation<any>, 'isInvalid' | 'validationState'> {
    /** The label to display on the action button. */
    actionLabel?: string;
    /** Handler that is called when the action button is pressed. */
    onAction?: () => void;
    /** Sets what the TagGroup should render when there are no tags to display. */
    renderEmptyState?: () => JSX.Element;
    /**
     * Limit the number of rows initially shown. This will render a button that allows the user to
     * expand to show all tags.
     */
    maxRows?: number;
}
/**
 * Tags allow users to categorize content. They can represent keywords or people, and are grouped to
 * describe an item or a search request.
 */
export declare const TagGroup: <T>(props: SpectrumTagGroupProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactNode;
