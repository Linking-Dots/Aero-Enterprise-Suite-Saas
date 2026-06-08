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
export function ChevronDownSmall({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M5 7a.747.747 0 0 0 .53-.22l4.24-4.24a.75.75 0 1 0-1.06-1.06L5 5.19 1.29 1.48A.75.75 0 1 0 .23 2.54l4.24 4.24A.747.747 0 0 0 5 7z" />
      )}
      {scale === 'M' && (
        <path d="M4 5.5a.747.747 0 0 0 .53-.22c.607-.577 1.97-2.038 3.24-3.24A.75.75 0 1 0 6.71.98L4 3.69 1.29.98A.75.75 0 1 0 .23 2.04l3.24 3.24A.747.747 0 0 0 4 5.5z" />
      )}
    </svg>
  );
}
ChevronDownSmall.displayName = 'ChevronDownSmall';
