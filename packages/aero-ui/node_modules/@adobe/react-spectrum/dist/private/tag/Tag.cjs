var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $0fc8553a4214494f$exports = require("../button/ClearButton.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../tags_vars.css");
var $74103ae0a349d695$exports = require("../tags_vars_css.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $7CUPC$reactariauseTagGroup = require("react-aria/useTagGroup");
var $7CUPC$reactariamergeProps = require("react-aria/mergeProps");
var $7CUPC$react = require("react");
var $7CUPC$reactariauseFocusRing = require("react-aria/useFocusRing");
var $7CUPC$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Tag", function () { return $5bdeed39ae3015c4$export$3288d34c523a1192; });
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










function $5bdeed39ae3015c4$export$3288d34c523a1192(props) {
    const { item: item, state: state, ...otherProps } = props;
    // @ts-ignore
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $7CUPC$reactariauseHover.useHover)({});
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $7CUPC$reactariauseFocusRing.useFocusRing)({
        within: false
    });
    let ref = (0, $7CUPC$react.useRef)(null);
    let { removeButtonProps: removeButtonProps, gridCellProps: gridCellProps, rowProps: rowProps, allowsRemoving: allowsRemoving } = (0, $7CUPC$reactariauseTagGroup.useTag)({
        ...props,
        item: item
    }, state, ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement("div", {
        ...(0, $7CUPC$reactariamergeProps.mergeProps)(rowProps, hoverProps, focusProps),
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag', {
            'focus-ring': isFocusVisible,
            'is-focused': isFocused,
            'is-hovered': isHovered,
            'spectrum-Tag--removable': allowsRemoving
        }, styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag-cell'),
        ...gridCellProps
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            icon: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag-icon'),
                size: 'XS'
            },
            text: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag-content')
            },
            avatar: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag-avatar'),
                size: 'avatar-size-50'
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, item.rendered) : item.rendered, /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, allowsRemoving && /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement($5bdeed39ae3015c4$var$TagRemoveButton, {
        item: item,
        ...removeButtonProps,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($74103ae0a349d695$exports))), 'spectrum-Tag-removeButton')
    })))));
}
function $5bdeed39ae3015c4$var$TagRemoveButton(props) {
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement("span", styleProps, /*#__PURE__*/ (0, ($parcel$interopDefault($7CUPC$react))).createElement((0, $0fc8553a4214494f$exports.ClearButton), {
        ...props,
        inset: true
    }));
}


//# sourceMappingURL=Tag.cjs.map
