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
export function SkipLeft({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <>
          <path d="M9.697 10.283L5.414 6l4.283-4.283A1 1 0 1 0 8.283.303l-4.99 4.99a1 1 0 0 0 0 1.414l4.99 4.99a1 1 0 1 0 1.414-1.414z" />
          <path d="M1 .01a1 1 0 0 0-1 1v9.98a1 1 0 0 0 2 0V1.01a1 1 0 0 0-1-1z" />
        </>
      )}
      {scale === 'M' && (
        <>
          <path d="M8.697 8.283L5.414 5l3.283-3.283A1 1 0 1 0 7.283.303l-3.99 3.99a1 1 0 0 0 0 1.414l3.99 3.99a1 1 0 1 0 1.414-1.414z" />
          <path d="M1 .01a1 1 0 0 0-1 1v7.98a1 1 0 0 0 2 0V1.01a1 1 0 0 0-1-1z" />
        </>
      )}
    </svg>
  );
}
SkipLeft.displayName = 'SkipLeft';
