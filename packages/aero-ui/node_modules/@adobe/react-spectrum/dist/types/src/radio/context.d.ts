import { RadioGroupState } from 'react-stately/useRadioGroupState';
import React from 'react';
interface RadioGroupContext {
    isEmphasized?: boolean;
    name?: string;
    state: RadioGroupState;
}
export declare const RadioContext: React.Context<RadioGroupContext | null>;
export declare function useRadioProvider(): RadioGroupContext;
export {};
