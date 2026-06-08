var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $5b544f66bba773fe$exports = require("./intlStrings.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../dropzone_vars.css");
var $7c0f6037b8465949$exports = require("../dropzone_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $jUOzR$reactariacomponentsDropZone = require("react-aria-components/DropZone");
var $jUOzR$reactariacomponentsHeading = require("react-aria-components/Heading");
var $jUOzR$reactariamergeProps = require("react-aria/mergeProps");
var $jUOzR$reactariacomponentsslots = require("react-aria-components/slots");
var $jUOzR$react = require("react");
var $jUOzR$reactariauseId = require("react-aria/useId");
var $jUOzR$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DropZone", function () { return $046693129cf6aded$export$3c6489d84dc98b6; });
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












// Filter out props used by RAC DropZone that we don't want in RSP DropZone for now.
let $046693129cf6aded$var$filterProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $046693129cf6aded$export$3c6489d84dc98b6 = /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).forwardRef(function DropZone(props, ref) {
    let { children: children, isFilled: isFilled, replaceMessage: replaceMessage, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let messageId = (0, $jUOzR$reactariauseId.useId)();
    let headingId = (0, $jUOzR$reactariauseId.useId)();
    let stringFormatter = (0, $jUOzR$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($5b544f66bba773fe$exports))), '@react-spectrum/dropzone');
    let ariaLabelledby = isFilled ? `${headingId} ${messageId}` : headingId;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).createElement((0, $jUOzR$reactariacomponentsslots.Provider), {
        values: [
            [
                (0, $jUOzR$reactariacomponentsHeading.HeadingContext),
                {
                    id: headingId
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).createElement((0, $jUOzR$reactariacomponentsDropZone.DropZone), {
        ...(0, $jUOzR$reactariamergeProps.mergeProps)($046693129cf6aded$var$filterProps(otherProps)),
        ...styleProps,
        "aria-labelledby": ariaLabelledby,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7c0f6037b8465949$exports))), 'spectrum-Dropzone', styleProps.className, {
            'spectrum-Dropzone--filled': isFilled
        }),
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            illustration: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7c0f6037b8465949$exports))), 'spectrum-Dropzone-illustratedMessage')
            }
        }
    }, children), /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7c0f6037b8465949$exports))), 'spectrum-Dropzone-backdrop')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($jUOzR$react))).createElement("div", {
        id: messageId,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($7c0f6037b8465949$exports))), 'spectrum-Dropzone-banner', styleProps.className)
    }, replaceMessage ? replaceMessage : stringFormatter.format('replaceMessage'))));
});


//# sourceMappingURL=DropZone.cjs.map
