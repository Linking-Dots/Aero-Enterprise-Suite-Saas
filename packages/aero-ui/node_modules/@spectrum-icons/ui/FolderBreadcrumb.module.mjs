import _extends from "@babel/runtime/helpers/extends";
import { FolderBreadcrumb as IconComponent } from '@adobe/react-spectrum-ui/dist/FolderBreadcrumb.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/React.createElement("circle", {
  cx: 9,
  cy: 9,
  r: 2
}), /*#__PURE__*/React.createElement("circle", {
  cx: 15,
  cy: 9,
  r: 2
}), /*#__PURE__*/React.createElement("circle", {
  cx: 3,
  cy: 9,
  r: 2
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function FolderBreadcrumb(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}