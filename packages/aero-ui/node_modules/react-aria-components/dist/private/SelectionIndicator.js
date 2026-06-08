import {useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import {SharedElement as $347bc273c4058e94$export$c34620ff8881d89f} from "./SharedElementTransition.js";
import $7dEbS$react, {createContext as $7dEbS$createContext, forwardRef as $7dEbS$forwardRef} from "react";

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


const $0d6f83ad40839938$export$c9549807523555e0 = /*#__PURE__*/ (0, $7dEbS$createContext)({
    isSelected: false
});
const $0d6f83ad40839938$export$17f80983afe4e444 = /*#__PURE__*/ (0, $7dEbS$forwardRef)(function SelectionIndicator(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $0d6f83ad40839938$export$c9549807523555e0);
    let { isSelected: isSelected, ...otherProps } = props;
    return /*#__PURE__*/ (0, $7dEbS$react).createElement((0, $347bc273c4058e94$export$c34620ff8881d89f), {
        ...otherProps,
        ref: ref,
        className: props.className || 'react-aria-SelectionIndicator',
        name: "SelectionIndicator",
        isVisible: isSelected
    });
});


export {$0d6f83ad40839938$export$c9549807523555e0 as SelectionIndicatorContext, $0d6f83ad40839938$export$17f80983afe4e444 as SelectionIndicator};
//# sourceMappingURL=SelectionIndicator.js.map
