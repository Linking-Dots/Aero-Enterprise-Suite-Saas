import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $brxMU$filterDOMProps} from "react-aria/filterDOMProps";
import $brxMU$react from "react";

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



function $661393416d7b515e$export$d43c2e2ca9b2c105(props) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'illustration');
    let { children: children, 'aria-label': ariaLabel, 'aria-labelledby': ariaLabelledby, 'aria-hidden': ariaHidden, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let hasLabel = ariaLabel || ariaLabelledby;
    if (!ariaHidden) ariaHidden = undefined;
    return /*#__PURE__*/ (0, $brxMU$react).cloneElement(children, {
        ...(0, $brxMU$filterDOMProps)(otherProps),
        ...styleProps,
        focusable: 'false',
        'aria-label': ariaLabel,
        'aria-labelledby': ariaLabelledby,
        'aria-hidden': ariaHidden,
        role: hasLabel ? 'img' : undefined
    });
}


export {$661393416d7b515e$export$d43c2e2ca9b2c105 as Illustration};
//# sourceMappingURL=Illustration.js.map
