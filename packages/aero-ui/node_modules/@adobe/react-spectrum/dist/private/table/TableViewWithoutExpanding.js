import {TableViewBase as $0f1ea4f28e10f05a$export$517e02184d273d69} from "./TableViewBase.js";
import $dq4Yb$react, {useState as $dq4Yb$useState} from "react";
import {useTableState as $dq4Yb$useTableState} from "react-stately/useTableState";

/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $afff8b74e9314c77$export$c488dd5235f7fcac = /*#__PURE__*/ (0, $dq4Yb$react).forwardRef(function TableView(props, ref) {
    let { selectionStyle: selectionStyle, dragAndDropHooks: dragAndDropHooks } = props;
    let [showSelectionCheckboxes, setShowSelectionCheckboxes] = (0, $dq4Yb$useState)(selectionStyle !== 'highlight');
    let isTableDraggable = !!(dragAndDropHooks === null || dragAndDropHooks === void 0 ? void 0 : dragAndDropHooks.useDraggableCollectionState);
    let state = (0, $dq4Yb$useTableState)({
        ...props,
        showSelectionCheckboxes: showSelectionCheckboxes,
        showDragButtons: isTableDraggable,
        selectionBehavior: props.selectionStyle === 'highlight' ? 'replace' : 'toggle'
    });
    // If the selection behavior changes in state, we need to update showSelectionCheckboxes here due to the circular dependency...
    let shouldShowCheckboxes = state.selectionManager.selectionBehavior !== 'replace';
    if (shouldShowCheckboxes !== showSelectionCheckboxes) setShowSelectionCheckboxes(shouldShowCheckboxes);
    return /*#__PURE__*/ (0, $dq4Yb$react).createElement((0, $0f1ea4f28e10f05a$export$517e02184d273d69), {
        ...props,
        state: state,
        ref: ref
    });
});


export {$afff8b74e9314c77$export$c488dd5235f7fcac as TableViewWithoutExpanding};
//# sourceMappingURL=TableViewWithoutExpanding.js.map
