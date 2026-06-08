import _extends from "@babel/runtime/helpers/extends";
import { InfoMedium as IconComponent } from '@adobe/react-spectrum-ui/dist/InfoMedium.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M9 8a1 1 0 0 0-1 1v4a1 1 0 0 0 2 0V9a1 1 0 0 0-1-1"
}), /*#__PURE__*/React.createElement("circle", {
  cx: 9,
  cy: 5.5,
  r: 1.5
}), /*#__PURE__*/React.createElement("path", {
  d: "M9 0a9 9 0 1 0 9 9 9.01 9.01 0 0 0-9-9m0 16a7 7 0 1 1 7-7 7.01 7.01 0 0 1-7 7"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function InfoMedium(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}