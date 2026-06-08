import {DialogContext as $abd082d14fc11575$export$8b93a07348a7730c} from "./context.js";
import {useContext as $kovTB$useContext} from "react";

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

function $754411bdeab736f8$export$a2f2d8fa6720dab1() {
    let context = (0, $kovTB$useContext)((0, $abd082d14fc11575$export$8b93a07348a7730c));
    if (!context) throw new Error('Cannot call useDialogContext outside a <DialogTrigger> or <DialogContainer>.');
    return {
        type: context.type,
        dismiss () {
            context === null || context === void 0 ? void 0 : context.onClose();
        }
    };
}


export {$754411bdeab736f8$export$a2f2d8fa6720dab1 as useDialogContainer};
//# sourceMappingURL=useDialogContainer.js.map
