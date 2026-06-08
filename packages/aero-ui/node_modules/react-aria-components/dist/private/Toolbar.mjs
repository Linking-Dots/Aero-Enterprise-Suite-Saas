import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {useToolbar as $1ohuH$useToolbar} from "react-aria/useToolbar";
import {filterDOMProps as $1ohuH$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $1ohuH$mergeProps} from "react-aria/mergeProps";
import $1ohuH$react, {createContext as $1ohuH$createContext, forwardRef as $1ohuH$forwardRef} from "react";

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




const $db7105decd081705$export$6311e7ab80ef752f = /*#__PURE__*/ (0, $1ohuH$createContext)({});
const $db7105decd081705$export$4c260019440d418f = /*#__PURE__*/ (0, $1ohuH$forwardRef)(function Toolbar(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $db7105decd081705$export$6311e7ab80ef752f);
    let { toolbarProps: toolbarProps } = (0, $1ohuH$useToolbar)(props, ref);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: props.orientation || 'horizontal'
        },
        defaultClassName: 'react-aria-Toolbar'
    });
    let DOMProps = (0, $1ohuH$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $1ohuH$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $1ohuH$mergeProps)(DOMProps, renderProps, toolbarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'horizontal'
    }, renderProps.children);
});


export {$db7105decd081705$export$6311e7ab80ef752f as ToolbarContext, $db7105decd081705$export$4c260019440d418f as Toolbar};
//# sourceMappingURL=Toolbar.mjs.map
