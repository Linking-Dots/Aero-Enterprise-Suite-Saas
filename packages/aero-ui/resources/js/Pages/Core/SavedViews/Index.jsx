import { SavedViewsList } from '@aero/ui';
import App from '../../app.jsx';

export default function SavedViewsIndex({ views, shared_views, module_code, route }) {
  return (
    <SavedViewsList
      moduleCode={module_code || 'core'}
      route={route}
      initialViews={views}
      initialSharedViews={shared_views}
    />
  );
}

SavedViewsIndex.layout = page => <App title="Saved Views">{page}</App>;
