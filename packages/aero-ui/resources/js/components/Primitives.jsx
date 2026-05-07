/**
 * AEOS UI — Primitive Components
 * -----------------------------------------------------------------------
 * Low-level building blocks for the AEOS design system.
 * All static styles come from CSS classes defined in app.css.
 * Inline styles are used ONLY for dynamic, prop-driven values
 * (spacing tokens, accent overrides, computed widths).
 *
 * Exports:
 *   cx            — lightweight class-name joiner (no runtime dependency)
 *   Box           — polymorphic layout container
 *   Stack         — vertical flex stack (alias for VStack)
 *   HStack        — horizontal flex row
 *   VStack        — vertical flex column
 *   Spacer        — fixed-size whitespace
 *   Flex1         — flex: 1 spacer (pushes siblings to edges)
 *   Divider       — horizontal or vertical rule
 *   Heading       — h1–h6 with AEOS type scale
 *   Text          — body text with tone + size modifiers
 *   Label         — form label
 *   Mono          — monospace inline span
 *   Eyebrow       — uppercase mono overline
 *   Card          — surface container with 10 visual variants
 *   CardHeader    — standardised card header region
 *   CardBody      — card body wrapper
 *   CardFooter    — card footer with alignment control
 * -----------------------------------------------------------------------
 */

import { forwardRef, createElement, useRef, useCallback } from 'react';

/* ════════════════════════════════════════════════════════════════════
   UTILITIES
   ════════════════════════════════════════════════════════════════════ */

/**
 * `cx` — join class name arguments, filtering falsy values.
 * Accepts strings, arrays, and conditional objects { [cls]: boolean }.
 *
 * @param {...(string|string[]|Record<string,boolean>|null|undefined|false)} args
 * @returns {string}
 *
 * @example
 * cx('foo', condition && 'bar', ['baz', 'qux'])  // 'foo bar baz qux'
 */
export function cx(...args) {
  const classes = [];

  for (const arg of args) {
    if (!arg && arg !== 0) continue;

    if (typeof arg === 'string') {
      classes.push(arg);
    } else if (Array.isArray(arg)) {
      const inner = cx(...arg);
      if (inner) classes.push(inner);
    } else if (typeof arg === 'object') {
      for (const [key, val] of Object.entries(arg)) {
        if (val) classes.push(key);
      }
    }
  }

  return classes.join(' ');
}

/* ════════════════════════════════════════════════════════════════════
   SPACING TOKEN RESOLVER
   ════════════════════════════════════════════════════════════════════ */

/**
 * Accepted spacing scale keys → their CSS custom property names.
 * A numeric key that matches the scale (1,2,3,4,6,8,12,16,24) is
 * converted to a `var(--aeos-space-N)` reference.
 * Any other value (e.g. '2rem', '0', 'auto') is passed through raw.
 */
const SPACE_SCALE = new Set([0, 0.5, 1, 1.5, 2, 3, 4, 5, 6, 8, 10, 12, 16, 20, 24]);

/**
 * `resolveSpace` — convert a spacing prop value to a CSS string.
 *
 * Fractional keys (0.5, 1.5) map to `--aeos-space-0-5`, `--aeos-space-1-5`.
 * `'px'` maps to `--aeos-space-px`.
 *
 * @param {number|string|undefined} value
 * @returns {string|undefined}
 */
function resolveSpace(value) {
  if (value === undefined || value === null) return undefined;
  if (value === 'px') return 'var(--aeos-space-px)';
  const num = Number(value);
  if (!Number.isNaN(num) && SPACE_SCALE.has(num)) {
    if (num === 0) return '0';
    const key = String(num).replace('.', '-');
    return `var(--aeos-space-${key})`;
  }
  if (value === 0 || value === '0') return '0';
  return String(value);
}

/* ════════════════════════════════════════════════════════════════════
   BOX — polymorphic container
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Box` — polymorphic layout container that maps layout props to inline styles.
 *
 * The `as` prop controls which HTML element (or component) is rendered.
 * All unrecognised props are forwarded to the underlying element.
 *
 * @param {object}           props
 * @param {React.ElementType} [props.as='div']   - Element type to render
 * @param {string}           [props.className]
 * @param {number|string}    [props.p]           - Padding (space scale or raw CSS)
 * @param {number|string}    [props.px]          - Padding left/right
 * @param {number|string}    [props.py]          - Padding top/bottom
 * @param {number|string}    [props.m]           - Margin
 * @param {number|string}    [props.mx]          - Margin left/right
 * @param {number|string}    [props.my]          - Margin top/bottom
 * @param {number|string}    [props.gap]         - Gap (flex/grid)
 * @param {boolean}          [props.flex]        - display: flex
 * @param {boolean}          [props.grid]        - display: grid
 * @param {number|string}    [props.cols]        - grid-template-columns
 * @param {number|string}    [props.rows]        - grid-template-rows
 * @param {string}           [props.align]       - align-items
 * @param {string}           [props.justify]     - justify-content
 * @param {string}           [props.dir]         - flex-direction
 * @param {string}           [props.wrap]        - flex-wrap
 * @param {boolean|number}   [props.grow]        - flex-grow
 * @param {string}           [props.bg]          - background
 * @param {string}           [props.color]       - color
 * @param {string}           [props.radius]      - border-radius
 * @param {string}           [props.border]      - border
 * @param {string}           [props.shadow]      - box-shadow
 * @param {string}           [props.w]           - width
 * @param {string}           [props.h]           - height
 * @param {string}           [props.minH]        - min-height
 * @param {string}           [props.maxW]        - max-width
 * @param {React.CSSProperties} [props.style]   - additional inline styles
 */
export const Box = forwardRef(function Box(
  {
    as: Tag = 'div',
    className,
    // Spacing props
    p, px, py, m, mx, my, gap,
    // Layout props
    flex, grid, cols, rows,
    align, justify, dir, wrap, grow,
    // Visual props
    bg, color, radius, border, shadow,
    // Dimension props
    w, h, minH, maxW,
    // Passthrough
    style,
    children,
    ...rest
  },
  ref
) {
  const computedStyle = {};

  // Display
  if (flex) computedStyle.display = 'flex';
  if (grid) computedStyle.display = 'grid';

  // Spacing
  const pVal  = resolveSpace(p);
  const pxVal = resolveSpace(px);
  const pyVal = resolveSpace(py);

  if (pVal)  computedStyle.padding = pVal;
  if (pxVal) { computedStyle.paddingLeft = pxVal; computedStyle.paddingRight = pxVal; }
  if (pyVal) { computedStyle.paddingTop  = pyVal; computedStyle.paddingBottom = pyVal; }

  const mVal  = resolveSpace(m);
  const mxVal = resolveSpace(mx);
  const myVal = resolveSpace(my);

  if (mVal)  computedStyle.margin = mVal;
  if (mxVal) { computedStyle.marginLeft = mxVal; computedStyle.marginRight = mxVal; }
  if (myVal) { computedStyle.marginTop  = myVal; computedStyle.marginBottom = myVal; }

  const gapVal = resolveSpace(gap);
  if (gapVal) computedStyle.gap = gapVal;

  // Flex / Grid layout
  if (align)   computedStyle.alignItems     = align;
  if (justify) computedStyle.justifyContent = justify;
  if (dir)     computedStyle.flexDirection  = dir;
  if (wrap)    computedStyle.flexWrap       = wrap;
  if (grow !== undefined) computedStyle.flexGrow = grow === true ? 1 : grow;

  // Grid columns/rows
  if (cols !== undefined) {
    computedStyle.gridTemplateColumns =
      typeof cols === 'number'
        ? `repeat(${cols}, 1fr)`
        : String(cols);
  }
  if (rows !== undefined) {
    computedStyle.gridTemplateRows =
      typeof rows === 'number'
        ? `repeat(${rows}, 1fr)`
        : String(rows);
  }

  // Visual
  if (bg)     computedStyle.background   = bg;
  if (color)  computedStyle.color        = color;
  if (radius) computedStyle.borderRadius = radius;
  if (border) computedStyle.border       = border;
  if (shadow) computedStyle.boxShadow    = shadow;

  // Dimensions
  if (w)    computedStyle.width     = w;
  if (h)    computedStyle.height    = h;
  if (minH) computedStyle.minHeight = minH;
  if (maxW) computedStyle.maxWidth  = maxW;

  return createElement(Tag, {
    ref,
    className: cx(className),
    style: Object.keys(computedStyle).length > 0
      ? { ...computedStyle, ...style }
      : style,
    ...rest,
  }, children);
});

/* ════════════════════════════════════════════════════════════════════
   STACK VARIANTS
   ════════════════════════════════════════════════════════════════════ */

/**
 * `VStack` — vertical flex column with optional gap.
 *
 * @param {object}         props
 * @param {number|string}  [props.gap=4] - Space between children
 * @param {string}         [props.align='stretch'] - align-items
 */
export const VStack = forwardRef(function VStack(
  { gap = 4, align = 'stretch', style, className, children, ...rest },
  ref
) {
  return (
    <Box
      ref={ref}
      flex
      dir="column"
      gap={gap}
      align={align}
      className={cx(className)}
      style={style}
      {...rest}
    >
      {children}
    </Box>
  );
});

/**
 * `HStack` — horizontal flex row with optional gap.
 *
 * @param {object}         props
 * @param {number|string}  [props.gap=4]        - Space between children
 * @param {string}         [props.align='center'] - align-items
 * @param {string}         [props.wrap]           - flex-wrap value
 */
export const HStack = forwardRef(function HStack(
  { gap = 4, align = 'center', wrap, style, className, children, ...rest },
  ref
) {
  return (
    <Box
      ref={ref}
      flex
      dir="row"
      gap={gap}
      align={align}
      wrap={wrap}
      className={cx(className)}
      style={style}
      {...rest}
    >
      {children}
    </Box>
  );
});

/**
 * `Stack` — alias for `VStack` (vertical flex column).
 */
export const Stack = VStack;

/* ════════════════════════════════════════════════════════════════════
   SPACER + FLEX1
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Spacer` — renders an invisible block of fixed size.
 *
 * @param {object}        props
 * @param {number|string} [props.size=4] - Height (vertical context) or width (horizontal)
 * @param {string}        [props.axis='vertical'] - 'vertical' | 'horizontal'
 */
export function Spacer({ size = 4, axis = 'vertical', ...rest }) {
  const resolved = resolveSpace(size) ?? String(size);
  const style =
    axis === 'horizontal'
      ? { display: 'inline-block', width: resolved, flexShrink: 0 }
      : { display: 'block', height: resolved, flexShrink: 0 };

  return <span aria-hidden="true" style={style} {...rest} />;
}

/**
 * `Flex1` — a flex child that grows to fill all remaining space.
 * Use inside HStack or VStack to push siblings to opposite edges.
 *
 * @example
 * <HStack>
 *   <Text>Left</Text>
 *   <Flex1 />
 *   <Button>Right</Button>
 * </HStack>
 */
export function Flex1({ style, ...rest }) {
  return <span style={{ flex: 1, ...style }} {...rest} />;
}

/* ════════════════════════════════════════════════════════════════════
   DIVIDER
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Divider` — horizontal or vertical separator, optionally labelled.
 *
 * @param {object}  props
 * @param {'horizontal'|'vertical'} [props.orientation='horizontal']
 * @param {string}  [props.label] - Optional text label centred on the rule
 * @param {string}  [props.className]
 */
export function Divider({ orientation = 'horizontal', label, className, ...rest }) {
  if (label) {
    return (
      <div className={cx('aeos-divider-label', className)} role="separator" {...rest}>
        <span className="aeos-divider-line" />
        <span className="aeos-divider-text">{label}</span>
        <span className="aeos-divider-line" />
      </div>
    );
  }

  return (
    <span
      role="separator"
      aria-orientation={orientation}
      className={cx(
        orientation === 'vertical'
          ? 'aeos-divider-vertical'
          : 'aeos-divider-horizontal',
        className
      )}
      {...rest}
    />
  );
}

/* ════════════════════════════════════════════════════════════════════
   TYPOGRAPHY
   ════════════════════════════════════════════════════════════════════ */

/**
 * `Heading` — renders a heading element with AEOS display type scale.
 *
 * @param {object}        props
 * @param {1|2|3|4|5|6}   [props.level=2]  - Semantic heading level (1–6)
 * @param {string}        [props.as]        - Override rendered element (e.g. 'p' for visual-only heading)
 * @param {boolean}       [props.gradient]  - Apply cyan gradient text effect
 * @param {boolean}       [props.mono]      - Apply monospace font
 * @param {string}        [props.className]
 */
export const Heading = forwardRef(function Heading(
  { level = 2, as, gradient, mono, className, children, ...rest },
  ref
) {
  const Tag = as ?? `h${level}`;
  return createElement(
    Tag,
    {
      ref,
      className: cx(
        `aeos-h${level}`,
        gradient && 'aeos-text-grad',
        mono     && 'aeos-text-mono',
        className
      ),
      ...rest,
    },
    children
  );
});

/**
 * Text size → CSS class map.
 */
const TEXT_SIZE_CLASS = {
  xs:   'aeos-text-xs',
  sm:   'aeos-text-sm',
  base: 'aeos-text-base',
  lg:   'aeos-text-lg',
};

/**
 * Text tone → CSS class map.
 */
const TEXT_TONE_CLASS = {
  primary:   'aeos-text-primary',
  secondary: 'aeos-text-secondary',
  tertiary:  'aeos-text-tertiary',
  muted:     'aeos-text-muted',
  success:   'aeos-text-success',
  danger:    'aeos-text-danger',
};

/**
 * Font-weight → CSS class map.
 */
const TEXT_WEIGHT_CLASS = {
  500: 'aeos-fw-500',
  600: 'aeos-fw-600',
  700: 'aeos-fw-700',
  medium:     'aeos-fw-500',
  semibold:   'aeos-fw-600',
  bold:       'aeos-fw-700',
};

/**
 * `Text` — body text element with tone and size control.
 *
 * @param {object}                                props
 * @param {string}                                [props.as='p']   - Rendered element
 * @param {'xs'|'sm'|'base'|'lg'}                [props.size='base']
 * @param {'primary'|'secondary'|'tertiary'|'muted'|'success'|'danger'} [props.tone]
 * @param {boolean}                               [props.mono]     - Use monospace font
 * @param {500|600|700|'medium'|'semibold'|'bold'} [props.weight]
 * @param {number|string}                         [props.mb]       - Margin-bottom (space scale)
 * @param {string}                                [props.className]
 */
export const Text = forwardRef(function Text(
  { as = 'p', size = 'base', tone, mono, weight, mb, className, style, children, ...rest },
  ref
) {
  const mbVal = resolveSpace(mb);
  const inlineStyle = mbVal ? { marginBottom: mbVal, ...style } : style;

  return createElement(
    as,
    {
      ref,
      className: cx(
        TEXT_SIZE_CLASS[size] ?? 'aeos-text-base',
        tone  && TEXT_TONE_CLASS[tone],
        mono  && 'aeos-text-mono',
        weight && TEXT_WEIGHT_CLASS[weight],
        className
      ),
      style: inlineStyle,
      ...rest,
    },
    children
  );
});

/**
 * `Label` — form label rendered with `.aeos-label` styling.
 *
 * @param {object}  props
 * @param {string}  [props.htmlFor] - Linked input id
 * @param {string}  [props.className]
 */
export const Label = forwardRef(function Label(
  { htmlFor, className, children, ...rest },
  ref
) {
  return (
    <label
      ref={ref}
      htmlFor={htmlFor}
      className={cx('aeos-label', className)}
      {...rest}
    >
      {children}
    </label>
  );
});

/**
 * `Mono` — inline monospace span for values, codes, and numeric data.
 *
 * @param {object}  props
 * @param {string}  [props.className]
 */
export const Mono = forwardRef(function Mono({ className, children, ...rest }, ref) {
  return (
    <span ref={ref} className={cx('aeos-text-mono', className)} {...rest}>
      {children}
    </span>
  );
});

/**
 * `Eyebrow` — small uppercase label rendered above a heading.
 *
 * @param {object}                       props
 * @param {'default'|'primary'|'amber'|'indigo'} [props.tone='default']
 * @param {string}                       [props.className]
 */
export const Eyebrow = forwardRef(function Eyebrow(
  { tone = 'default', className, children, ...rest },
  ref
) {
  const toneClass =
    tone !== 'default' ? `aeos-eyebrow-${tone}` : undefined;

  return (
    <span
      ref={ref}
      className={cx('aeos-eyebrow', toneClass, className)}
      {...rest}
    >
      {children}
    </span>
  );
});

/* ════════════════════════════════════════════════════════════════════
   CARD
   ════════════════════════════════════════════════════════════════════ */

/**
 * Track bento mouse position for the cursor-tracked radial glow.
 * Works automatically when the active theme sets data-card-style="bento".
 */
function handleBentoMove(event) {
  const rect = event.currentTarget.getBoundingClientRect();
  event.currentTarget.style.setProperty('--mx', `${((event.clientX - rect.left) / rect.width) * 100}%`);
  event.currentTarget.style.setProperty('--my', `${((event.clientY - rect.top) / rect.height) * 100}%`);
}

/**
 * `Card` — theme-controlled surface container.
 *
 * The visual style (flat, glass, elevated, glow, bento, etc.) is determined
 * entirely by the active theme via `body[data-card-style]`. No variant prop —
 * the ThemeDrawer is the only way to change card appearance.
 *
 * The mouse-tracking handler for the bento glow is always attached; it only
 * has a visual effect when the bento card style is active.
 *
 * @param {object}   props
 * @param {boolean}  [props.interactive] - Adds hover lift styles
 * @param {string}   [props.as='div']    - Rendered HTML element
 * @param {string}   [props.className]
 */
export const Card = forwardRef(function Card(
  { interactive, as: Tag = 'div', className, style, onMouseMove, children, ...rest },
  ref
) {
  function handleMouseMove(event) {
    handleBentoMove(event);
    onMouseMove?.(event);
  }

  return createElement(
    Tag,
    {
      ref,
      className: cx('aeos-card-auto aeos-card', interactive && 'aeos-card-interactive', className),
      style,
      onMouseMove: handleMouseMove,
      ...rest,
    },
    children
  );
});

/* ════════════════════════════════════════════════════════════════════
   CARD SUB-COMPONENTS
   ════════════════════════════════════════════════════════════════════ */

/**
 * `CardHeader` — standardised card header region.
 *
 * Renders a two-column layout: [icon + title + subtitle] on the left,
 * [action] on the right. If you need full control, pass `children` instead.
 *
 * @param {object}         props
 * @param {string}         [props.eyebrow]   - Small overline text (mono)
 * @param {string}         [props.title]     - Primary heading text
 * @param {string}         [props.subtitle]  - Secondary description text
 * @param {React.ReactNode} [props.action]   - Element(s) to show on the right
 * @param {string}         [props.className]
 * @param {React.ReactNode} [props.children] - If provided, renders instead of structured layout
 */
export const CardHeader = forwardRef(function CardHeader(
  { eyebrow, title, subtitle, action, className, children, ...rest },
  ref
) {
  if (children) {
    return (
      <div
        ref={ref}
        className={cx('aeos-card-header', className)}
        {...rest}
      >
        {children}
      </div>
    );
  }

  return (
    <div
      ref={ref}
      className={cx('aeos-card-header', className)}
      {...rest}
    >
      {/* Left region */}
      <div className="aeos-card-header-text">
        {eyebrow && (
          <Eyebrow tone="primary">{eyebrow}</Eyebrow>
        )}
        {title && (
          <Heading level={4}>{title}</Heading>
        )}
        {subtitle && (
          <Text size="sm" tone="secondary">
            {subtitle}
          </Text>
        )}
      </div>

      {/* Right region */}
      {action && (
        <div className="aeos-card-header-action">
          {action}
        </div>
      )}
    </div>
  );
});

/**
 * `CardBody` — card body wrapper with optional className.
 *
 * @param {object}  props
 * @param {string}  [props.className]
 */
export const CardBody = forwardRef(function CardBody(
  { className, children, ...rest },
  ref
) {
  return (
    <div
      ref={ref}
      className={cx('aeos-card-body', className)}
      {...rest}
    >
      {children}
    </div>
  );
});

/**
 * `CardFooter` — card footer with alignment control.
 *
 * @param {object}          props
 * @param {'left'|'right'|'between'} [props.align='left'] - Horizontal alignment of children
 * @param {string}          [props.className]
 */
export const CardFooter = forwardRef(function CardFooter(
  { align = 'left', className, children, style, ...rest },
  ref
) {
  const alignClass = align === 'right' ? 'aeos-card-footer-right'
    : align === 'between' ? 'aeos-card-footer-between'
    : 'aeos-card-footer-left';

  return (
    <div
      ref={ref}
      className={cx('aeos-card-footer', alignClass, className)}
      style={style}
      {...rest}
    >
      {children}
    </div>
  );
});
