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
export function ArrowLeftMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M16.99 6H3.414l4.283-4.283A1 1 0 1 0 6.283.303l-5.98 5.99a1 1 0 0 0 0 1.414l5.98 5.99a1 1 0 1 0 1.414-1.414L3.414 8H16.99a1 1 0 0 0 0-2z" />
      )}
      {scale === 'M' && (
        <path d="M12.99 4H3.414l2.283-2.283A1 1 0 1 0 4.283.303l-3.98 3.99a1 1 0 0 0 0 1.414l3.98 3.99a1 1 0 1 0 1.414-1.414L3.414 6h9.576a1 1 0 1 0 0-2z" />
      )}
    </svg>
  );
}
ArrowLeftMedium.displayName = 'ArrowLeftMedium';
