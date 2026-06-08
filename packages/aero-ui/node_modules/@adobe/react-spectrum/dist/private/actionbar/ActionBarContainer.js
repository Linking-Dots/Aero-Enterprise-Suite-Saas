import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "./actionbar.css";
import $eSLMe$actionbar_cssmjs from "./actionbar_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $eSLMe$filterDOMProps} from "react-aria/filterDOMProps";
import $eSLMe$react from "react";


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






const $d998ea5bb37d669c$export$ac2eb07f267e434c = /*#__PURE__*/ (0, $eSLMe$react).forwardRef(function ActionBarContainer(props, ref) {
    // Grabs specific props from the closest Provider (see https://react-spectrum.adobe.com/react-spectrum/Provider.html#property-groups). Remove if your component doesn't support any of the listed props.
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { children: children } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $eSLMe$react).createElement("div", {
        ...(0, $eSLMe$filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eSLMe$actionbar_cssmjs))), 'ActionBarContainer', styleProps.className)
    }, children);
});


export {$d998ea5bb37d669c$export$ac2eb07f267e434c as ActionBarContainer};
//# sourceMappingURL=ActionBarContainer.js.map
