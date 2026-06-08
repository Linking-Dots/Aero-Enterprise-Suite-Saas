import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $8dILT$filterDOMProps} from "react-aria/filterDOMProps";
import {HeadingContext as $8dILT$HeadingContext} from "react-aria-components/Heading";
import $8dILT$react, {forwardRef as $8dILT$forwardRef} from "react";
import {useContextProps as $8dILT$useContextProps} from "react-aria-components/slots";

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






const $31107baeb31b7fac$export$a8a3e93435678ff9 = /*#__PURE__*/ (0, $8dILT$forwardRef)(function Heading(props, ref) {
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'heading');
    [props, domRef] = (0, $8dILT$useContextProps)(props, domRef, (0, $8dILT$HeadingContext));
    let { children: children, level: level = 3, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let HeadingTag = `h${level}`;
    return /*#__PURE__*/ (0, $8dILT$react).createElement(HeadingTag, {
        ...(0, $8dILT$filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, children);
});


export {$31107baeb31b7fac$export$a8a3e93435678ff9 as Heading};
//# sourceMappingURL=Heading.mjs.map
