import App from '@/Pages/App.jsx';
import { VStack, Text, Eyebrow, Card, CardBody } from '@aero/ui';

export default function SelfServiceNoProfile() {
  return (
    <div className="min-h-screen flex items-center justify-center p-6">
      <div className="max-w-lg w-full text-center">
        <Card>
          <CardBody>
            <VStack gap={3} align="center">
              <Eyebrow>Self-Service Portal</Eyebrow>
              <Text size="lg">No Employee Profile Found</Text>
              <Text tone="secondary">
                Your employee profile could not be located. Please contact HR to have your profile set up.
              </Text>
            </VStack>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}

SelfServiceNoProfile.layout = page => <App title="No Profile">{page}</App>;
