var $4965a9907649f3b8$exports = require("./context.cjs");
var $59Hdo$react = require("react");


function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useDialogContainer", function () { return $66fb7a7755ec9057$export$a2f2d8fa6720dab1; });
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

function $66fb7a7755ec9057$export$a2f2d8fa6720dab1() {
    let context = (0, $59Hdo$react.useContext)((0, $4965a9907649f3b8$exports.DialogContext));
    if (!context) throw new Error('Cannot call useDialogContext outside a <DialogTrigger> or <DialogContainer>.');
    return {
        type: context.type,
        dismiss () {
            context?.onClose();
        }
    };
}


//# sourceMappingURL=useDialogContainer.cjs.map
