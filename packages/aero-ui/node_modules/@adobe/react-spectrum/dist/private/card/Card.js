import {CardBase as $cdc2130588546f60$export$7a6ccaf429ad93a8} from "./CardBase.js";
import {useCardViewContext as $09157122c9607ee5$export$fea0b38586ec8f13} from "./CardViewContext.js";
import $giBA6$react, {forwardRef as $giBA6$forwardRef} from "react";

// @ts-nocheck
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


let $6487ef69d571694f$var$Card = /*#__PURE__*/ (0, $giBA6$forwardRef)((props, ref)=>{
    let context = (0, $09157122c9607ee5$export$fea0b38586ec8f13)();
    if (context !== null) return null;
    else return /*#__PURE__*/ (0, $giBA6$react).createElement((0, $cdc2130588546f60$export$7a6ccaf429ad93a8), {
        ...props,
        ref: ref
    });
});
// @ts-ignore
$6487ef69d571694f$var$Card.getCollectionNode = function* getCollectionNode(props) {
    let { children: children, textValue: textValue } = props;
    yield {
        type: 'item',
        props: props,
        rendered: children,
        'aria-label': props['aria-label'],
        hasChildNodes: false,
        textValue: textValue
    };
};
let $6487ef69d571694f$export$60332b2344f7fe41 = $6487ef69d571694f$var$Card;


export {$6487ef69d571694f$export$60332b2344f7fe41 as Card};
//# sourceMappingURL=Card.js.map
