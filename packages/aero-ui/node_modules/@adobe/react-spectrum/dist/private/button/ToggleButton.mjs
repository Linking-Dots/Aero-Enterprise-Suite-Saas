import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../button_vars.css";
import $fewWH$button_vars_cssmjs from "../button_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {FocusRing as $fewWH$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $fewWH$mergeProps} from "react-aria/mergeProps";
import $fewWH$react from "react";
import {useToggleButton as $fewWH$useToggleButton} from "react-aria/useToggleButton";
import {useHover as $fewWH$useHover} from "react-aria/useHover";
import {useToggleState as $fewWH$useToggleState} from "react-stately/useToggleState";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 












const $0d90e7ff3b288d63$export$d2b052e7b4be1756 = /*#__PURE__*/ (0, $fewWH$react).forwardRef(function ToggleButton(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { isQuiet: isQuiet, isDisabled: isDisabled, isEmphasized: isEmphasized, staticColor: staticColor, children: children, autoFocus: autoFocus, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    let state = (0, $fewWH$useToggleState)(props);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $fewWH$useToggleButton)(props, state, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fewWH$useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let isTextOnly = (0, $fewWH$react).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, $fewWH$react).isValidElement(c));
    return /*#__PURE__*/ (0, $fewWH$react).createElement((0, $fewWH$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fewWH$button_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $fewWH$react).createElement("button", {
        ...styleProps,
        ...(0, $fewWH$mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fewWH$button_vars_cssmjs))), 'spectrum-ActionButton', {
            'spectrum-ActionButton--quiet': isQuiet,
            'spectrum-ActionButton--emphasized': isEmphasized,
            'spectrum-ActionButton--staticColor': !!staticColor,
            'spectrum-ActionButton--staticWhite': staticColor === 'white',
            'spectrum-ActionButton--staticBlack': staticColor === 'black',
            'is-active': isPressed,
            'is-disabled': isDisabled,
            'is-hovered': isHovered,
            'is-selected': state.isSelected
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $fewWH$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fewWH$button_vars_cssmjs))), 'spectrum-Icon')
            },
            text: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fewWH$button_vars_cssmjs))), 'spectrum-ActionButton-label')
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, $fewWH$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, children) : children)));
});


export {$0d90e7ff3b288d63$export$d2b052e7b4be1756 as ToggleButton};
//# sourceMappingURL=ToggleButton.mjs.map
