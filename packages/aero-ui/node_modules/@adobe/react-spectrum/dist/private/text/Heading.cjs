var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $8JQ45$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $8JQ45$reactariacomponentsHeading = require("react-aria-components/Heading");
var $8JQ45$react = require("react");
var $8JQ45$reactariacomponentsslots = require("react-aria-components/slots");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Heading", function () { return $ef0b1cd7dce2b6c2$export$a8a3e93435678ff9; });
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






const $ef0b1cd7dce2b6c2$export$a8a3e93435678ff9 = /*#__PURE__*/ (0, $8JQ45$react.forwardRef)(function Heading(props, ref) {
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'heading');
    [props, domRef] = (0, $8JQ45$reactariacomponentsslots.useContextProps)(props, domRef, (0, $8JQ45$reactariacomponentsHeading.HeadingContext));
    let { children: children, level: level = 3, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let HeadingTag = `h${level}`;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8JQ45$react))).createElement(HeadingTag, {
        ...(0, $8JQ45$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, children);
});


//# sourceMappingURL=Heading.cjs.map
