import React from 'react';
export interface SpectrumColorEditorProps {
    /** Whether to hide the alpha channel color slider and color field. */
    hideAlphaChannel?: boolean;
}
/**
 * ColorEditor provides a default UI for editing colors within a ColorPicker.
 */
export declare const ColorEditor: React.ForwardRefExoticComponent<SpectrumColorEditorProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
