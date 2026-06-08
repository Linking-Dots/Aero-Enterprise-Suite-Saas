import {CardBase as $957034e7d12d8044$export$7a6ccaf429ad93a8} from "./CardBase.mjs";
import {useCardViewContext as $161b6b8b1c8e2230$export$fea0b38586ec8f13} from "./CardViewContext.mjs";
import $6wsNs$react, {forwardRef as $6wsNs$forwardRef} from "react";

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


let $89d4493ab1dfc901$var$Card = /*#__PURE__*/ (0, $6wsNs$forwardRef)((props, ref)=>{
    let context = (0, $161b6b8b1c8e2230$export$fea0b38586ec8f13)();
    if (context !== null) return null;
    else return /*#__PURE__*/ (0, $6wsNs$react).createElement((0, $957034e7d12d8044$export$7a6ccaf429ad93a8), {
        ...props,
        ref: ref
    });
});
// @ts-ignore
$89d4493ab1dfc901$var$Card.getCollectionNode = function* getCollectionNode(props) {
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
let $89d4493ab1dfc901$export$60332b2344f7fe41 = $89d4493ab1dfc901$var$Card;


export {$89d4493ab1dfc901$export$60332b2344f7fe41 as Card};
//# sourceMappingURL=Card.mjs.map
