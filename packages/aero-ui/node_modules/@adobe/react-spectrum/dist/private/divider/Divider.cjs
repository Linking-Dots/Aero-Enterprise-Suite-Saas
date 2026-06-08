var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../rule_vars.css");
var $ce4b794a606ad55c$exports = require("../rule_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $11WK8$react = require("react");
var $11WK8$reactariauseSeparator = require("react-aria/useSeparator");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Divider", function () { return $70687492d9e04f58$export$2e0a83ec2e27ecbb; });
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






let $70687492d9e04f58$var$sizeMap = {
    S: 'small',
    M: 'medium',
    L: 'large'
};
const $70687492d9e04f58$export$2e0a83ec2e27ecbb = /*#__PURE__*/ (0, ($parcel$interopDefault($11WK8$react))).forwardRef(function Divider(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'divider');
    let { size: size = 'L', orientation: orientation = 'horizontal', ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let weight = $70687492d9e04f58$var$sizeMap[size];
    let Element = 'hr';
    if (orientation === 'vertical') Element = 'div';
    let { separatorProps: separatorProps } = (0, $11WK8$reactariauseSeparator.useSeparator)({
        ...props,
        elementType: Element
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($11WK8$react))).createElement(Element, {
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($ce4b794a606ad55c$exports))), 'spectrum-Rule', `spectrum-Rule--${weight}`, {
            'spectrum-Rule--vertical': orientation === 'vertical',
            'spectrum-Rule--horizontal': orientation === 'horizontal'
        }, styleProps.className),
        // @ts-ignore https://github.com/Microsoft/TypeScript/issues/28892
        ref: domRef,
        ...separatorProps
    });
});


//# sourceMappingURL=Divider.cjs.map
