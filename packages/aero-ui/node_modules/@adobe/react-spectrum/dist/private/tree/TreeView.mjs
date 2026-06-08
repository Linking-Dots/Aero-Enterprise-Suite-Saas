import "./TreeView.css";
import {Checkbox as $b50e47f9c64ebdde$export$48513f6b9f8ce62d} from "../checkbox/Checkbox.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {ButtonContext as $ceFJu$ButtonContext} from "react-aria-components/Button";
import $ceFJu$spectrumiconsuiChevronLeftMedium from "@spectrum-icons/ui/ChevronLeftMedium";
import $ceFJu$spectrumiconsuiChevronRightMedium from "@spectrum-icons/ui/ChevronRightMedium";
import {isAndroid as $ceFJu$isAndroid} from "react-aria/private/utils/platform";
import $ceFJu$react, {createContext as $ceFJu$createContext, useRef as $ceFJu$useRef} from "react";
import {Tree as $ceFJu$Tree, TreeItem as $ceFJu$TreeItem, TreeItemContent as $ceFJu$TreeItemContent} from "react-aria-components/Tree";
import {useButton as $ceFJu$useButton} from "react-aria/useButton";
import {useContextProps as $ceFJu$useContextProps} from "react-aria-components/slots";
import {useLocale as $ceFJu$useLocale} from "react-aria/I18nProvider";

/*
 * Copyright 2024 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 












const $85896c343d88fa63$var$TreeRendererContext = /*#__PURE__*/ (0, $ceFJu$createContext)({});
// TODO: add animations for rows appearing and disappearing
// TODO: the below is needed so the borders of the top and bottom row isn't cut off if the TreeView is wrapped within a container by always reserving the 2px needed for the
// keyboard focus ring. Perhaps find a different way of rendering the outlines since the top of the item doesn't
// scroll into view due to how the ring is offset. Alternatively, have the tree render the top/bottom outline like it does in Listview
const $85896c343d88fa63$var$tree = function anonymous(props) {
    let rules = "";
    if (props.isFocusVisible) rules += ' s1-_Fs1-b';
    else rules += ' s1-_Fs1-a';
    rules += ' s1-ds1-as1-___D';
    rules += ' s1-ds1-___I';
    rules += ' s1-_Hs1-c';
    rules += ' s1-_G-yj8a3w';
    rules += ' s1-ns1-Y';
    rules += ' s1-os1-Y';
    rules += ' s1-ws1-c';
    rules += ' s1-xs1-c';
    rules += ' s1-us1-a';
    rules += ' s1-vs1-a';
    rules += ' s1-__ls1-a';
    rules += ' s1-As1-a';
    rules += ' s1-cs1-as1-___y';
    rules += ' s1-cs1-a';
    if (props.isEmpty) rules += ' s1-_Ws1-d';
    if (props.isEmpty) rules += ' s1-_Vs1-c';
    if (props.isEmpty) rules += ' s1-_Ts1-d';
    rules += ' s1-__ts1-a';
    rules += ' s1-__us1-a';
    return rules;
};
const $85896c343d88fa63$export$6940b0d9c820eca7 = /*#__PURE__*/ (0, $ceFJu$react).forwardRef(function TreeView(props, ref) {
    let { children: children, selectionStyle: selectionStyle, UNSAFE_className: UNSAFE_className } = props;
    let renderer;
    if (typeof children === 'function') renderer = children;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let selectionBehavior = selectionStyle === 'highlight' ? 'replace' : 'toggle';
    return /*#__PURE__*/ (0, $ceFJu$react).createElement($85896c343d88fa63$var$TreeRendererContext.Provider, {
        value: {
            renderer: renderer
        }
    }, /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $ceFJu$Tree), {
        ...props,
        ...styleProps,
        className: (renderProps)=>(UNSAFE_className ?? '') + $85896c343d88fa63$var$tree(renderProps),
        selectionBehavior: selectionBehavior,
        ref: domRef
    }, props.children));
});
const $85896c343d88fa63$var$treeRow = function anonymous(props) {
    let rules = "";
    rules += ' s1-Xs1-c';
    rules += ' s1-_Ts1-d';
    rules += ' s1-ns1-k';
    rules += ' s1-os1-Y';
    rules += ' s1-__ls1-a';
    rules += ' s1-6s1-c';
    rules += ' s1-7s1-d';
    rules += ' s1-9s1-b';
    rules += ' s1-as1-___K';
    rules += ' s1-_Fs1-a';
    if (props.isLink) rules += ' s1-__Fs1-c';
    else rules += ' s1-__Fs1-b';
    if (props.isSelected) rules += ' s1-b-7vr0l1';
    else if (props.isPressed) rules += ' s1-b-1t6gvb8';
    else if (props.isFocusVisibleWithin) rules += ' s1-b-ml9cvk';
    else if (props.isHovered) rules += ' s1-b-ml9cvk';
    return rules;
};
const $85896c343d88fa63$var$treeCellGrid = function anonymous(props) {
    let rules = "";
    rules += ' s1-_Ts1-f';
    rules += ' s1-os1-Y';
    rules += ' s1-_Vs1-c';
    rules += ' s1-__e-1nxidkl-1nxidkl-1nxidkl-3hmti-1nxidkl-375yi6-1nxidkl-ykdwf2';
    rules += ' s1-__f-375yi6';
    rules += ' s1-__g-1qk85yo';
    if (props.isDisabled) {
        rules += ' s1-as1-as1-___F';
        rules += ' s1-as1-i';
    }
    return rules;
};
// TODO: These styles lose against the spectrum class names, so I've did unsafe for the ones that get overridden
const $85896c343d88fa63$var$treeCheckbox = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-4wahvw';
    rules += ' s1-_K-37nn5o';
    rules += ' s1-Cs1-d';
    rules += ' s1-Ds1-a';
    return rules;
};
const $85896c343d88fa63$var$treeIcon = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-ykjmzy';
    rules += ' s1-Ds1-c';
    return rules;
};
const $85896c343d88fa63$var$treeContent = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-1mod4sg';
    rules += ' s1-_gs1-a';
    rules += ' s1-_ks1-b';
    rules += ' s1-__ts1-b';
    rules += ' s1-__us1-b';
    return rules;
};
const $85896c343d88fa63$var$treeActions = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-8ayfo6';
    rules += ' s1-_4-3t1x';
    rules += ' s1-_3-3t1x';
    rules += ' s1-Cs1-F';
    rules += ' s1-Ds1-b';
    return rules;
};
const $85896c343d88fa63$var$treeActionMenu = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-wit6hk';
    rules += ' s1-os1-i';
    return rules;
};
const $85896c343d88fa63$var$treeRowOutline = function anonymous(props) {
    let rules = "";
    rules += ' s1-_Ts1-a';
    rules += ' s1-Xs1-a';
    rules += ' s1-Ys1-a';
    rules += ' s1-Zs1-a';
    if (props.isFirst) rules += ' s1-0s1-a';
    else if (props.isSelected) {
        if (props.isFocusVisible) rules += ' s1-0-yj8a3w';
        else rules += ' s1-0-yj899n';
    } else if (props.isFocusVisible) rules += ' s1-0-yj8a3w';
    else rules += ' s1-0s1-a';
    rules += ' s1-2s1-a';
    rules += ' s1-__zs1-a';
    rules += ' s1-_us1-b';
    if (props.isSelected) {
        if (props.isFocusVisible) rules += ' s1-_ps1-a-4bhpmf';
        else rules += ' s1-_ps1-a-zlnqab';
    } else if (props.isFocusVisible) rules += ' s1-_ps1-a-4bhpmf';
    if (props.isSelected) {
        if (props.isFocusVisible) rules += ' s1-_p-ba5uxf';
        else rules += ' s1-_p-1ytnijz';
    } else if (props.isFocusVisible) rules += ' s1-_p-ba5uxf';
    return rules;
};
const $85896c343d88fa63$export$6e77ea6719814e9c = (props)=>{
    let { href: href } = props;
    return /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $ceFJu$TreeItem), {
        ...props,
        className: (renderProps)=>$85896c343d88fa63$var$treeRow({
                ...renderProps,
                isLink: !!href
            })
    });
};
const $85896c343d88fa63$export$9a5779ed3fade674 = (props)=>{
    let { children: children } = props;
    return /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $ceFJu$TreeItemContent), null, ({ isExpanded: isExpanded, hasChildItems: hasChildItems, level: level, selectionMode: selectionMode, selectionBehavior: selectionBehavior, isDisabled: isDisabled, isSelected: isSelected, isFocusVisible: isFocusVisible, state: state, id: id })=>{
        let isFirst = state.collection.getFirstKey() === id;
        return /*#__PURE__*/ (0, $ceFJu$react).createElement("div", {
            className: $85896c343d88fa63$var$treeCellGrid({
                isDisabled: isDisabled
            })
        }, selectionMode !== 'none' && selectionBehavior === 'toggle' && // TODO: add transition?
        /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $b50e47f9c64ebdde$export$48513f6b9f8ce62d), {
            isEmphasized: true,
            UNSAFE_className: $85896c343d88fa63$var$treeCheckbox(),
            UNSAFE_style: {
                paddingInlineEnd: '0px'
            },
            slot: "selection"
        }), /*#__PURE__*/ (0, $ceFJu$react).createElement("div", {
            style: {
                gridArea: 'level-padding',
                marginInlineEnd: `calc(${level - 1} * var(--spectrum-global-dimension-size-200))`
            }
        }), hasChildItems && /*#__PURE__*/ (0, $ceFJu$react).createElement($85896c343d88fa63$var$ExpandableRowChevron, {
            isDisabled: isDisabled,
            isExpanded: isExpanded
        }), /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
            slots: {
                text: {
                    UNSAFE_className: $85896c343d88fa63$var$treeContent({
                        isDisabled: isDisabled
                    })
                },
                // Note there is also an issue here where these icon props are making into the action menu's icon. Resolved by 8ab0ffb276ff437a65b365c9a3be0323a1b24656
                // but could crop up later for other components
                icon: {
                    UNSAFE_className: $85896c343d88fa63$var$treeIcon(),
                    size: 'S'
                },
                actionButton: {
                    UNSAFE_className: $85896c343d88fa63$var$treeActions(),
                    isQuiet: true
                },
                actionGroup: {
                    UNSAFE_className: $85896c343d88fa63$var$treeActions(),
                    isQuiet: true,
                    density: 'compact',
                    buttonLabelBehavior: 'hide',
                    isDisabled: isDisabled,
                    overflowMode: 'collapse'
                },
                actionMenu: {
                    UNSAFE_className: $85896c343d88fa63$var$treeActionMenu(),
                    UNSAFE_style: {
                        marginInlineEnd: '.5rem'
                    },
                    isQuiet: true
                }
            }
        }, children), /*#__PURE__*/ (0, $ceFJu$react).createElement("div", {
            className: $85896c343d88fa63$var$treeRowOutline({
                isFocusVisible: isFocusVisible,
                isSelected: isSelected,
                isFirst: isFirst
            })
        }));
    });
};
const $85896c343d88fa63$var$expandButton = function anonymous(props) {
    let rules = "";
    rules += ' s1-__h-pn4rxq';
    rules += ' s1-ns1-Y';
    rules += ' s1-4s1-b';
    rules += ' s1-_Ts1-d';
    rules += ' s1-_1s1-a';
    rules += ' s1-_Us1-b';
    rules += ' s1-_Ws1-d';
    rules += ' s1-_Fs1-a';
    if (props.isExpanded) {
        if (props.isRTL) rules += ' s1-W-negfvv';
        else rules += ' s1-W-10b8jr2';
    }
    rules += ' s1-_I-1o2fh9e';
    return rules;
};
function $85896c343d88fa63$var$ExpandableRowChevron(props) {
    let expandButtonRef = (0, $ceFJu$useRef)(null);
    let [fullProps, ref] = (0, $ceFJu$useContextProps)({
        ...props,
        slot: 'chevron'
    }, expandButtonRef, (0, $ceFJu$ButtonContext));
    let { isExpanded: isExpanded, isDisabled: isDisabled } = fullProps;
    let { direction: direction } = (0, $ceFJu$useLocale)();
    // Will need to keep the chevron as a button for iOS VO at all times since VO doesn't focus the cell. Also keep as button if cellAction is defined by the user in the future
    let { buttonProps: buttonProps } = (0, $ceFJu$useButton)({
        ...fullProps,
        elementType: 'span'
    }, ref);
    return /*#__PURE__*/ (0, $ceFJu$react).createElement("span", {
        ...buttonProps,
        ref: ref,
        // Override tabindex so that grid keyboard nav skips over it. Needs -1 so android talkback can actually "focus" it
        tabIndex: (0, $ceFJu$isAndroid)() && !isDisabled ? -1 : undefined,
        className: $85896c343d88fa63$var$expandButton({
            isExpanded: isExpanded,
            isDisabled: isDisabled,
            isRTL: direction === 'rtl'
        })
    }, direction === 'ltr' ? /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $ceFJu$spectrumiconsuiChevronRightMedium), null) : /*#__PURE__*/ (0, $ceFJu$react).createElement((0, $ceFJu$spectrumiconsuiChevronLeftMedium), null));
}


export {$85896c343d88fa63$export$6940b0d9c820eca7 as TreeView, $85896c343d88fa63$export$6e77ea6719814e9c as TreeViewItem, $85896c343d88fa63$export$9a5779ed3fade674 as TreeViewItemContent};
//# sourceMappingURL=TreeView.mjs.map
