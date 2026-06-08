import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../rule_vars.css";
import $5Ac2L$rule_vars_cssmjs from "../rule_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $5Ac2L$react from "react";
import {useSeparator as $5Ac2L$useSeparator} from "react-aria/useSeparator";


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






let $722c0b5bc0afb38c$var$sizeMap = {
    S: 'small',
    M: 'medium',
    L: 'large'
};
const $722c0b5bc0afb38c$export$2e0a83ec2e27ecbb = /*#__PURE__*/ (0, $5Ac2L$react).forwardRef(function Divider(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'divider');
    let { size: size = 'L', orientation: orientation = 'horizontal', ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let weight = $722c0b5bc0afb38c$var$sizeMap[size];
    let Element = 'hr';
    if (orientation === 'vertical') Element = 'div';
    let { separatorProps: separatorProps } = (0, $5Ac2L$useSeparator)({
        ...props,
        elementType: Element
    });
    return /*#__PURE__*/ (0, $5Ac2L$react).createElement(Element, {
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($5Ac2L$rule_vars_cssmjs))), 'spectrum-Rule', `spectrum-Rule--${weight}`, {
            'spectrum-Rule--vertical': orientation === 'vertical',
            'spectrum-Rule--horizontal': orientation === 'horizontal'
        }, styleProps.className),
        // @ts-ignore https://github.com/Microsoft/TypeScript/issues/28892
        ref: domRef,
        ...separatorProps
    });
});


export {$722c0b5bc0afb38c$export$2e0a83ec2e27ecbb as Divider};
//# sourceMappingURL=Divider.mjs.map
