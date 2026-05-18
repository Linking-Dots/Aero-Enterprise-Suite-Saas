import App from '../../../App.jsx';
import { VStack, Text, Eyebrow, Card, CardBody } from '@aero/ui';

export default function SelfServiceNoProfile() {
  return (
    <div className="ss-noprofile-wrap">
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

      <style>{`
        .ss-noprofile-wrap {
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 60vh;
          padding: 1.5rem;
        }
        .ss-noprofile-wrap .aeos-card-auto {
          max-width: 480px;
          width: 100%;
          text-align: center;
        }
      `}</style>
    </div>
  );
}

SelfServiceNoProfile.layout = page => <App title="No Profile">{page}</App>;
