import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearButton as $cf8b586db4c34baa$export$13ec83e50bf04290} from "../button/ClearButton.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../tags_vars.css";
import $hEmok$tags_vars_cssmjs from "../tags_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useTag as $hEmok$useTag} from "react-aria/useTagGroup";
import {mergeProps as $hEmok$mergeProps} from "react-aria/mergeProps";
import $hEmok$react, {useRef as $hEmok$useRef} from "react";
import {useFocusRing as $hEmok$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $hEmok$useHover} from "react-aria/useHover";


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










function $db82d34e13e477cd$export$3288d34c523a1192(props) {
    const { item: item, state: state, ...otherProps } = props;
    // @ts-ignore
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $hEmok$useHover)({});
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $hEmok$useFocusRing)({
        within: false
    });
    let ref = (0, $hEmok$useRef)(null);
    let { removeButtonProps: removeButtonProps, gridCellProps: gridCellProps, rowProps: rowProps, allowsRemoving: allowsRemoving } = (0, $hEmok$useTag)({
        ...props,
        item: item
    }, state, ref);
    return /*#__PURE__*/ (0, $hEmok$react).createElement("div", {
        ...(0, $hEmok$mergeProps)(rowProps, hoverProps, focusProps),
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag', {
            'focus-ring': isFocusVisible,
            'is-focused': isFocused,
            'is-hovered': isHovered,
            'spectrum-Tag--removable': allowsRemoving
        }, styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, $hEmok$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag-cell'),
        ...gridCellProps
    }, /*#__PURE__*/ (0, $hEmok$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag-icon'),
                size: 'XS'
            },
            text: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag-content')
            },
            avatar: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag-avatar'),
                size: 'avatar-size-50'
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, $hEmok$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, item.rendered) : item.rendered, /*#__PURE__*/ (0, $hEmok$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, allowsRemoving && /*#__PURE__*/ (0, $hEmok$react).createElement($db82d34e13e477cd$var$TagRemoveButton, {
        item: item,
        ...removeButtonProps,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hEmok$tags_vars_cssmjs))), 'spectrum-Tag-removeButton')
    })))));
}
function $db82d34e13e477cd$var$TagRemoveButton(props) {
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    return /*#__PURE__*/ (0, $hEmok$react).createElement("span", styleProps, /*#__PURE__*/ (0, $hEmok$react).createElement((0, $cf8b586db4c34baa$export$13ec83e50bf04290), {
        ...props,
        inset: true
    }));
}


export {$db82d34e13e477cd$export$3288d34c523a1192 as Tag};
//# sourceMappingURL=Tag.js.map
