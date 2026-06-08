import _extends from "@babel/runtime/helpers/extends";
import { SuccessMedium as IconComponent } from '@adobe/react-spectrum-ui/dist/SuccessMedium.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M9.014 18.001a10 10 0 0 1-.628-.021 9 9 0 0 1-5.3-15.764 9 9 0 0 1 11.829 13.568 8.93 8.93 0 0 1-5.901 2.217M8.99 1.999a7 7 0 0 0-5.265 11.6A7 7 0 1 0 14.276 4.4 6.95 6.95 0 0 0 9.48 2.017a7 7 0 0 0-.489-.018m-.83 11.26a1 1 0 0 0 .713-.395l4.924-6.546a1 1 0 0 0-1.598-1.203l-4.254 5.656L5.4 8.379A1 1 0 0 0 4.03 9.838l3.36 3.154a1 1 0 0 0 .684.27 1 1 0 0 0 .086-.003"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function SuccessMedium(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}