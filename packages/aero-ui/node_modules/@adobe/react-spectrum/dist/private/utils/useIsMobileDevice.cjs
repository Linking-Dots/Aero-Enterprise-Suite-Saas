var $5qCjK$reactariaSSRProvider = require("react-aria/SSRProvider");


function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useIsMobileDevice", function () { return $0b97cdf6ccc1e502$export$736bf165441b18c7; });
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
const $0b97cdf6ccc1e502$var$MOBILE_SCREEN_WIDTH = 700;
function $0b97cdf6ccc1e502$export$736bf165441b18c7() {
    let isSSR = (0, $5qCjK$reactariaSSRProvider.useIsSSR)();
    if (isSSR || typeof window === 'undefined') return false;
    return window.screen.width <= $0b97cdf6ccc1e502$var$MOBILE_SCREEN_WIDTH;
}


//# sourceMappingURL=useIsMobileDevice.cjs.map
