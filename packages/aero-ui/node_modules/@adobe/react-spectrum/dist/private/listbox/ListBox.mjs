import {ListBoxBase as $ee13b4eccaed924f$export$1afdcf349979fb7e, useListBoxLayout as $ee13b4eccaed924f$export$25768ea656ae32a7} from "./ListBoxBase.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import $eslw3$react from "react";
import {useListState as $eslw3$useListState} from "react-stately/useListState";

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



const $bf2ac3e2d8e88cb9$export$41f133550aa26f48 = /*#__PURE__*/ (0, $eslw3$react).forwardRef(function ListBox(props, ref) {
    let state = (0, $eslw3$useListState)(props);
    let layout = (0, $ee13b4eccaed924f$export$25768ea656ae32a7)();
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $eslw3$react).createElement((0, $ee13b4eccaed924f$export$1afdcf349979fb7e), {
        ...props,
        ref: domRef,
        state: state,
        layout: layout
    });
});


export {$bf2ac3e2d8e88cb9$export$41f133550aa26f48 as ListBox};
//# sourceMappingURL=ListBox.mjs.map
