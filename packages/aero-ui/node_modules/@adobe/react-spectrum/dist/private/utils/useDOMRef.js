import {useRef as $3lCUw$useRef, useImperativeHandle as $3lCUw$useImperativeHandle, useMemo as $3lCUw$useMemo} from "react";

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
function $c234463e9ef56637$export$a5795cc979dfae80(ref) {
    return {
        UNSAFE_getDOMNode () {
            return ref.current;
        }
    };
}
function $c234463e9ef56637$export$79d69eee6ae4b329(domRef, focusableRef = domRef) {
    return {
        ...$c234463e9ef56637$export$a5795cc979dfae80(domRef),
        focus () {
            if (focusableRef.current) focusableRef.current.focus();
        }
    };
}
function $c234463e9ef56637$export$c2c55ef9111cafd8(ref) {
    let domRef = (0, $3lCUw$useRef)(null);
    (0, $3lCUw$useImperativeHandle)(ref, ()=>$c234463e9ef56637$export$a5795cc979dfae80(domRef));
    return domRef;
}
function $c234463e9ef56637$export$96a734597687c040(ref, focusableRef) {
    let domRef = (0, $3lCUw$useRef)(null);
    (0, $3lCUw$useImperativeHandle)(ref, ()=>$c234463e9ef56637$export$79d69eee6ae4b329(domRef, focusableRef));
    return domRef;
}
function $c234463e9ef56637$export$c7e28c72a4823176(ref) {
    return {
        get current () {
            return ref.current && ref.current.UNSAFE_getDOMNode();
        }
    };
}
function $c234463e9ef56637$export$1d5cc31d9d8df817(ref) {
    return (0, $3lCUw$useMemo)(()=>$c234463e9ef56637$export$c7e28c72a4823176(ref), [
        ref
    ]);
}


export {$c234463e9ef56637$export$a5795cc979dfae80 as createDOMRef, $c234463e9ef56637$export$79d69eee6ae4b329 as createFocusableRef, $c234463e9ef56637$export$c2c55ef9111cafd8 as useDOMRef, $c234463e9ef56637$export$96a734597687c040 as useFocusableRef, $c234463e9ef56637$export$c7e28c72a4823176 as unwrapDOMRef, $c234463e9ef56637$export$1d5cc31d9d8df817 as useUnwrapDOMRef};
//# sourceMappingURL=useDOMRef.js.map
