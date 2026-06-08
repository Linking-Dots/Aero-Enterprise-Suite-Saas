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
export function ChevronLeftLarge({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M12.109 17.853l-8.066-7.849 8.066-7.84a1.243 1.243 0 0 0 .381-.894 1.24 1.24 0 0 0-2.12-.894L1.379 9.108a1.246 1.246 0 0 0 .003 1.79l8.99 8.744a1.247 1.247 0 1 0 1.738-1.789z" />
      )}
      {scale === 'M' && (
        <path d="M9.605 13.843L3.55 8l6.056-5.84A1.248 1.248 0 1 0 7.876.363L.882 7.1a1.243 1.243 0 0 0 .003 1.797l6.988 6.742a1.248 1.248 0 1 0 1.732-1.796z" />
      )}
    </svg>
  );
}
ChevronLeftLarge.displayName = 'ChevronLeftLarge';
