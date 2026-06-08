var $eXFpb$react = require("react");


function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "createDOMRef", function () { return $65aea7b37663976b$export$a5795cc979dfae80; });
$parcel$export(module.exports, "createFocusableRef", function () { return $65aea7b37663976b$export$79d69eee6ae4b329; });
$parcel$export(module.exports, "useDOMRef", function () { return $65aea7b37663976b$export$c2c55ef9111cafd8; });
$parcel$export(module.exports, "useFocusableRef", function () { return $65aea7b37663976b$export$96a734597687c040; });
$parcel$export(module.exports, "unwrapDOMRef", function () { return $65aea7b37663976b$export$c7e28c72a4823176; });
$parcel$export(module.exports, "useUnwrapDOMRef", function () { return $65aea7b37663976b$export$1d5cc31d9d8df817; });
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
function $65aea7b37663976b$export$a5795cc979dfae80(ref) {
    return {
        UNSAFE_getDOMNode () {
            return ref.current;
        }
    };
}
function $65aea7b37663976b$export$79d69eee6ae4b329(domRef, focusableRef = domRef) {
    return {
        ...$65aea7b37663976b$export$a5795cc979dfae80(domRef),
        focus () {
            if (focusableRef.current) focusableRef.current.focus();
        }
    };
}
function $65aea7b37663976b$export$c2c55ef9111cafd8(ref) {
    let domRef = (0, $eXFpb$react.useRef)(null);
    (0, $eXFpb$react.useImperativeHandle)(ref, ()=>$65aea7b37663976b$export$a5795cc979dfae80(domRef));
    return domRef;
}
function $65aea7b37663976b$export$96a734597687c040(ref, focusableRef) {
    let domRef = (0, $eXFpb$react.useRef)(null);
    (0, $eXFpb$react.useImperativeHandle)(ref, ()=>$65aea7b37663976b$export$79d69eee6ae4b329(domRef, focusableRef));
    return domRef;
}
function $65aea7b37663976b$export$c7e28c72a4823176(ref) {
    return {
        get current () {
            return ref.current && ref.current.UNSAFE_getDOMNode();
        }
    };
}
function $65aea7b37663976b$export$1d5cc31d9d8df817(ref) {
    return (0, $eXFpb$react.useMemo)(()=>$65aea7b37663976b$export$c7e28c72a4823176(ref), [
        ref
    ]);
}


//# sourceMappingURL=useDOMRef.cjs.map
