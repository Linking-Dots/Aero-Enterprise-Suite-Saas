import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../underlay_vars.css";
import $cgYOc$underlay_vars_cssmjs from "../underlay_vars_css.mjs";
import {isScrollable as $cgYOc$isScrollable} from "react-aria/private/utils/isScrollable";
import $cgYOc$react from "react";


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



function $29c7092a192e9a93$export$f360afc887607b02({ isOpen: isOpen, isTransparent: isTransparent, ...otherProps }) {
    let pageHeight = undefined;
    if (typeof document !== 'undefined') {
        let scrollingElement = (0, $cgYOc$isScrollable)(document.body) ? document.body : document.scrollingElement || document.documentElement;
        // Prevent Firefox from adding scrollbars when the page has a fractional height.
        let fractionalHeightDifference = scrollingElement.getBoundingClientRect().height % 1;
        pageHeight = scrollingElement.scrollHeight - fractionalHeightDifference;
    }
    return /*#__PURE__*/ (0, $cgYOc$react).createElement("div", {
        "data-testid": "underlay",
        ...otherProps,
        // Cover the entire document so iOS 26 Safari doesn't clip the underlay to the inner viewport.
        style: {
            height: pageHeight
        },
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cgYOc$underlay_vars_cssmjs))), 'spectrum-Underlay', {
            'is-open': isOpen,
            'spectrum-Underlay--transparent': isTransparent
        })
    });
}


export {$29c7092a192e9a93$export$f360afc887607b02 as Underlay};
//# sourceMappingURL=Underlay.js.map
