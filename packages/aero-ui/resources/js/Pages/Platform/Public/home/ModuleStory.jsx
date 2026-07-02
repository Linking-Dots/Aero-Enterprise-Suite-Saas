// ─── Living data-OS — Signature §1: sticky-scroll module storytelling ─────────
import { useRef, useState } from 'react';
import { motion, AnimatePresence, useScroll, useMotionValueEvent } from 'framer-motion';
import { Container } from '@aero/ui';
import { ThemedShot } from './primitives.jsx';
import { STORY_SLIDES } from './livingData.js';

export default function ModuleStory() {
  const ref = useRef(null);
  const [active, setActive] = useState(0);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ['start start', 'end end'],
  });

  useMotionValueEvent(scrollYProgress, 'change', (p) => {
    const idx = Math.min(STORY_SLIDES.length - 1, Math.floor(p * STORY_SLIDES.length));
    setActive(idx < 0 ? 0 : idx);
  });

  const slide = STORY_SLIDES[active];
  const Icon = slide.icon;

  return (
    <section
      className="lv-story"
      ref={ref}
      style={{ height: `${STORY_SLIDES.length * 100}vh` }}
    >
      <div className="lv-story-sticky">
        <Container>
          <div className="lv-story-cols">
            {/* Copy */}
            <div className="lv-story-copy">
              <div className="lv-story-rail">
                {STORY_SLIDES.map((s, i) => (
                  <button
                    key={s.id}
                    type="button"
                    className={`lv-story-tick ${i === active ? 'is-active' : ''} lv-story-tick--${s.accent}`}
                    aria-label={s.tag}
                    onClick={() => {
                      const el = ref.current;
                      if (!el) return;
                      const top = el.offsetTop + (i + 0.5) * window.innerHeight;
                      window.scrollTo({ top, behavior: 'smooth' });
                    }}
                  />
                ))}
              </div>
              <AnimatePresence mode="wait">
                <motion.div
                  key={slide.id}
                  initial={{ opacity: 0, y: 22 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -18 }}
                  transition={{ duration: 0.45, ease: [0.16, 1, 0.3, 1] }}
                  className="lv-story-copy-inner"
                >
                  <span className={`lv-story-tag lv-accent--${slide.accent}`}>
                    <Icon width={16} height={16} /> {slide.tag}
                  </span>
                  <h2 className="lv-h2">
                    {slide.title}{' '}
                    <span className={`lv-h2-grad lv-grad--${slide.accent}`}>{slide.highlight}</span>
                  </h2>
                  <p className="lv-lead">{slide.body}</p>
                  <span className={`lv-story-stat lv-accent--${slide.accent}`}>{slide.stat}</span>
                </motion.div>
              </AnimatePresence>
            </div>

            {/* Visual */}
            <div className="lv-story-visual">
              <div className={`lv-story-glow lv-glow--${slide.accent}`} aria-hidden="true" />
              <AnimatePresence>
                <motion.div
                  key={slide.id}
                  className="lv-story-shot"
                  initial={{ opacity: 0, scale: 0.98 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 1.01 }}
                  transition={{ duration: 0.55, ease: [0.16, 1, 0.3, 1] }}
                >
                  <ThemedShot shot={slide.shot} alt={slide.tag} />
                </motion.div>
              </AnimatePresence>
            </div>
          </div>
        </Container>
      </div>
    </section>
  );
}
