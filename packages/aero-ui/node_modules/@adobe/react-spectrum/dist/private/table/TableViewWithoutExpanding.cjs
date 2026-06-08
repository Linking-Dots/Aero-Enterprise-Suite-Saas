var $783600b7b364e0bf$exports = require("./TableViewBase.cjs");
var $c3W0l$react = require("react");
var $c3W0l$reactstatelyuseTableState = require("react-stately/useTableState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TableViewWithoutExpanding", function () { return $cec3453498a09f0b$export$c488dd5235f7fcac; });
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


const $cec3453498a09f0b$export$c488dd5235f7fcac = /*#__PURE__*/ (0, ($parcel$interopDefault($c3W0l$react))).forwardRef(function TableView(props, ref) {
    let { selectionStyle: selectionStyle, dragAndDropHooks: dragAndDropHooks } = props;
    let [showSelectionCheckboxes, setShowSelectionCheckboxes] = (0, $c3W0l$react.useState)(selectionStyle !== 'highlight');
    let isTableDraggable = !!dragAndDropHooks?.useDraggableCollectionState;
    let state = (0, $c3W0l$reactstatelyuseTableState.useTableState)({
        ...props,
        showSelectionCheckboxes: showSelectionCheckboxes,
        showDragButtons: isTableDraggable,
        selectionBehavior: props.selectionStyle === 'highlight' ? 'replace' : 'toggle'
    });
    // If the selection behavior changes in state, we need to update showSelectionCheckboxes here due to the circular dependency...
    let shouldShowCheckboxes = state.selectionManager.selectionBehavior !== 'replace';
    if (shouldShowCheckboxes !== showSelectionCheckboxes) setShowSelectionCheckboxes(shouldShowCheckboxes);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($c3W0l$react))).createElement((0, $783600b7b364e0bf$exports.TableViewBase), {
        ...props,
        state: state,
        ref: ref
    });
});


//# sourceMappingURL=TableViewWithoutExpanding.cjs.map
