import App from '@/Pages/App.jsx';
import { VStack, Text, Eyebrow, Card, CardBody } from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

export default function SelfServiceCareerPath() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">Career Path</Text>
        </VStack>

        <Card>
          <CardBody>
            <VStack gap={2}>
              <Text>Career path information coming soon.</Text>
              <Text tone="secondary">
                Career development planning is under development. Please speak with your manager for career guidance.
              </Text>
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </div>
  );
}

SelfServiceCareerPath.layout = page => <App title="Career Path">{page}</App>;
