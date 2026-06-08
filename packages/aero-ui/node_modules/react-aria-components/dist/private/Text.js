import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import $dk7Vg$react, {createContext as $dk7Vg$createContext, forwardRef as $dk7Vg$forwardRef} from "react";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 

const $20d769b1e2b13352$export$9afb8bc826b033ea = /*#__PURE__*/ (0, $dk7Vg$createContext)({});
const $20d769b1e2b13352$export$5f1af8db9871e1d6 = /*#__PURE__*/ (0, $dk7Vg$forwardRef)(function Text(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $20d769b1e2b13352$export$9afb8bc826b033ea);
    let { elementType: elementType = 'span', ...domProps } = props;
    let ElementType = (0, $b7b7a92703138c9b$export$df3a06d6289f983e)[elementType];
    // @ts-ignore
    return /*#__PURE__*/ (0, $dk7Vg$react).createElement(ElementType, {
        className: "react-aria-Text",
        ...domProps,
        ref: ref
    });
});


export {$20d769b1e2b13352$export$9afb8bc826b033ea as TextContext, $20d769b1e2b13352$export$5f1af8db9871e1d6 as Text};
//# sourceMappingURL=Text.js.map
