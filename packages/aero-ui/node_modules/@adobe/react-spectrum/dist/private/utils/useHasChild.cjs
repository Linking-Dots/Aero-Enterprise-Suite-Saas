var $15eSj$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $15eSj$react = require("react");


function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useHasChild", function () { return $dd6348d4a1a51ff9$export$e52e2242b6d0f1d4; });
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

function $dd6348d4a1a51ff9$export$e52e2242b6d0f1d4(query, ref) {
    let [hasChild, setHasChild] = (0, $15eSj$react.useState)(true);
    (0, $15eSj$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        setHasChild(!!(ref.current && ref.current.querySelector(query)));
    }, [
        setHasChild,
        query,
        ref
    ]);
    return hasChild;
}


//# sourceMappingURL=useHasChild.cjs.map
