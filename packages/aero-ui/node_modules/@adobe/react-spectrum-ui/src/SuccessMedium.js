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
export function SuccessMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M11 2a9 9 0 1 0 9 9 9 9 0 0 0-9-9zm5.638 5.609L10.1 15.652a.5.5 0 0 1-.742.038L5.086 11.5a.5.5 0 0 1 0-.707l.707-.707a.5.5 0 0 1 .707 0L9.6 13.1l5.486-6.751a.5.5 0 0 1 .7-.073l.776.631a.5.5 0 0 1 .076.702z" />
      )}
      {scale === 'M' && (
        <path d="M9 1a8 8 0 1 0 8 8 8 8 0 0 0-8-8zm5.333 4.54l-6.324 8.13a.6.6 0 0 1-.437.23h-.037a.6.6 0 0 1-.425-.176l-3.893-3.9a.6.6 0 0 1 0-.849l.663-.663a.6.6 0 0 1 .848 0L7.4 10.991l5.256-6.754a.6.6 0 0 1 .843-.1l.728.566a.6.6 0 0 1 .106.837z" />
      )}
    </svg>
  );
}
SuccessMedium.displayName = 'SuccessMedium';
