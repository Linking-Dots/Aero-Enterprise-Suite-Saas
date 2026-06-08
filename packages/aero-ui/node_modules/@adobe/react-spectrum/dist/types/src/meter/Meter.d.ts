import { SpectrumProgressBarBaseProps } from '../progress/ProgressBarBase';
import React from 'react';
export interface SpectrumMeterProps extends SpectrumProgressBarBaseProps {
    /**
     * The [visual style](https://spectrum.adobe.com/page/meter/#Options) of the Meter.
     *
     * @default 'informative'
     */
    variant?: 'informative' | 'positive' | 'warning' | 'critical';
}
/**
 * Meters are visual representations of a quantity or an achievement.
 * Their progress is determined by user actions, rather than system actions.
 */
export declare const Meter: React.ForwardRefExoticComponent<SpectrumMeterProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
