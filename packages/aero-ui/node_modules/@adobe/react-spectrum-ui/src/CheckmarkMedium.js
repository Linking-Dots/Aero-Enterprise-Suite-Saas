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
export function CheckmarkMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M6 14a1 1 0 0 1-.789-.385l-4-5a1 1 0 1 1 1.577-1.23L6 11.376l7.213-8.99a1 1 0 1 1 1.576 1.23l-8 10a1 1 0 0 1-.789.384z" />
      )}
      {scale === 'M' && (
        <path d="M4.5 10a1.022 1.022 0 0 1-.799-.384l-2.488-3a1 1 0 0 1 1.576-1.233L4.5 7.376l4.712-5.991a1 1 0 1 1 1.576 1.23l-5.51 7A.978.978 0 0 1 4.5 10z" />
      )}
    </svg>
  );
}
CheckmarkMedium.displayName = 'CheckmarkMedium';
