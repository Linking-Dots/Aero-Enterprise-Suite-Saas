import PublicLayout from './Layout/PublicLayout.jsx';
import HeroLivingOS from './home/HeroLivingOS.jsx';
import ModuleStory from './home/ModuleStory.jsx';
import FeaturesLiving from './home/FeaturesLiving.jsx';
import ArchitectureParallax from './home/ArchitectureParallax.jsx';
import {
  TrustBarLiving, StatsBand, TestimonialsLiving, CtaLiving,
} from './home/HomeSections.jsx';

// ─── Page ─────────────────────────────────────────────────────────
export default function Home() {
  return (
    <>
      <HeroLivingOS />
      <TrustBarLiving />
      <ModuleStory />
      <FeaturesLiving />
      <ArchitectureParallax />
      <StatsBand />
      <TestimonialsLiving />
      <CtaLiving />
    </>
  );
}

Home.layout = (page) => (
  <PublicLayout title="Enterprise ERP Platform">{page}</PublicLayout>
);
