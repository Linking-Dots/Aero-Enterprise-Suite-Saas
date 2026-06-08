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
export function ChevronLeftMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M7.197 10.283L2.914 6l4.283-4.283A1 1 0 1 0 5.783.303l-4.99 4.99a1 1 0 0 0 0 1.414l4.99 4.99a1 1 0 1 0 1.414-1.414z" />
      )}
      {scale === 'M' && (
        <path d="M5.697 8.283L2.414 5l3.283-3.283A1 1 0 1 0 4.283.303l-3.98 3.99a1 1 0 0 0 0 1.414l3.98 3.99a1 1 0 1 0 1.414-1.414z" />
      )}
    </svg>
  );
}
ChevronLeftMedium.displayName = 'ChevronLeftMedium';
