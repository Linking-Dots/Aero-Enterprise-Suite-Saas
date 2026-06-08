import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import {createHideableComponent as $7pcuQ$createHideableComponent} from "react-aria/private/collections/Hidden";
import $7pcuQ$react, {createContext as $7pcuQ$createContext} from "react";

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


const $3e4839e5b30e7b17$export$75b6ee27786ba447 = /*#__PURE__*/ (0, $7pcuQ$createContext)({});
const $3e4839e5b30e7b17$export$b04be29aa201d4f5 = /*#__PURE__*/ (0, $7pcuQ$createHideableComponent)(function Label(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $3e4839e5b30e7b17$export$75b6ee27786ba447);
    let { elementType: elementType = 'label', ...labelProps } = props;
    let ElementType = (0, $b7b7a92703138c9b$export$df3a06d6289f983e)[elementType];
    // @ts-ignore
    return /*#__PURE__*/ (0, $7pcuQ$react).createElement(ElementType, {
        className: "react-aria-Label",
        ...labelProps,
        ref: ref
    });
});


export {$3e4839e5b30e7b17$export$75b6ee27786ba447 as LabelContext, $3e4839e5b30e7b17$export$b04be29aa201d4f5 as Label};
//# sourceMappingURL=Label.js.map
