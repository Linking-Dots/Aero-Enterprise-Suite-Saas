var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../underlay_vars.css");
var $3e5647342fe68f54$exports = require("../underlay_vars_css.cjs");
var $5J3Su$reactariaprivateutilsisScrollable = require("react-aria/private/utils/isScrollable");
var $5J3Su$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Underlay", function () { return $1607bc090e03ac72$export$f360afc887607b02; });
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



function $1607bc090e03ac72$export$f360afc887607b02({ isOpen: isOpen, isTransparent: isTransparent, ...otherProps }) {
    let pageHeight = undefined;
    if (typeof document !== 'undefined') {
        let scrollingElement = (0, $5J3Su$reactariaprivateutilsisScrollable.isScrollable)(document.body) ? document.body : document.scrollingElement || document.documentElement;
        // Prevent Firefox from adding scrollbars when the page has a fractional height.
        let fractionalHeightDifference = scrollingElement.getBoundingClientRect().height % 1;
        pageHeight = scrollingElement.scrollHeight - fractionalHeightDifference;
    }
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5J3Su$react))).createElement("div", {
        "data-testid": "underlay",
        ...otherProps,
        // Cover the entire document so iOS 26 Safari doesn't clip the underlay to the inner viewport.
        style: {
            height: pageHeight
        },
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($3e5647342fe68f54$exports))), 'spectrum-Underlay', {
            'is-open': isOpen,
            'spectrum-Underlay--transparent': isTransparent
        })
    });
}


//# sourceMappingURL=Underlay.cjs.map
