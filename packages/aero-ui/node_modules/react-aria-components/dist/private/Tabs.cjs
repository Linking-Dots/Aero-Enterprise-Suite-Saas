var $048d76b84370f141$exports = require("./utils.cjs");
var $f7b82bedbb70abac$exports = require("./Collection.cjs");
var $61557b2a9b2862a8$exports = require("./SelectionIndicator.cjs");
var $9a60bd90621ebc78$exports = require("./SharedElementTransition.cjs");
var $KH96U$reactariauseTabList = require("react-aria/useTabList");
var $KH96U$reactariaCollection = require("react-aria/Collection");
var $KH96U$reactariaCollectionBuilder = require("react-aria/CollectionBuilder");
var $KH96U$reactariaprivatecollectionsBaseCollection = require("react-aria/private/collections/BaseCollection");
var $KH96U$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $KH96U$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $KH96U$reactariaprivateutilsinertValue = require("react-aria/private/utils/inertValue");
var $KH96U$reactariamergeProps = require("react-aria/mergeProps");
var $KH96U$react = require("react");
var $KH96U$reactstatelyuseTabListState = require("react-stately/useTabListState");
var $KH96U$reactariaprivateutilsanimation = require("react-aria/private/utils/animation");
var $KH96U$reactariauseFocusRing = require("react-aria/useFocusRing");
var $KH96U$reactariauseHover = require("react-aria/useHover");
var $KH96U$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $KH96U$reactariauseObjectRef = require("react-aria/useObjectRef");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TabsContext", function () { return $d6999420f0e5d757$export$cfa7aa87c26e7d1f; });
$parcel$export(module.exports, "TabListStateContext", function () { return $d6999420f0e5d757$export$364712098d2aa57c; });
$parcel$export(module.exports, "Tabs", function () { return $d6999420f0e5d757$export$b2539bed5023c21c; });
$parcel$export(module.exports, "TabList", function () { return $d6999420f0e5d757$export$e51a686c67fdaa2d; });
$parcel$export(module.exports, "Tab", function () { return $d6999420f0e5d757$export$3e41faf802a29e71; });
$parcel$export(module.exports, "TabPanels", function () { return $d6999420f0e5d757$export$5dae8d435677f210; });
$parcel$export(module.exports, "TabPanel", function () { return $d6999420f0e5d757$export$3d96ec278d3efce4; });
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


















const $d6999420f0e5d757$export$cfa7aa87c26e7d1f = /*#__PURE__*/ (0, $KH96U$react.createContext)(null);
const $d6999420f0e5d757$export$364712098d2aa57c = /*#__PURE__*/ (0, $KH96U$react.createContext)(null);
const $d6999420f0e5d757$export$b2539bed5023c21c = /*#__PURE__*/ (0, $KH96U$react.forwardRef)(function Tabs(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $d6999420f0e5d757$export$cfa7aa87c26e7d1f);
    let { children: children, orientation: orientation = 'horizontal' } = props;
    children = (0, $KH96U$react.useMemo)(()=>typeof children === 'function' ? children({
            orientation: orientation,
            defaultChildren: null
        }) : children, [
        children,
        orientation
    ]);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $KH96U$reactariaCollectionBuilder.CollectionBuilder), {
        content: children
    }, (collection)=>/*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement($d6999420f0e5d757$var$TabsInner, {
            props: props,
            collection: collection,
            tabsRef: ref
        }));
});
function $d6999420f0e5d757$var$TabsInner({ props: props, tabsRef: ref, collection: collection }) {
    let { orientation: orientation = 'horizontal' } = props;
    let state = (0, $KH96U$reactstatelyuseTabListState.useTabListState)({
        ...props,
        collection: collection,
        children: undefined
    });
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $KH96U$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let values = (0, $KH96U$react.useMemo)(()=>({
            orientation: orientation,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible
        }), [
        orientation,
        isFocused,
        isFocusVisible
    ]);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-Tabs',
        values: values
    });
    let DOMProps = (0, $KH96U$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $KH96U$reactariamergeProps.mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": isFocused || undefined,
        "data-orientation": orientation,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": state.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $d6999420f0e5d757$export$cfa7aa87c26e7d1f,
                props
            ],
            [
                $d6999420f0e5d757$export$364712098d2aa57c,
                state
            ]
        ]
    }, renderProps.children));
}
const $d6999420f0e5d757$export$e51a686c67fdaa2d = /*#__PURE__*/ (0, $KH96U$react.forwardRef)(function TabList(props, ref) {
    let state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    return state ? /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement($d6999420f0e5d757$var$TabListInner, {
        props: props,
        forwardedRef: ref
    }) : /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $KH96U$reactariaCollection.Collection), props);
});
function $d6999420f0e5d757$var$TabListInner({ props: props, forwardedRef: ref }) {
    let state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    let { CollectionRoot: CollectionRoot } = (0, $KH96U$react.useContext)((0, $f7b82bedbb70abac$exports.CollectionRendererContext));
    let { orientation: orientation = 'horizontal', keyboardActivation: keyboardActivation = 'automatic' } = (0, $048d76b84370f141$exports.useSlottedContext)($d6999420f0e5d757$export$cfa7aa87c26e7d1f);
    let objectRef = (0, $KH96U$reactariauseObjectRef.useObjectRef)(ref);
    let { tabListProps: tabListProps } = (0, $KH96U$reactariauseTabList.useTabList)({
        ...props,
        orientation: orientation,
        keyboardActivation: keyboardActivation
    }, state, objectRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        children: null,
        defaultClassName: 'react-aria-TabList',
        values: {
            orientation: orientation,
            state: state
        }
    });
    let DOMProps = (0, $KH96U$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $KH96U$reactariamergeProps.mergeProps)(DOMProps, renderProps, tabListProps),
        ref: objectRef,
        "data-orientation": orientation || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $9a60bd90621ebc78$exports.SharedElementTransition), null, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement(CollectionRoot, {
        collection: state.collection,
        persistedKeys: (0, $f7b82bedbb70abac$exports.usePersistedKeys)(state.selectionManager.focusedKey)
    })));
}
class $d6999420f0e5d757$var$TabItemNode extends (0, $KH96U$reactariaprivatecollectionsBaseCollection.CollectionNode) {
    static{
        this.type = 'item';
    }
}
const $d6999420f0e5d757$export$3e41faf802a29e71 = /*#__PURE__*/ (0, $KH96U$reactariaCollectionBuilder.createLeafComponent)($d6999420f0e5d757$var$TabItemNode, (props, forwardedRef, item)=>{
    let state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    let ref = (0, $KH96U$reactariauseObjectRef.useObjectRef)(forwardedRef);
    let { tabProps: tabProps, isSelected: isSelected, isDisabled: isDisabled, isPressed: isPressed } = (0, $KH96U$reactariauseTabList.useTab)({
        key: item.key,
        ...props
    }, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $KH96U$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $KH96U$reactariauseHover.useHover)({
        isDisabled: isDisabled,
        onHoverStart: props.onHoverStart,
        onHoverEnd: props.onHoverEnd,
        onHoverChange: props.onHoverChange
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        id: undefined,
        children: item.rendered,
        defaultClassName: 'react-aria-Tab',
        values: {
            isSelected: isSelected,
            isDisabled: isDisabled,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isPressed: isPressed,
            isHovered: isHovered
        }
    });
    let ElementType = item.props.href ? (0, $048d76b84370f141$exports.dom).a : (0, $048d76b84370f141$exports.dom).div;
    let DOMProps = (0, $KH96U$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement(ElementType, {
        ...(0, $KH96U$reactariamergeProps.mergeProps)(DOMProps, renderProps, tabProps, focusProps, hoverProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-pressed": isPressed || undefined,
        "data-hovered": isHovered || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $61557b2a9b2862a8$exports.SelectionIndicatorContext).Provider, {
        value: {
            isSelected: isSelected
        }
    }, renderProps.children));
});
const $d6999420f0e5d757$export$5dae8d435677f210 = /*#__PURE__*/ (0, $KH96U$reactariaprivatecollectionsHidden.createHideableComponent)(function TabPanels(props, forwardedRef) {
    let state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    let ref = (0, $KH96U$reactariauseObjectRef.useObjectRef)(forwardedRef);
    let selectedKeyRef = (0, $KH96U$react.useRef)(state.selectedKey);
    let prevSize = (0, $KH96U$react.useRef)(null);
    let hasTransition = (0, $KH96U$react.useRef)(null);
    (0, $KH96U$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        let el = ref.current;
        if (!el) return;
        if (hasTransition.current == null) hasTransition.current = /width|height|block-size|inline-size|all/.test(window.getComputedStyle(el).transition);
        if (hasTransition.current && selectedKeyRef.current != null && selectedKeyRef.current !== state.selectedKey) {
            // Measure auto size.
            el.style.setProperty('--tab-panel-width', 'auto');
            el.style.setProperty('--tab-panel-height', 'auto');
            let { width: width, height: height } = el.getBoundingClientRect();
            if (prevSize.current && (prevSize.current.width !== width || prevSize.current.height !== height)) {
                // Revert to previous size.
                el.style.setProperty('--tab-panel-width', prevSize.current.width + 'px');
                el.style.setProperty('--tab-panel-height', prevSize.current.height + 'px');
                // Force style re-calculation to trigger animations.
                window.getComputedStyle(el).height;
                // Animate to current pixel size.
                el.style.setProperty('--tab-panel-width', width + 'px');
                el.style.setProperty('--tab-panel-height', height + 'px');
                // When animations complete, revert back to auto size.
                Promise.all(el.getAnimations().map((a)=>a.finished)).then(()=>{
                    el.style.setProperty('--tab-panel-width', 'auto');
                    el.style.setProperty('--tab-panel-height', 'auto');
                }).catch(()=>{});
            }
        }
        selectedKeyRef.current = state.selectedKey;
    }, [
        ref,
        state.selectedKey
    ]);
    // Store previous size before DOM updates occur.
    // This breaks the rules of hooks because there is no effect that runs _before_ DOM updates.
    if (state.selectedKey != null && // eslint-disable-next-line rsp-rules/pure-render
    state.selectedKey !== selectedKeyRef.current && ref.current && // eslint-disable-next-line rsp-rules/pure-render
    hasTransition.current) // eslint-disable-next-line rsp-rules/pure-render
    prevSize.current = ref.current.getBoundingClientRect();
    let DOMProps = (0, $KH96U$reactariafilterDOMProps.filterDOMProps)(props, {
        labelable: true,
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: props.style,
        className: props.className || 'react-aria-TabPanels'
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $KH96U$reactariaCollection.Collection), props));
});
const $d6999420f0e5d757$export$3d96ec278d3efce4 = /*#__PURE__*/ (0, $KH96U$reactariaprivatecollectionsHidden.createHideableComponent)(function TabPanel(props, forwardedRef) {
    const state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    let ref = (0, $KH96U$reactariauseObjectRef.useObjectRef)(forwardedRef);
    // Track if the tab panel was initially selected on mount (after extra render to populate the collection).
    // In this case, we don't want to trigger animations.
    let isSelected = state.selectedKey === props.id;
    let [isInitiallySelected, setInitiallySelected] = (0, $KH96U$react.useState)(state.selectedKey != null ? isSelected : null);
    if (isInitiallySelected == null && state.selectedKey != null) setInitiallySelected(isSelected);
    else if (!isSelected && isInitiallySelected) setInitiallySelected(false);
    let isExiting = (0, $KH96U$reactariaprivateutilsanimation.useExitAnimation)(ref, isSelected);
    if (!isSelected && !props.shouldForceMount && !isExiting) return null;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement($d6999420f0e5d757$var$TabPanelInner, {
        ...props,
        tabPanelRef: ref,
        isInitiallySelected: isInitiallySelected || false,
        isExiting: isExiting
    });
});
function $d6999420f0e5d757$var$TabPanelInner(props) {
    let state = (0, $KH96U$react.useContext)($d6999420f0e5d757$export$364712098d2aa57c);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { id: id, tabPanelRef: ref, isInitiallySelected: isInitiallySelected, isExiting: isExiting, ...otherProps } = props;
    let { tabPanelProps: tabPanelProps } = (0, $KH96U$reactariauseTabList.useTabPanel)(props, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $KH96U$reactariauseFocusRing.useFocusRing)();
    let isSelected = state.selectedKey === props.id;
    let isEntering = (0, $KH96U$reactariaprivateutilsanimation.useEnterAnimation)(ref) && !isInitiallySelected;
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-TabPanel',
        values: {
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            // @ts-ignore - compatibility with React < 19
            isInert: (0, $KH96U$reactariaprivateutilsinertValue.inertValue)(!isSelected),
            isEntering: isEntering,
            isExiting: isExiting,
            state: state
        }
    });
    let DOMProps = (0, $KH96U$reactariafilterDOMProps.filterDOMProps)(otherProps, {
        global: true
    });
    delete DOMProps.id;
    let domProps = isSelected ? (0, $KH96U$reactariamergeProps.mergeProps)(DOMProps, tabPanelProps, focusProps, renderProps) : (0, $KH96U$reactariamergeProps.mergeProps)(DOMProps, renderProps);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...domProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        // @ts-ignore
        inert: (0, $KH96U$reactariaprivateutilsinertValue.inertValue)(!isSelected || props.inert),
        "data-inert": !isSelected ? 'true' : undefined,
        "data-entering": isEntering || undefined,
        "data-exiting": isExiting || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $d6999420f0e5d757$export$cfa7aa87c26e7d1f,
                null
            ],
            [
                $d6999420f0e5d757$export$364712098d2aa57c,
                null
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($KH96U$react))).createElement((0, $f7b82bedbb70abac$exports.CollectionRendererContext).Provider, {
        value: (0, $f7b82bedbb70abac$exports.DefaultCollectionRenderer)
    }, renderProps.children)));
}


//# sourceMappingURL=Tabs.cjs.map
