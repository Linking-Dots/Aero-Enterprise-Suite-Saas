var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $8qyOg$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $8qyOg$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Illustration", function () { return $e1595e3d2977a284$export$d43c2e2ca9b2c105; });
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



function $e1595e3d2977a284$export$d43c2e2ca9b2c105(props) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'illustration');
    let { children: children, 'aria-label': ariaLabel, 'aria-labelledby': ariaLabelledby, 'aria-hidden': ariaHidden, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let hasLabel = ariaLabel || ariaLabelledby;
    if (!ariaHidden) ariaHidden = undefined;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8qyOg$react))).cloneElement(children, {
        ...(0, $8qyOg$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        focusable: 'false',
        'aria-label': ariaLabel,
        'aria-labelledby': ariaLabelledby,
        'aria-hidden': ariaHidden,
        role: hasLabel ? 'img' : undefined
    });
}


//# sourceMappingURL=Illustration.cjs.map
