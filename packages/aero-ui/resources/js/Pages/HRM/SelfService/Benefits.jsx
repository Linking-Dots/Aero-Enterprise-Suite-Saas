import App from '@/Pages/App.jsx';
import { VStack, Text, Eyebrow, Card, CardBody } from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

export default function SelfServiceBenefits() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6 p-6">
      <SelfServiceSidebar />

      <VStack gap={5}>
        <VStack gap={1}>
          <Eyebrow>Self-Service Portal</Eyebrow>
          <Text size="lg">My Benefits</Text>
        </VStack>

        <Card>
          <CardBody>
            <VStack gap={2}>
              <Text>Benefits information coming soon.</Text>
              <Text tone="secondary">
                The benefits module is under development. Please contact HR for your current benefits details.
              </Text>
            </VStack>
          </CardBody>
        </Card>
      </VStack>
    </div>
  );
}

SelfServiceBenefits.layout = page => <App title="My Benefits">{page}</App>;
