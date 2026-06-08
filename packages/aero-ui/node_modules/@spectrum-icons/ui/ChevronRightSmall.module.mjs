import _extends from "@babel/runtime/helpers/extends";
import { ChevronRightSmall as IconComponent } from '@adobe/react-spectrum-ui/dist/ChevronRightSmall.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M7.707 4.293a1 1 0 1 0-1.414 1.414L9.586 9l-3.293 3.293a1 1 0 1 0 1.414 1.414l4-4a1 1 0 0 0 0-1.414z"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function ChevronRightSmall(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}