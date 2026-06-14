import { router } from '@inertiajs/react';
import App from '@/Pages/App.jsx';
import {
  PageHeader, Button, VStack, HStack, Text, Card, CardBody,
} from '@aero/ui';

/** Recursive tree node — renders a department card and its children. */
function Node({ node, depth = 0 }) {
  return (
    <div className={depth > 0 ? 'org-chart-child' : undefined}>
      <Card>
        <CardBody>
          <VStack gap={1}>
            <Text size="sm" tone="primary">{node.name}</Text>
            {node.head?.user?.name && (
              <Text size="xs" tone="secondary">{node.head.user.name}</Text>
            )}
          </VStack>
        </CardBody>
      </Card>

      {node.children?.length > 0 && (
        <div className="org-chart-children">
          {node.children.map(child => (
            <Node key={child.id} node={child} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

export default function DepartmentsOrgChart({ tree }) {
  return (
    <VStack gap={6}>
      <PageHeader
        breadcrumb={[
          { label: 'HRM' },
          { label: 'Org Structure' },
          { label: 'Departments', href: route('hrm.org.departments.index') },
          { label: 'Org Chart' },
        ]}
        title="Org Chart"
        actions={
          <Button
            intent="ghost"
            onClick={() => router.get(route('hrm.org.departments.index'))}
          >
            Back to List
          </Button>
        }
      />

      <div className="org-chart-root">
        {(tree ?? []).map(node => (
          <Node key={node.id} node={node} depth={0} />
        ))}
      </div>

      <style>{`
        .org-chart-root {
          display: flex;
          flex-direction: column;
          gap: var(--aeos-space-4);
        }
        .org-chart-children {
          display: flex;
          flex-direction: column;
          gap: var(--aeos-space-3);
          padding-left: var(--aeos-space-8);
          border-left: 2px solid var(--aeos-divider);
          margin-left: var(--aeos-space-4);
          margin-top: var(--aeos-space-3);
        }
        .org-chart-child {
          position: relative;
        }
        .org-chart-child::before {
          content: '';
          position: absolute;
          top: 50%;
          left: calc(-1 * var(--aeos-space-4));
          width: var(--aeos-space-4);
          height: 2px;
          background: var(--aeos-divider);
        }
      `}</style>
    </VStack>
  );
}

DepartmentsOrgChart.layout = page => <App title="Org Chart">{page}</App>;
