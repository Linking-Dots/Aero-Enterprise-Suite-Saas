import { useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import './tour.css';
import { GRAND_TOUR } from './tourSteps.js';

/* ── Persistence keys ────────────────────────────────────────────────── */
const K_ACTIVE = 'aeos:tour:active';   // sessionStorage — a tour is running
const K_STEP   = 'aeos:tour:step';     // sessionStorage — current step index
const K_SEEN   = 'aeos:tour:seen';     // localStorage   — this browser saw the auto-tour

/* Single live driver instance (one highlight at a time; we drive steps
   ourselves so the tour can span Inertia page navigations). */
let d = null;
let curReduced = false;
let shownStep = -1;       // step index currently shown (avoids duplicate highlights)
let watchdog = null;      // re-asserts the popover if a lazy re-render drops it

function path() { return window.location.pathname; }

function makeDriver(reduced) {
  return driver({
    animate: !reduced,
    smoothScroll: !reduced,
    allowClose: true,
    stagePadding: 6,
    stageRadius: 8,
    overlayOpacity: 0.6,
    showProgress: false, // we render our own "Step X of N"
    popoverClass: 'aeos-tour',
  });
}

function destroyDriver() {
  if (d) { try { d.destroy(); } catch (e) { /* noop */ } d = null; }
  shownStep = -1;
}

/** Highlight step i once its anchor exists (polls briefly after Inertia nav). */
function highlightWhenReady(i, reduced, attempt = 0) {
  const step = GRAND_TOUR[i];
  if (!step) { endTour(); return; }
  curReduced = reduced;

  // Already showing this exact step with a live popover → nothing to do.
  if (shownStep === i && document.querySelector('.driver-popover')) return;

  if (step.element && !document.querySelector(step.element) && attempt < 24) {
    setTimeout(() => highlightWhenReady(i, reduced, attempt + 1), 150);
    return;
  }

  destroyDriver();
  d = makeDriver(reduced);
  shownStep = i;
  const isLast = i === GRAND_TOUR.length - 1;
  const progress = `<div class="aeos-tour__progress">Step ${i + 1} of ${GRAND_TOUR.length}</div>`;

  d.highlight({
    element: step.element && document.querySelector(step.element) ? step.element : undefined,
    popover: {
      title: step.title,
      description: step.description + progress,
      showButtons: ['next', 'previous', 'close'],
      disableButtons: i === 0 ? ['previous'] : [],
      nextBtnText: isLast ? 'Done' : 'Next',
      prevBtnText: 'Back',
      onNextClick: () => { if (isLast) { endTour(); } else { goToStep(i + 1, reduced); } },
      onPrevClick: () => goToStep(Math.max(0, i - 1), reduced),
      onCloseClick: () => endTour(),
    },
  });
}

function goToStep(i, reduced) {
  const step = GRAND_TOUR[i];
  if (!step) { endTour(); return; }
  curReduced = reduced;
  sessionStorage.setItem(K_STEP, String(i));

  // Cross-page: navigate then resume on the destination page.
  if (step.route && path() !== step.route) {
    destroyDriver();
    router.visit(step.route);
    return;
  }
  highlightWhenReady(i, reduced);
}

/* Watchdog: while a tour is active and we're on the right page with the anchor
   present, ensure the popover is on screen. Recovers from lazy re-renders that
   detach driver.js's highlighted node (e.g. a table hydrating after fetch). */
function startWatchdog() {
  stopWatchdog();
  watchdog = setInterval(() => {
    if (!isTourActive()) { stopWatchdog(); return; }
    const i = Number(sessionStorage.getItem(K_STEP) || 0);
    const step = GRAND_TOUR[i];
    if (!step) return;
    if (step.route && path() !== step.route) return;      // mid-navigation
    if (step.element && !document.querySelector(step.element)) return; // anchor not ready
    if (!document.querySelector('.driver-popover')) {
      highlightWhenReady(i, curReduced);
    }
  }, 600);
}

function stopWatchdog() {
  if (watchdog) { clearInterval(watchdog); watchdog = null; }
}

/** Start (or restart) the guided tour from the beginning. */
export function startTour(reduced = false) {
  curReduced = reduced;
  sessionStorage.setItem(K_ACTIVE, '1');
  sessionStorage.setItem(K_STEP, '0');
  startWatchdog();
  goToStep(0, reduced);
}

/** End the tour and clear all state. */
export function endTour() {
  sessionStorage.removeItem(K_ACTIVE);
  sessionStorage.removeItem(K_STEP);
  stopWatchdog();
  destroyDriver();
}

export function isTourActive() {
  return typeof window !== 'undefined' && sessionStorage.getItem(K_ACTIVE) === '1';
}

/**
 * useTourEngine — mount in App. Jobs:
 *  1. Auto-start the tour once per browser on the demo tenant.
 *  2. Resume an in-progress tour after an Inertia navigation.
 *
 * @param {{ isDemo?: boolean, reduced?: boolean, url?: string }} opts
 */
export function useTourEngine({ isDemo = false, reduced = false, url = '' }) {
  const startedRef = useRef(false);
  curReduced = reduced;

  // Auto-start once per browser, on the demo tenant only.
  useEffect(() => {
    if (!isDemo || startedRef.current) return;
    if (localStorage.getItem(K_SEEN) === '1') return;
    if (isTourActive()) return;
    startedRef.current = true;
    localStorage.setItem(K_SEEN, '1');
    const t = setTimeout(() => startTour(reduced), 700); // let the first page settle
    return () => clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isDemo]);

  // Resume across navigations (+ keep the watchdog running).
  useEffect(() => {
    if (!isTourActive()) return;
    startWatchdog();
    const i = Number(sessionStorage.getItem(K_STEP) || 0);
    const step = GRAND_TOUR[i];
    if (!step) { endTour(); return; }
    if (step.route && path() !== step.route) return; // not on this step's page yet
    const t = setTimeout(() => highlightWhenReady(i, reduced), 300);
    return () => clearTimeout(t);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [url]);
}
