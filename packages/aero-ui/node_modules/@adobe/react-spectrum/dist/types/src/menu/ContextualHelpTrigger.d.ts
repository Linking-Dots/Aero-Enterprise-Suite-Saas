import { ItemProps, Key } from '@react-types/shared';
import React, { JSX, ReactElement } from 'react';
interface MenuDialogTriggerProps {
    /** Whether the menu item is currently unavailable. */
    isUnavailable?: boolean;
    /** The triggering Item and the Dialog, respectively. */
    children: [ReactElement, ReactElement];
}
interface InternalMenuDialogTriggerProps extends MenuDialogTriggerProps {
    targetKey: Key;
}
export interface SpectrumMenuDialogTriggerProps extends MenuDialogTriggerProps {
}
declare function ContextualHelpTrigger(props: InternalMenuDialogTriggerProps): ReactElement;
declare namespace ContextualHelpTrigger {
    var getCollectionNode: <T>(props: ItemProps<T>) => Generator<{
        element: ReactElement<unknown, string | React.JSXElementConstructor<any>>;
        wrapper: (element: any) => JSX.Element;
    }, void, unknown>;
}
declare let _Item: (props: SpectrumMenuDialogTriggerProps) => JSX.Element;
export { _Item as ContextualHelpTrigger };
