import {useIsSSR as $kIDRr$useIsSSR} from "react-aria/SSRProvider";

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
const $196ab9279fe71c29$var$MOBILE_SCREEN_WIDTH = 700;
function $196ab9279fe71c29$export$736bf165441b18c7() {
    let isSSR = (0, $kIDRr$useIsSSR)();
    if (isSSR || typeof window === 'undefined') return false;
    return window.screen.width <= $196ab9279fe71c29$var$MOBILE_SCREEN_WIDTH;
}


export {$196ab9279fe71c29$export$736bf165441b18c7 as useIsMobileDevice};
//# sourceMappingURL=useIsMobileDevice.js.map
