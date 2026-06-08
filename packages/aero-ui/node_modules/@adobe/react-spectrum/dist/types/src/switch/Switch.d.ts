import { AriaSwitchProps } from 'react-aria/useSwitch';
import { StyleProps } from '@react-types/shared';
import React from 'react';
export interface SpectrumSwitchProps extends AriaSwitchProps, StyleProps {
    /**
     * This prop sets the emphasized style which provides visual prominence.
     */
    isEmphasized?: boolean;
}
/**
 * Switches allow users to turn an individual option on or off.
 * They are usually used to activate or deactivate a specific setting.
 */
export declare const Switch: React.ForwardRefExoticComponent<SpectrumSwitchProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLLabelElement, HTMLLabelElement>>>;
