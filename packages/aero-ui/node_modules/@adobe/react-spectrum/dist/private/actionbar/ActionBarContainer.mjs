import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "./actionbar.css";
import $3wBcS$actionbar_cssmjs from "./actionbar_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $3wBcS$filterDOMProps} from "react-aria/filterDOMProps";
import $3wBcS$react from "react";


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






const $1f44d724a35dac69$export$ac2eb07f267e434c = /*#__PURE__*/ (0, $3wBcS$react).forwardRef(function ActionBarContainer(props, ref) {
    // Grabs specific props from the closest Provider (see https://react-spectrum.adobe.com/react-spectrum/Provider.html#property-groups). Remove if your component doesn't support any of the listed props.
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { children: children } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $3wBcS$react).createElement("div", {
        ...(0, $3wBcS$filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3wBcS$actionbar_cssmjs))), 'ActionBarContainer', styleProps.className)
    }, children);
});


export {$1f44d724a35dac69$export$ac2eb07f267e434c as ActionBarContainer};
//# sourceMappingURL=ActionBarContainer.mjs.map
