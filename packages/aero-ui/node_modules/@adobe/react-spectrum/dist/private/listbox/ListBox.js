import {ListBoxBase as $45f8932a4e549cb6$export$1afdcf349979fb7e, useListBoxLayout as $45f8932a4e549cb6$export$25768ea656ae32a7} from "./ListBoxBase.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import $aAaFq$react from "react";
import {useListState as $aAaFq$useListState} from "react-stately/useListState";

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



const $e6f9f1de53801217$export$41f133550aa26f48 = /*#__PURE__*/ (0, $aAaFq$react).forwardRef(function ListBox(props, ref) {
    let state = (0, $aAaFq$useListState)(props);
    let layout = (0, $45f8932a4e549cb6$export$25768ea656ae32a7)();
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $aAaFq$react).createElement((0, $45f8932a4e549cb6$export$1afdcf349979fb7e), {
        ...props,
        ref: domRef,
        state: state,
        layout: layout
    });
});


export {$e6f9f1de53801217$export$41f133550aa26f48 as ListBox};
//# sourceMappingURL=ListBox.js.map
