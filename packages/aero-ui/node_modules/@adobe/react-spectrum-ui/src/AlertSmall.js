/*
Copyright 2024 Adobe. All rights reserved.
This file is licensed to you under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy
of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under
the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
OF ANY KIND, either express or implied. See the License for the specific language
governing permissions and limitations under the License.
*/
import React from 'react';
export function AlertSmall({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M8.564 1.289L.2 16.256A.5.5 0 0 0 .636 17h16.728a.5.5 0 0 0 .436-.744L9.436 1.289a.5.5 0 0 0-.872 0zM10 14.75a.25.25 0 0 1-.25.25h-1.5a.25.25 0 0 1-.25-.25v-1.5a.25.25 0 0 1 .25-.25h1.5a.25.25 0 0 1 .25.25zm0-3a.25.25 0 0 1-.25.25h-1.5a.25.25 0 0 1-.25-.25v-6a.25.25 0 0 1 .25-.25h1.5a.25.25 0 0 1 .25.25z" />
      )}
      {scale === 'M' && (
        <path d="M6.66 1.003L.157 12.643a.389.389 0 0 0 .339.58h13.01a.389.389 0 0 0 .34-.58L7.338 1.004a.389.389 0 0 0-.678 0zm1.118 10.47a.194.194 0 0 1-.195.194H6.417a.194.194 0 0 1-.195-.195v-1.166a.194.194 0 0 1 .195-.195h1.166a.194.194 0 0 1 .195.195zm0-2.334a.194.194 0 0 1-.195.194H6.417a.194.194 0 0 1-.195-.194V4.472a.194.194 0 0 1 .195-.194h1.166a.194.194 0 0 1 .195.194z" />
      )}
    </svg>
  );
}
AlertSmall.displayName = 'AlertSmall';
