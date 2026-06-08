import {TableViewBase as $2a45525f66468ec9$export$517e02184d273d69} from "./TableViewBase.mjs";
import $3zi9r$react, {useState as $3zi9r$useState} from "react";
import {useTableState as $3zi9r$useTableState} from "react-stately/useTableState";

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


const $50d34b48263523ea$export$c488dd5235f7fcac = /*#__PURE__*/ (0, $3zi9r$react).forwardRef(function TableView(props, ref) {
    let { selectionStyle: selectionStyle, dragAndDropHooks: dragAndDropHooks } = props;
    let [showSelectionCheckboxes, setShowSelectionCheckboxes] = (0, $3zi9r$useState)(selectionStyle !== 'highlight');
    let isTableDraggable = !!dragAndDropHooks?.useDraggableCollectionState;
    let state = (0, $3zi9r$useTableState)({
        ...props,
        showSelectionCheckboxes: showSelectionCheckboxes,
        showDragButtons: isTableDraggable,
        selectionBehavior: props.selectionStyle === 'highlight' ? 'replace' : 'toggle'
    });
    // If the selection behavior changes in state, we need to update showSelectionCheckboxes here due to the circular dependency...
    let shouldShowCheckboxes = state.selectionManager.selectionBehavior !== 'replace';
    if (shouldShowCheckboxes !== showSelectionCheckboxes) setShowSelectionCheckboxes(shouldShowCheckboxes);
    return /*#__PURE__*/ (0, $3zi9r$react).createElement((0, $2a45525f66468ec9$export$517e02184d273d69), {
        ...props,
        state: state,
        ref: ref
    });
});


export {$50d34b48263523ea$export$c488dd5235f7fcac as TableViewWithoutExpanding};
//# sourceMappingURL=TableViewWithoutExpanding.mjs.map
