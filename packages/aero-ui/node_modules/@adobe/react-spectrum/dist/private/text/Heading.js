import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $ckqwo$filterDOMProps} from "react-aria/filterDOMProps";
import {HeadingContext as $ckqwo$HeadingContext} from "react-aria-components/Heading";
import $ckqwo$react, {forwardRef as $ckqwo$forwardRef} from "react";
import {useContextProps as $ckqwo$useContextProps} from "react-aria-components/slots";

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






const $ddc09b0bc61c28b1$export$a8a3e93435678ff9 = /*#__PURE__*/ (0, $ckqwo$forwardRef)(function Heading(props, ref) {
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'heading');
    [props, domRef] = (0, $ckqwo$useContextProps)(props, domRef, (0, $ckqwo$HeadingContext));
    let { children: children, level: level = 3, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let HeadingTag = `h${level}`;
    return /*#__PURE__*/ (0, $ckqwo$react).createElement(HeadingTag, {
        ...(0, $ckqwo$filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, children);
});


export {$ddc09b0bc61c28b1$export$a8a3e93435678ff9 as Heading};
//# sourceMappingURL=Heading.js.map
