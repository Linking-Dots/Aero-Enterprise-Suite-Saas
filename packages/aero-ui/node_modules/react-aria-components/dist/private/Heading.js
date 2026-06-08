import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import $cqURp$react, {createContext as $cqURp$createContext, forwardRef as $cqURp$forwardRef} from "react";

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

const $cd487658cd83358d$export$d688439359537581 = /*#__PURE__*/ (0, $cqURp$createContext)({});
const $cd487658cd83358d$export$a8a3e93435678ff9 = /*#__PURE__*/ (0, $cqURp$forwardRef)(function Heading(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $cd487658cd83358d$export$d688439359537581);
    let { children: children, level: level = 3, className: className, ...domProps } = props;
    let Element = (0, $b7b7a92703138c9b$export$df3a06d6289f983e)[`h${level}`];
    return /*#__PURE__*/ (0, $cqURp$react).createElement(Element, {
        ...domProps,
        ref: ref,
        className: className !== null && className !== void 0 ? className : 'react-aria-Heading'
    }, children);
});


export {$cd487658cd83358d$export$d688439359537581 as HeadingContext, $cd487658cd83358d$export$a8a3e93435678ff9 as Heading};
//# sourceMappingURL=Heading.js.map
