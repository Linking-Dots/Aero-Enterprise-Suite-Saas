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
export function Magnifier({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M19.77 18.71l-5.464-5.464a7.503 7.503 0 1 0-1.06 1.06l5.463 5.464a.75.75 0 1 0 1.061-1.06zM2.5 8.5a6 6 0 1 1 6 6 6.007 6.007 0 0 1-6-6z" />
      )}
      {scale === 'M' && (
        <path d="M15.77 14.71l-4.534-4.535a6.014 6.014 0 1 0-1.06 1.06l4.533 4.535a.75.75 0 1 0 1.061-1.06zM6.5 11A4.5 4.5 0 1 1 11 6.5 4.505 4.505 0 0 1 6.5 11z" />
      )}
    </svg>
  );
}
Magnifier.displayName = 'Magnifier';
