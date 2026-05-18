import App from '../../../App.jsx';
import { VStack, Text, Eyebrow, Card, CardBody } from '@aero/ui';
import SelfServiceSidebar from './components/SelfServiceSidebar.jsx';

export default function SelfServiceBenefits() {
  return (
    <div className="ss-layout">
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

      <style>{`
        .ss-layout {
          display: grid;
          grid-template-columns: 240px 1fr;
          gap: 1.5rem;
          padding: 1.5rem;
        }
        @media (max-width: 1023px) {
          .ss-layout { grid-template-columns: 1fr; }
        }
      `}</style>
    </div>
  );
}

SelfServiceBenefits.layout = page => <App title="My Benefits">{page}</App>;
