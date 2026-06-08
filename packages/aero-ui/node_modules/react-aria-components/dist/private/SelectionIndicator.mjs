import {useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415} from "./utils.mjs";
import {SharedElement as $792f28e438b9ad5f$export$c34620ff8881d89f} from "./SharedElementTransition.mjs";
import $31sgV$react, {createContext as $31sgV$createContext, forwardRef as $31sgV$forwardRef} from "react";

/*
 * Copyright 2025 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $91fe5e721c7f36c1$export$c9549807523555e0 = /*#__PURE__*/ (0, $31sgV$createContext)({
    isSelected: false
});
const $91fe5e721c7f36c1$export$17f80983afe4e444 = /*#__PURE__*/ (0, $31sgV$forwardRef)(function SelectionIndicator(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $91fe5e721c7f36c1$export$c9549807523555e0);
    let { isSelected: isSelected, ...otherProps } = props;
    return /*#__PURE__*/ (0, $31sgV$react).createElement((0, $792f28e438b9ad5f$export$c34620ff8881d89f), {
        ...otherProps,
        ref: ref,
        className: props.className || 'react-aria-SelectionIndicator',
        name: "SelectionIndicator",
        isVisible: isSelected
    });
});


export {$91fe5e721c7f36c1$export$c9549807523555e0 as SelectionIndicatorContext, $91fe5e721c7f36c1$export$17f80983afe4e444 as SelectionIndicator};
//# sourceMappingURL=SelectionIndicator.mjs.map
