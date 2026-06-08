import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415} from "./utils.mjs";
import $cGkLv$react, {createContext as $cGkLv$createContext, forwardRef as $cGkLv$forwardRef} from "react";

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

const $efe09c6d1c304b50$export$9afb8bc826b033ea = /*#__PURE__*/ (0, $cGkLv$createContext)({});
const $efe09c6d1c304b50$export$5f1af8db9871e1d6 = /*#__PURE__*/ (0, $cGkLv$forwardRef)(function Text(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $efe09c6d1c304b50$export$9afb8bc826b033ea);
    let { elementType: elementType = 'span', ...domProps } = props;
    let ElementType = (0, $7230ffa83bc0c2cf$export$df3a06d6289f983e)[elementType];
    // @ts-ignore
    return /*#__PURE__*/ (0, $cGkLv$react).createElement(ElementType, {
        className: "react-aria-Text",
        ...domProps,
        ref: ref
    });
});


export {$efe09c6d1c304b50$export$9afb8bc826b033ea as TextContext, $efe09c6d1c304b50$export$5f1af8db9871e1d6 as Text};
//# sourceMappingURL=Text.mjs.map
