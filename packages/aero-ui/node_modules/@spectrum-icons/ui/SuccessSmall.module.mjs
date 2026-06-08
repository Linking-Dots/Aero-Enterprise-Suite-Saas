import _extends from "@babel/runtime/helpers/extends";
import { SuccessSmall as IconComponent } from '@adobe/react-spectrum-ui/dist/SuccessSmall.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M11.523 3.057a6 6 0 1 0-9.046 7.887 6 6 0 0 0 9.046-7.887m-1.166 2.275L7.075 9.696a.87.87 0 0 1-.617.342l-.076.004a.86.86 0 0 1-.592-.235L3.55 7.704c-.17-.159-.266-.373-.274-.604s.077-.453.235-.621a.87.87 0 0 1 1.225-.04L6.27 7.881 8.973 4.29a.86.86 0 0 1 .693-.346.867.867 0 0 1 .692 1.388"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function SuccessSmall(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}