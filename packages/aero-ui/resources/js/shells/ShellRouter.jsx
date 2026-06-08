/**
 * AEOS UI — ShellRouter (Phase B)
 * -----------------------------------------------------------------------
 * Thin routing layer. Determines which shell variant to render and
 * delegates entirely to the appropriate shell component.
 *
 * Public API preserved: AppShell({ variant, brand, nav, children, ... })
 * -----------------------------------------------------------------------
 */
import { useTheme } from '../theme/ThemeProvider.jsx';
import { SidebarShell } from './Shells.jsx';
import { TopNavShell } from './Shells.jsx';
import { FloatingShell } from './Shells.jsx';
import { CommandShell } from './Shells.jsx';

/** Map of shell variant names → shell components */
const SHELL_MAP = {
  sidebar:  SidebarShell,
  topnav:   TopNavShell,
  floating: FloatingShell,
  command:  CommandShell,
};

/**
 * ShellRouter — resolves the active variant from props or theme context
 * and renders the matching shell component.
 *
 * @param {{ variant?: string, [key: string]: any }} props
 */
export function ShellRouter({ variant, ...props }) {
  const theme = useTheme();
  const shell = variant ?? theme.shell;
  const ShellComponent = SHELL_MAP[shell] ?? SidebarShell;
  return <ShellComponent {...props} />;
}

export default ShellRouter;
