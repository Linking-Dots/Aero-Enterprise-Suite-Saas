import { Key } from '@react-types/shared';
import React, { type JSX, ReactElement } from 'react';
interface SubmenuTriggerProps {
    /**
     * The contents of the SubmenuTrigger - an Item and a Menu.
     */
    children: ReactElement<any>[];
    targetKey: Key;
}
export interface SpectrumSubmenuTriggerProps extends Omit<SubmenuTriggerProps, 'targetKey'> {
}
declare function SubmenuTrigger(props: SubmenuTriggerProps): JSX.Element;
declare namespace SubmenuTrigger {
    var getCollectionNode: (props: SpectrumSubmenuTriggerProps) => Generator<{
        element: ReactElement<unknown, string | React.JSXElementConstructor<any>>;
        wrapper: (element: any) => JSX.Element;
    }, void, unknown>;
}
declare let _SubmenuTrigger: (props: SpectrumSubmenuTriggerProps) => JSX.Element;
export { _SubmenuTrigger as SubmenuTrigger };
