import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearButton as $ab14010a528467be$export$13ec83e50bf04290} from "../button/ClearButton.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../tags_vars.css";
import $cslN6$tags_vars_cssmjs from "../tags_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useTag as $cslN6$useTag} from "react-aria/useTagGroup";
import {mergeProps as $cslN6$mergeProps} from "react-aria/mergeProps";
import $cslN6$react, {useRef as $cslN6$useRef} from "react";
import {useFocusRing as $cslN6$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $cslN6$useHover} from "react-aria/useHover";


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










function $9d679de5135d5833$export$3288d34c523a1192(props) {
    const { item: item, state: state, ...otherProps } = props;
    // @ts-ignore
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $cslN6$useHover)({});
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $cslN6$useFocusRing)({
        within: false
    });
    let ref = (0, $cslN6$useRef)(null);
    let { removeButtonProps: removeButtonProps, gridCellProps: gridCellProps, rowProps: rowProps, allowsRemoving: allowsRemoving } = (0, $cslN6$useTag)({
        ...props,
        item: item
    }, state, ref);
    return /*#__PURE__*/ (0, $cslN6$react).createElement("div", {
        ...(0, $cslN6$mergeProps)(rowProps, hoverProps, focusProps),
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag', {
            'focus-ring': isFocusVisible,
            'is-focused': isFocused,
            'is-hovered': isHovered,
            'spectrum-Tag--removable': allowsRemoving
        }, styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, $cslN6$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag-cell'),
        ...gridCellProps
    }, /*#__PURE__*/ (0, $cslN6$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag-icon'),
                size: 'XS'
            },
            text: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag-content')
            },
            avatar: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag-avatar'),
                size: 'avatar-size-50'
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, $cslN6$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, item.rendered) : item.rendered, /*#__PURE__*/ (0, $cslN6$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, allowsRemoving && /*#__PURE__*/ (0, $cslN6$react).createElement($9d679de5135d5833$var$TagRemoveButton, {
        item: item,
        ...removeButtonProps,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cslN6$tags_vars_cssmjs))), 'spectrum-Tag-removeButton')
    })))));
}
function $9d679de5135d5833$var$TagRemoveButton(props) {
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    return /*#__PURE__*/ (0, $cslN6$react).createElement("span", styleProps, /*#__PURE__*/ (0, $cslN6$react).createElement((0, $ab14010a528467be$export$13ec83e50bf04290), {
        ...props,
        inset: true
    }));
}


export {$9d679de5135d5833$export$3288d34c523a1192 as Tag};
//# sourceMappingURL=Tag.mjs.map
