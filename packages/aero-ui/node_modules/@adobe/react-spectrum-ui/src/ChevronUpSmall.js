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
export function ChevronUpSmall({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M5 1a.747.747 0 0 0-.53.22L.23 5.46a.75.75 0 1 0 1.06 1.06L5 2.81l3.71 3.71a.75.75 0 1 0 1.06-1.06L5.53 1.22A.747.747 0 0 0 5 1z" />
      )}
      {scale === 'M' && (
        <path d="M4 .5a.747.747 0 0 0-.53.22C2.862 1.297 1.5 2.758.23 3.96a.75.75 0 1 0 1.06 1.06L4 2.31l2.71 2.71a.75.75 0 1 0 1.06-1.06L4.53.72A.747.747 0 0 0 4 .5z" />
      )}
    </svg>
  );
}
ChevronUpSmall.displayName = 'ChevronUpSmall';
