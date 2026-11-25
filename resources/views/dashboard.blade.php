<x-app-layout>
@php
  $first = trim(explode(' ', auth()->user()->name ?? '')[0] ?? '');
  $fr = (int) ($fieldReports ?? 0); $bf = (int) ($bulkFiles ?? 0); $bl = (int) ($bulkLayers ?? 0);
  $sheets = $fr + $bl;                                   // each bulk layer is one FDT sheet
  $pBulk  = $sheets ? round($bl / $sheets * 100) : 0;  $pOne = $sheets ? 100 - $pBulk : 0;
  $C = 251.33;                                          // circumference of r=40
  $a = round($pOne / 100 * $C, 2); $b = round($pBulk / 100 * $C, 2);
  $avg = $bf ? round($bl / $bf, 1) : 0;                 // layers per bulk file (tool max is 30)
  $g = round(min(100, $avg / 30 * 100) / 100 * $C, 2);
  $max = max(1, $fr, $bf, $bl);
  $hasActivity = ($fr + $bf + $bl) > 0;
@endphp


<style>
/* ===== FDTNexa dashboard · design tokens ===== */
.fx-root{
  --fx-bg:#0B1220;--fx-s1:#111C2D;--fx-s2:#172438;--fx-line:rgba(148,163,184,.16);
  --cyan:#06B6D4;--teal:#14B8A6;--sky:#38BDF8;--vio:#6366F1;
  --t1:#F8FAFC;--t2:#CBD5E1;--t3:#94A3B8;
  --r:18px;--gap:1.5rem;--ease:.25s ease;
  --plate:0 7px 0 -3px #0e1626,0 14px 0 -6px #0a111e,0 26px 38px -14px rgba(0,0,0,.7);
  position:relative;isolation:isolate;overflow:hidden;box-sizing:border-box;color:var(--t1);padding:2rem 0 0;min-height:calc(100vh - 4rem);
  background:radial-gradient(1200px 520px at 70% -10%,#12304a 0%,transparent 60%),var(--fx-bg)}
.fx-root *,.fx-root *::before,.fx-root *::after{box-sizing:border-box}
.fx-wrap{max-width:80rem;margin:0 auto;padding:0 1rem}
@media(min-width:640px){.fx-wrap{padding:0 1.5rem}}@media(min-width:1024px){.fx-wrap{padding:0 2rem}}

/* ===== motion ===== */
@keyframes fx-drift{to{transform:translate3d(-2.5%,1.5%,0)}}
@keyframes fx-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@keyframes fx-pulse{0%{box-shadow:0 0 0 0 rgba(20,184,166,.55)}100%{box-shadow:0 0 0 9px rgba(20,184,166,0)}}
@keyframes fx-flow{to{background-position:0 -24px}}@keyframes fx-flowx{to{background-position:24px 0}}
@keyframes fx-draw{from{stroke-dasharray:0 252}}@keyframes fx-grow{from{transform:scaleX(0)}}
@keyframes fx-rot{to{transform:rotate(360deg)}}
@property --fx-angle{syntax:"<angle>";initial-value:0deg;inherits:false}
@keyframes fx-electric{to{--fx-angle:360deg}}

/* ===== background ===== */
.fx-bg{position:absolute;inset:-4%;z-index:-1;width:108%;height:108%;pointer-events:none;animation:fx-drift 40s ease-in-out infinite alternate;
  -webkit-mask-image:radial-gradient(ellipse at 50% 28%,#000 15%,transparent 72%);mask-image:radial-gradient(ellipse at 50% 28%,#000 15%,transparent 72%)}

/* Background parallax — combined with existing drift */
.fx-root .fx-bg {
    translate: var(--bg-x, 0px) var(--bg-y, 0px);
    transition: translate .35s ease-out;
    will-change: translate;
}

@media (prefers-reduced-motion: reduce) {
    .fx-root .fx-bg {
        translate: none !important;
        transition: none !important;
    }
}

/* ===== layout ===== */
.fx-g2,.fx-g3{display:grid;gap:var(--gap);grid-template-columns:1fr}
@media(min-width:640px){.fx-g2,.fx-g3{grid-template-columns:repeat(2,minmax(0,1fr))}.fx-last{grid-column:span 2}}
@media(min-width:1024px){.fx-g3{grid-template-columns:repeat(3,minmax(0,1fr))}.fx-last{grid-column:auto}}
.fx-sec{margin-top:2.5rem}.fx-row{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}.fx-mid{align-items:center}
.fx-gap{gap:1rem;justify-content:flex-start}.fx-min{min-width:0}.fx-blk{display:block}

/* ===== typography ===== */
.fx-h1{font-size:clamp(2rem,5.2vw,3.4rem);line-height:1.04;font-weight:800;letter-spacing:-.03em;margin:.7rem 0 .8rem}
.fx-h1 em{font-style:normal;background:linear-gradient(90deg,var(--cyan),var(--teal));-webkit-background-clip:text;background-clip:text;color:transparent}
.fx-h2{font-size:1.25rem;font-weight:700;letter-spacing:-.015em;margin:0}.fx-sub{color:var(--t3);font-size:.9rem;margin:.2rem 0 1.25rem}
.fx-k{margin:0;color:var(--t2);font-size:.74rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase}
.fx-t{margin:.35rem 0 0;color:var(--t1);font-weight:600}
.fx-mt{margin:1.1rem 0 0;font-size:1.45rem;line-height:1.2;font-weight:700;letter-spacing:-.02em}
.fx-d{margin:.3rem 0 0;color:var(--t3);font-size:.9rem;line-height:1.6}
.fx-lead{margin:0;color:var(--t3);max-width:46ch;line-height:1.65}
.fx-num{display:block;margin-top:1.1rem;font-size:clamp(2.1rem,3vw,2.7rem);line-height:1.05;font-weight:800;letter-spacing:-.04em;font-variant-numeric:tabular-nums;text-shadow:0 0 22px color-mix(in srgb,var(--a) 18%,transparent)}
.fx-tag{font-size:.68rem;font-weight:700;letter-spacing:.04em;padding:.28rem .65rem;border-radius:99px;white-space:nowrap;color:color-mix(in srgb,var(--a) 65%,#fff);background:color-mix(in srgb,var(--a) 9%,transparent);border:1px solid color-mix(in srgb,var(--a) 25%,transparent)}

/* ===== hero ===== */
.fx-hero{display:grid;gap:1.5rem;align-items:center}
@media(min-width:768px){.fx-hero{grid-template-columns:1.25fr .75fr}}
.fx-status{display:inline-flex;align-items:center;gap:.55rem;margin-top:1.3rem;padding:.45rem .9rem;border-radius:99px;font-size:.82rem;color:#99f6e4;background:rgba(20,184,166,.1);border:1px solid rgba(20,184,166,.28)}
.fx-status b{width:8px;height:8px;border-radius:50%;background:var(--teal);animation:fx-pulse 1.8s ease-out infinite}
.fx-iso{display:none;justify-self:center;width:min(100%,300px)}@media(min-width:768px){.fx-iso{display:block}}
.fx-iso .s,.fx-art .s{animation:fx-float 7s ease-in-out infinite;animation-delay:var(--d)}

/* ===== cards (plates) ===== */
.fx-card{--a:var(--cyan);--rx:0deg;--ry:0deg;--ty:0px;position:relative;display:block;overflow:hidden;color:inherit;text-decoration:none;border-radius:var(--r);
  background:linear-gradient(160deg,var(--fx-s2),var(--fx-s1) 70%);border:1px solid var(--fx-line);box-shadow:inset 0 1px 0 rgba(255,255,255,.07),var(--plate);
  transform:perspective(900px) rotateX(var(--rx)) rotateY(var(--ry)) translateY(var(--ty));transition:transform .18s ease-out,border-color var(--ease),box-shadow var(--ease)}
.fx-card::before{content:"";position:absolute;inset:0 0 auto;height:2px;background:linear-gradient(90deg,transparent,var(--a),transparent);pointer-events:none}
.fx-card::after{content:"";position:absolute;inset:0;pointer-events:none;opacity:0;transition:opacity var(--ease);background:radial-gradient(260px circle at var(--mx,50%) var(--my,0%),color-mix(in srgb,var(--a) 20%,transparent),transparent 65%)}
.fx-in{position:relative;z-index:1;padding:1.35rem}
a.fx-card:focus-visible{outline:2px solid var(--a);outline-offset:3px;--ty:-3px}
a.fx-card:focus-visible::after{opacity:1}
@media(hover:hover){
  .fx-card:hover{border-color:color-mix(in srgb,var(--a) 40%,transparent)}
  a.fx-card:hover{--ty:-4px;box-shadow:inset 0 1px 0 rgba(255,255,255,.08),var(--plate),0 0 28px -6px color-mix(in srgb,var(--a) 50%,transparent)}
  a.fx-card:hover::after{opacity:1}
  a.fx-card:hover .fx-ico{transform:translateY(-3px) scale(1.06)}
  a.fx-card:hover .fx-cta svg{transform:translateX(4px)}
}
.fx-ico{display:grid;place-items:center;flex:none;width:48px;height:48px;border-radius:14px;color:color-mix(in srgb,var(--a) 55%,#fff);
  background:color-mix(in srgb,var(--a) 14%,transparent);border:1px solid color-mix(in srgb,var(--a) 32%,transparent);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.1),0 4px 15px color-mix(in srgb,var(--a) 12%,transparent);transition:transform .3s ease}
.fx-ico svg,.fx-cta svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.fx-ticks{height:8px;margin-top:1.1rem;opacity:.85;background:repeating-linear-gradient(90deg,var(--a) 0 2px,transparent 2px 9px);-webkit-mask-image:linear-gradient(90deg,#000,transparent);mask-image:linear-gradient(90deg,#000,transparent)}

/* ===== tool modules (primary) ===== */
.fx-tool .fx-in{display:grid;gap:1.2rem;align-items:center;padding:1.6rem}
.fx-art{order:-1;height:130px}.fx-art svg{width:100%;height:100%}
@media(min-width:1024px){.fx-tool .fx-in{grid-template-columns:minmax(0,1fr) 170px}.fx-art{order:2;height:170px}}
.fx-art .l{fill:color-mix(in srgb,var(--a) 10%,transparent);stroke:var(--a);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
.fx-art .nf{fill:none}.fx-art .dm{fill:none;stroke:var(--a);stroke-width:1.2;stroke-dasharray:3 4;opacity:.75}
.fx-chips{display:flex;flex-wrap:wrap;gap:.5rem;list-style:none;margin:1rem 0 0;padding:0}
.fx-chips li{font-size:.76rem;color:var(--t2);padding:.25rem .65rem;border-radius:8px;background:rgba(148,163,184,.1);border:1px solid var(--fx-line)}
.fx-cta{display:inline-flex;align-items:center;gap:.55rem;min-height:44px;padding:0 1.1rem;margin-top:1.3rem;border-radius:12px;font-weight:700;font-size:.9rem;color:#031318;
  background:linear-gradient(145deg,color-mix(in srgb,var(--a) 62%,#fff),var(--a));box-shadow:0 4px 0 color-mix(in srgb,var(--a) 45%,#000),0 10px 18px -6px color-mix(in srgb,var(--a) 55%,transparent)}
.fx-cta svg{width:18px;height:18px;transition:transform .2s}
/* electric perimeter: tool cards only */
.fx-tool::before{inset:0;height:auto;padding:1.5px;border-radius:inherit;opacity:.7;animation:fx-electric 9s linear infinite;
  background:conic-gradient(from var(--fx-angle),transparent 0 180deg,var(--cyan) 220deg,var(--vio) 255deg,#A855F7 285deg,var(--teal) 320deg,transparent 360deg);
  -webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);mask-composite:exclude}
@media(hover:hover){.fx-tool:hover::before{animation-duration:3s;opacity:1}}

/* ===== analytics ===== */
.fx-ring{display:flex;align-items:center;gap:1.2rem;margin-top:1rem;flex-wrap:wrap}
.fx-donut{width:132px;height:132px;flex:none}
.fx-donut .tr{fill:none;stroke:rgba(148,163,184,.14);stroke-width:11}
.fx-donut .sg{fill:none;stroke-width:11;transform:rotate(-90deg);transform-origin:50% 50%;animation:fx-draw 1.1s ease-out both}
.fx-donut text{fill:var(--t1);font-weight:800;text-anchor:middle}.fx-donut .sm{fill:var(--t3);font-weight:500;font-size:7px}
.fx-leg{list-style:none;margin:0;padding:0;display:grid;gap:.6rem;font-size:.88rem;flex:1;min-width:130px}
.fx-leg li{display:flex;align-items:center;justify-content:space-between;gap:.6rem;color:var(--t3)}
.fx-leg i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:.5rem;background:var(--c)}.fx-leg b{color:var(--t1);font-variant-numeric:tabular-nums}
.fx-bar{margin-top:1rem}.fx-bar>div:first-child{display:flex;justify-content:space-between;font-size:.88rem;color:var(--t3);margin-bottom:.4rem}.fx-bar b{color:var(--t1)}
.fx-trk{height:9px;border-radius:9px;background:rgba(148,163,184,.14);overflow:hidden}
.fx-fill{height:100%;border-radius:9px;background:linear-gradient(90deg,color-mix(in srgb,var(--c) 70%,#000),var(--c));transform-origin:left;animation:fx-grow .9s ease-out both;box-shadow:0 0 12px color-mix(in srgb,var(--c) 45%,transparent)}
.fx-note{color:var(--t3);font-size:.85rem;margin:.9rem 0 0}
.fx-start{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap}

/* ===== connectors ===== */
.fx-cell{position:relative}.fx-conn{display:none}
@media(min-width:1024px){.fx-conn{display:block;position:absolute;top:50%;right:-1.5rem;width:1.5rem;height:2px;z-index:2;background:repeating-linear-gradient(90deg,var(--cyan) 0 6px,transparent 6px 12px);background-size:24px 2px;animation:fx-flowx 1.6s linear infinite}
  .fx-conn::after{content:"";position:absolute;right:-3px;top:-3px;width:8px;height:8px;border-radius:50%;background:var(--cyan);box-shadow:0 0 10px var(--cyan)}}
.fx-vline{display:block;width:2px;height:36px;margin:1.1rem auto 0;position:relative;background:repeating-linear-gradient(180deg,var(--teal) 0 6px,transparent 6px 12px);background-size:2px 24px;animation:fx-flow 1.6s linear infinite}
.fx-vline::after{content:"";position:absolute;left:-3px;bottom:-4px;width:8px;height:8px;border-radius:50%;background:var(--teal);box-shadow:0 0 10px var(--teal)}
.fx-foot{margin-top:2.6rem;padding-top:1.3rem;border-top:1px solid var(--fx-line);display:flex;justify-content:center;align-items:center;gap:.6rem;color:var(--t3);font-size:.8rem}

/* ===== construction-site scene ===== */
.fx-scene{position:relative;height:clamp(132px,18.4vw,350px);overflow:hidden;margin-top:1.5rem;pointer-events:none}
.fx-scene svg{position:absolute;bottom:0;left:50%;width:max(100%,720px);height:auto;transform:translateX(-50%);overflow:visible}
.fx-scene .tl{fill:rgba(6,182,212,.07);stroke:var(--sky);stroke-opacity:.7;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
.fx-scene .nf{fill:none}.fx-scene .dm{fill:none;stroke-dasharray:3 5;stroke-opacity:.55}
.fx-spin{transform-box:fill-box;transform-origin:center;animation:fx-rot 9s linear infinite}

/* ===== Stage 1 · futuristic KPI cards ===== */
.fx-g4{display:grid;gap:var(--gap);grid-template-columns:1fr}
@media(min-width:640px){.fx-g4{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(min-width:1180px){.fx-g4{grid-template-columns:repeat(4,minmax(0,1fr))}}
.fx-g4 .fx-cell{display:flex}.fx-g4 .fx-cell>.fx-card{flex:1}
.fx-g4 .fx-conn{display:none}@media(min-width:1180px){.fx-g4 .fx-conn{display:block}}
.fx-kpi{min-height:240px;border-radius:20px;border-color:rgba(141,168,188,.2);
  background:linear-gradient(155deg,#1b2f48 0%,#0e1b2d 55%,#0a1422 100%);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.1),inset 0 -18px 30px -24px color-mix(in srgb,var(--a) 40%,transparent),var(--plate)}
.fx-kpi::before{inset:16% auto 16% 0;width:2px;height:auto;background:linear-gradient(180deg,transparent,var(--a),transparent);box-shadow:0 0 14px var(--a)}
.fx-kpi::after{opacity:1;background:radial-gradient(240px circle at var(--mx,78%) var(--my,8%),color-mix(in srgb,var(--a) 17%,transparent),transparent 62%),linear-gradient(115deg,rgba(255,255,255,.07),transparent 36%)}
.fx-kin{position:relative;z-index:1;display:flex;flex-direction:column;height:100%;min-height:240px;padding:1.3rem}
.fx-kid{display:inline-flex;align-items:center;gap:.5rem;font:600 .66rem/1 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.14em;color:var(--t3);text-transform:uppercase}
.fx-kid::before{content:"";width:6px;height:6px;border-radius:50%;background:var(--a);box-shadow:0 0 8px var(--a)}
.fx-kart{position:absolute;z-index:0;right:-4px;top:2px;width:46%;max-width:168px;aspect-ratio:1;pointer-events:none;animation:fx-float 7s ease-in-out infinite;animation-delay:var(--d,0s);will-change:transform}
.fx-kart svg{width:100%;height:100%;filter:drop-shadow(0 14px 16px rgba(0,0,0,.55));transition:transform .4s ease}
.fx-kb{margin-top:auto;position:relative;z-index:1}
.fx-knum{display:block;font-size:clamp(2.3rem,3.2vw,3rem);font-weight:800;line-height:1;letter-spacing:-.04em;font-variant-numeric:tabular-nums;text-shadow:0 0 26px color-mix(in srgb,var(--a) 30%,transparent)}
@media(hover:hover){.fx-kpi:hover{--ty:-5px;border-color:color-mix(in srgb,var(--a) 45%,transparent);box-shadow:inset 0 1px 0 rgba(255,255,255,.12),var(--plate),0 0 30px -8px color-mix(in srgb,var(--a) 55%,transparent)}
  .fx-kpi:hover .fx-kart svg{transform:scale(1.05) translateY(-3px)}}

@media(max-width:480px){.fx-ico{width:43px;height:43px}.fx-num{font-size:2.1rem}.fx-mt{font-size:1.3rem}}
@media(prefers-reduced-motion:reduce){.fx-root *,.fx-root *::before,.fx-root *::after{animation:none!important;transition:none!important}}


/* =====================================================
   FDTNEXA — STAGE 2: HOLOGRAPHIC ENGINEERING INTERFACE
===================================================== */

/* Dashboard boot-up */
@keyframes fx-boot {
  from { opacity: 0; transform: translateY(18px) scale(.985); filter: brightness(.4); }
  to   { opacity: 1; transform: translateY(0) scale(1); filter: brightness(1); }
}

@keyframes fx-beam {
  0%   { transform: translateY(-130%); opacity: 0; }
  12%  { opacity: .9; }
  88%  { opacity: .9; }
  100% { transform: translateY(250%); opacity: 0; }
}

@keyframes fx-orbit-a {
  to { transform: rotate(360deg); }
}

@keyframes fx-orbit-b {
  to { transform: rotate(-360deg); }
}

@keyframes fx-core-float {
    0%, 100% {
        transform: translateY(0) rotateY(-5deg);
    }

    50% {
        transform: translateY(-12px) rotateY(5deg);
    }
}
@keyframes fx-energy {
  0%,100% { opacity: .25; }
  50% { opacity: .9; }
}

@keyframes fx-grid-pulse {
  0%,100% { opacity: .18; }
  50% { opacity: .4; }
}

/* Boot sequence for the main dashboard sections */
.fx-root .fx-hero,
.fx-root .fx-sec,
.fx-root .fx-vline {
  animation: fx-boot .8s cubic-bezier(.2,.75,.25,1) both;
}

.fx-root .fx-sec:nth-of-type(2) { animation-delay: .12s; }
.fx-root .fx-sec:nth-of-type(3) { animation-delay: .22s; }
.fx-root .fx-sec:nth-of-type(4) { animation-delay: .32s; }


/* Cinematic hologram chamber */
.fx-core-stage {
  position: relative;
  justify-self: center;
  width: min(100%, 390px);
  min-height: 350px;
  display: grid;
  place-items: center;
  isolation: isolate;
  perspective: 1000px;
  overflow: hidden;

  border: 1px solid rgba(56,189,248,.2);

  background:
    radial-gradient(ellipse at 50% 48%,
      rgba(6,182,212,.20), transparent 48%),
    radial-gradient(ellipse at 50% 100%,
      rgba(14,165,233,.13), transparent 65%),
    linear-gradient(145deg,
      rgba(17,42,61,.65), rgba(5,14,27,.35));

  clip-path: polygon(
    0 18px,18px 0,
    calc(100% - 18px) 0,100% 18px,
    100% calc(100% - 18px),
    calc(100% - 18px) 100%,
    18px 100%,0 calc(100% - 18px)
  );

  box-shadow:
    inset 0 0 35px rgba(6,182,212,.08),
    0 0 35px rgba(6,182,212,.07);
}

/* Background technical grid */
.fx-core-stage::before {
  content: "";
  position: absolute;
  inset: 0;
  z-index: -1;

  background-image:
    linear-gradient(rgba(56,189,248,.09) 1px,transparent 1px),
    linear-gradient(90deg,rgba(56,189,248,.09) 1px,transparent 1px);

  background-size: 24px 24px;
  mask-image: radial-gradient(ellipse at center,#000,transparent 78%);
  animation: fx-grid-pulse 5s ease-in-out infinite;
}

/* Moving scan beam */
/* ===== HOLOGRAPHIC SCAN BEAM ===== */

.fx-core-stage::after {
    content: "";
    position: absolute;
    z-index: 3;
    left: 0;
    right: 0;
    top: -45%;
    height: 45%;
    pointer-events: none;

    background: linear-gradient(
        180deg,
        transparent,
        rgba(34, 211, 238, .20),
        rgba(56, 189, 248, .08),
        transparent
    );

    filter: blur(5px);
    animation: fx-beam 5s ease-in-out infinite;
}

/* Light source */

.fx-core-stage {
    --core-light: rgba(34, 211, 238, .22);
}

/* Geological core */

.fx-core-stage .fx-core-svg {
    position: relative;
    z-index: 2;
    width: min(90%, 330px);
    height: auto;
    overflow: visible;
    transform-style: preserve-3d;

    animation: fx-core-float 5s ease-in-out infinite;

    filter:
        drop-shadow(0 12px 12px rgba(0,0,0,.55))
        drop-shadow(0 0 18px rgba(6,182,212,.18));
}

/* Orbit illumination */

.fx-core-stage .fx-orbit-a,
.fx-core-stage .fx-orbit-b {
    filter: drop-shadow(0 0 7px rgba(34,211,238,.35));
}
/* Subtle glass reflection */
.fx-core-stage .fx-core-label {
  text-shadow: 0 0 10px rgba(34,211,238,.45);
}

/* Keep the visual comfortable on smaller screens */
@media (max-width: 480px) {
  .fx-core-stage {
    width: 100%;
    min-height: 320px;
  }

  .fx-core-stage .fx-core-svg {
    width: min(94%,310px);
  }
}

.fx-orbit {
  position: absolute;
  width: 78%;
  aspect-ratio: 1;
  border: 1px solid rgba(34,211,238,.35);
  border-radius: 50%;
  transform: rotateX(68deg);
  box-shadow: 0 0 18px rgba(6,182,212,.12);
}

.fx-orbit::before,
.fx-orbit::after {
  content: "";
  position: absolute;
  inset: 12%;
  border: 1px dashed rgba(56,189,248,.3);
  border-radius: 50%;
}

.fx-orbit::after {
  inset: 26%;
  border-style: solid;
  border-color: rgba(20,184,166,.25);
}

.fx-orbit-a {
  animation: fx-orbit-a 22s linear infinite;
}

.fx-orbit-b {
  width: 94%;
  border-color: rgba(139,92,246,.28);
  transform: rotateX(68deg) rotateZ(55deg);
  animation: fx-orbit-b 30s linear infinite;
}

.fx-orbit-b::before {
  border-color: rgba(139,92,246,.28);
}

.fx-core-label {
  position: absolute;
  z-index: 4;
  font: 600 9px/1.4 ui-monospace,SFMono-Regular,Menlo,monospace;
  letter-spacing: .13em;
  color: #8BE9F7;
  text-transform: uppercase;
  text-shadow: 0 0 10px rgba(6,182,212,.65);
}

.fx-core-label.top {
  top: 17px;
  left: 18px;
}

.fx-core-label.bottom {
  bottom: 17px;
  left: 18px;
}

.fx-depth {
  position: absolute;
  z-index: 4;
  top: 19%;
  right: 13px;
  bottom: 19%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  font: 9px ui-monospace,SFMono-Regular,Menlo,monospace;
  color: rgba(148,216,235,.75);
}

.fx-depth::before {
  content: "";
  position: absolute;
  right: 30px;
  top: 0;
  bottom: 0;
  width: 1px;
  background: repeating-linear-gradient(
    to bottom,#38BDF8 0 2px,transparent 2px 8px
  );
  opacity: .65;
}

.fx-depth span::before {
  content: "";
  display: inline-block;
  width: 7px;
  height: 1px;
  margin: 0 5px 3px 0;
  background: #38BDF8;
}

.fx-core-corner {
  position: absolute;
  z-index: 4;
  width: 17px;
  height: 17px;
  border-color: #22D3EE;
  opacity: .8;
}

.fx-core-corner.tl {
  top: 8px;left: 8px;
  border-top: 1px solid;border-left: 1px solid;
}
.fx-core-corner.tr {
  top: 8px;right: 8px;
  border-top: 1px solid;border-right: 1px solid;
}
.fx-core-corner.bl {
  bottom: 8px;left: 8px;
  border-bottom: 1px solid;border-left: 1px solid;
}
.fx-core-corner.br {
  bottom: 8px;right: 8px;
  border-bottom: 1px solid;border-right: 1px solid;
}

/* Engineering tool portals — angular architecture */
.fx-tool {
  border-radius: 4px 20px 4px 20px;
  border-color: rgba(56,189,248,.3);
  background:
    linear-gradient(135deg,rgba(6,182,212,.08),transparent 38%),
    linear-gradient(155deg,#172c42,#0b1728 75%);
}

.fx-tool .fx-in {
  min-height: 100%;
}

.fx-tool .fx-tag {
  border-radius: 3px;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.fx-tool .fx-cta {
  border-radius: 4px 12px 4px 12px;
  letter-spacing: .015em;
}

.fx-tool .fx-art {
  position: relative;
  filter: drop-shadow(0 0 15px rgba(6,182,212,.13));
}

.fx-tool .fx-art::after {
  content: "";
  position: absolute;
  left: 15%;
  right: 15%;
  bottom: 2px;
  height: 1px;
  background: linear-gradient(90deg,transparent,var(--a),transparent);
  box-shadow: 0 0 12px var(--a);
  animation: fx-energy 3s ease-in-out infinite;
}

/* KPI panels: technical corner details */
.fx-kpi {
  border-radius: 5px 19px 5px 19px;
  background:
    linear-gradient(135deg,color-mix(in srgb,var(--a) 8%,transparent),transparent 42%),
    linear-gradient(155deg,#1b2f48 0%,#0e1b2d 55%,#0a1422 100%);
}

.fx-kpi .fx-kin::before {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: .45;
  background:
    linear-gradient(var(--a),var(--a)) left top / 17px 1px no-repeat,
    linear-gradient(var(--a),var(--a)) left top / 1px 17px no-repeat,
    linear-gradient(var(--a),var(--a)) right bottom / 17px 1px no-repeat,
    linear-gradient(var(--a),var(--a)) right bottom / 1px 17px no-repeat;
}

.fx-kpi .fx-kid {
  color: color-mix(in srgb,var(--a) 65%,#dbeafe);
}

.fx-kpi .fx-knum {
  color: #F0FBFF;
  text-shadow:
    0 0 9px color-mix(in srgb,var(--a) 55%,transparent),
    0 0 30px color-mix(in srgb,var(--a) 28%,transparent);
}

/* Analytics panels become technical readout surfaces */
.fx-root .fx-sec[aria-label="Generation analytics"] .fx-card {
  border-radius: 5px 17px 5px 17px;
  background:
    linear-gradient(145deg,color-mix(in srgb,var(--a) 7%,transparent),transparent 45%),
    linear-gradient(160deg,#14263b,#0d192a);
}

.fx-root .fx-sec[aria-label="Generation analytics"] .fx-in {
  min-height: 100%;
}

/* Mobile and accessibility */
@media(max-width:767px) {
  .fx-core-stage {
    width: min(100%,340px);
    min-height: 310px;
  }

  .fx-core-svg {
    width: min(88%,290px);
  }

  .fx-depth {
    right: 9px;
    font-size: 8px;
  }
}

@media(prefers-reduced-motion:reduce) {
  .fx-core-stage::before,
  .fx-core-stage::after,
  .fx-orbit,
  .fx-core-svg,
  .fx-core-stage .fx-core-label,
  .fx-tool .fx-art::after {
    animation: none !important;
  }
}

/* ===== PERMANENT HOLOGRAPHIC LIGHT ===== */

.fx-core-stage {
    position: relative;
    isolation: isolate;
}

.fx-core-light {
    position: absolute;
    z-index: 0;
    width: 280px;
    height: 280px;
    left: 50%;
    top: 48%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    background: radial-gradient(
        circle,
        rgba(34, 211, 238, .45) 0%,
        rgba(6, 182, 212, .22) 35%,
        rgba(6, 182, 212, .08) 60%,
        transparent 75%
    );
    filter: blur(22px);
    pointer-events: none;
    animation: fx-light-breathe 4s ease-in-out infinite;
}

@keyframes fx-light-breathe {
    0%, 100% {
        opacity: .45;
        scale: .9;
    }
    50% {
        opacity: 1;
        scale: 1.15;
    }
}

/* Floating geological core */

.fx-core-svg {
    position: relative;
    z-index: 2;
    animation: fx-core-float 5s ease-in-out infinite;
    will-change: transform;
}

@keyframes fx-core-float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-12px);
    }
}

/* Orbit rings */

.fx-orbit {
    z-index: 1;
    pointer-events: none;
}

/* Mobile and accessibility */
@media (prefers-reduced-motion: reduce) {
    .fx-core-stage::before,
    .fx-core-stage::after,
    .fx-orbit,
    .fx-core-svg,
    .fx-core-stage .fx-core-label,
    .fx-tool .fx-art::after,
    .fx-core-light {
        animation: none !important;
    }
}


/* =========================================
   STAGE 3 — HOLOGRAPHIC ENERGY CONNECTIONS
========================================= */

/* Energy field around the engineering tools */
.fx-root .fx-sec[aria-labelledby="fx-tools"] {
    position: relative;
    isolation: isolate;
}

/* Signal line above the two tool modules */
.fx-root .fx-sec[aria-labelledby="fx-tools"]::before {
    content: "";
    position: absolute;
    top: -22px;
    left: 50%;
    width: min(72%, 620px);
    height: 1px;
    transform: translateX(-50%);
    background: linear-gradient(
        90deg,
        transparent,
        rgba(34, 211, 238, .12),
        rgba(34, 211, 238, .8),
        rgba(20, 184, 166, .5),
        transparent
    );
    box-shadow: 0 0 12px rgba(34, 211, 238, .35);
    pointer-events: none;
}

/* Moving energy pulse */
.fx-root .fx-sec[aria-labelledby="fx-tools"]::after {
    content: "";
    position: absolute;
    top: -25px;
    left: 14%;
    width: 48px;
    height: 7px;
    border-radius: 50%;
    background: rgba(103, 232, 249, .9);
    filter: blur(5px);
    box-shadow: 0 0 14px 4px rgba(34, 211, 238, .45);
    animation: fx-energy-travel 5s ease-in-out infinite;
    pointer-events: none;
}

@keyframes fx-energy-travel {
    0% {
        left: 14%;
        opacity: 0;
    }
    15% {
        opacity: 1;
    }
    85% {
        opacity: 1;
    }
    100% {
        left: 84%;
        opacity: 0;
    }
}

/* Tool cards receive a subtle energy edge */
.fx-root .fx-tool {
    position: relative;
    isolation: isolate;
    overflow: hidden;
}

/* Animated light passing around the card */
.fx-root .fx-tool::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: inherit;
    padding: 1px;
    background: linear-gradient(
        115deg,
        transparent 10%,
        rgba(34, 211, 238, .05) 30%,
        var(--a) 50%,
        rgba(34, 211, 238, .08) 70%,
        transparent 90%
    );
    background-size: 250% 100%;
    animation: fx-card-energy 7s linear infinite;
    pointer-events: none;

    /* Keep the effect only on the card edge */
    -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
}

@keyframes fx-card-energy {
    from { background-position: 100% 0; }
    to   { background-position: -150% 0; }
}

/* Soft receiving glow on hover */
.fx-root .fx-tool:hover {
    box-shadow:
        0 0 0 1px color-mix(in srgb, var(--a) 35%, transparent),
        0 0 28px color-mix(in srgb, var(--a) 15%, transparent),
        0 22px 55px rgba(0, 0, 0, .25);
}

/* Subtle illumination behind the tool illustration */
.fx-root .fx-tool .fx-art {
    position: relative;
}

.fx-root .fx-tool .fx-art::before {
    content: "";
    position: absolute;
    width: 100px;
    height: 100px;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    background: var(--a);
    opacity: .08;
    filter: blur(35px);
    pointer-events: none;
    transition: opacity .4s ease, transform .4s ease;
}

.fx-root .fx-tool:hover .fx-art::before {
    opacity: .22;
    transform: translate(-50%, -50%) scale(1.3);
}

/* KPI panels: gentle illuminated edge */
.fx-root .fx-kpi {
    transition:
        border-color .35s ease,
        box-shadow .35s ease,
        transform .35s ease;
}

.fx-root .fx-kpi:hover {
    border-color: color-mix(in srgb, var(--a) 55%, transparent);
    box-shadow:
        0 0 22px color-mix(in srgb, var(--a) 13%, transparent),
        0 18px 45px rgba(0, 0, 0, .2);
}

/* Respect reduced-motion preferences */
@media (prefers-reduced-motion: reduce) {
    .fx-root .fx-sec[aria-labelledby="fx-tools"]::after,
    .fx-root .fx-tool::before {
        animation: none !important;
    }
}

/* STAGE 4 — INTERACTIVE HOLOGRAPHIC DEPTH */

.fx-root .fx-core-stage {
    transform:
        perspective(1000px)
        rotateX(var(--stage-rx, 0deg))
        rotateY(var(--stage-ry, 0deg));

    transform-style: preserve-3d;
    transition: transform .25s ease-out;
    will-change: transform;
}


/* ==========================================
   FDTNEXA — CLEAN PROFESSIONAL FOOTER
========================================== */

.fx-root .fx-foot {
    position: relative;
    isolation: isolate;
    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    min-height: 72px;
    padding: 12px 10px;

    border-top: 1px solid rgba(34,211,238,.10);
}

/* Moving background signature */
.fx-root .fx-credit-track {
    position: absolute;
    z-index: 1;
    inset: 0;

    display: flex;
    align-items: center;

    width: 100%;
    overflow: hidden;
    white-space: nowrap;

    pointer-events: none;
}

.fx-root .fx-credit {
    position: relative;
    display: inline-block;
    flex-shrink: 0;

    padding-left: 100%;

    color: rgba(34,211,238,.16);
    font-size: clamp(18px, 3vw, 28px);
    font-weight: 800;
    letter-spacing: 4px;

    text-shadow:
        0 0 8px rgba(34,211,238,.35),
        0 0 18px rgba(20,184,166,.22);

    animation: fx-credit-scroll 22s linear infinite;
}

/* Text motion */
@keyframes fx-credit-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-100%); }
}

/* Foreground content */
.fx-root .fx-foot-content {
    position: relative;
    z-index: 3;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 5px;
    text-align: center;
}

/* Brand row */
.fx-root .fx-foot-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;

    gap: 8px;

    color: rgba(203,213,225,.85);
    font-size: 11px;
    line-height: 1.6;
}

/* Studio row */
.fx-root .fx-foot-product {
    color: rgba(148,163,184,.65);
    font-size: 10px;
    letter-spacing: .5px;
}

/* Construction scene spacing */
.fx-root .fx-scene {
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden;
}

.fx-root .fx-scene svg {
    display: block;
    width: 100%;
    height: auto;
}

/* Mobile */
@media (max-width:480px) {

    .fx-root .fx-foot {
        min-height: 65px;
        padding: 10px 6px;
    }

    .fx-root .fx-foot-brand {
        font-size: 10px;
        gap: 5px;
    }

    .fx-root .fx-foot-product {
        font-size: 9px;
    }

    .fx-root .fx-credit {
        font-size: 18px;
        letter-spacing: 3px;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion:reduce) {
    .fx-root .fx-credit {
        animation: none;
        padding-left: 0;
        opacity: .5;
    }
}

</style>

<div class="fx-root">
  <!-- shared SVG definitions (sprite) -->
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      <pattern id="fxg" width="48" height="27.7" patternUnits="userSpaceOnUse"><path d="M0 13.85 24 0l24 13.85M0 13.85l24 13.85 24-13.85" fill="none" stroke="#38BDF8" stroke-opacity=".11"/></pattern>
      <path id="fxc" d="M0-210C160-220 300-140 340-30 380 80 280 180 120 210-40 240-220 190-280 80-340-30-160-200 0-210Z" fill="none"/>
      <pattern id="fxh" width="10" height="10" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0v10" stroke="#38BDF8" stroke-opacity=".16"/></pattern>
      <linearGradient id="fxgd" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#14566a" stop-opacity=".6"/><stop offset="1" stop-color="#0B1220" stop-opacity="0"/></linearGradient>
      <g id="fxs"><g transform="translate(20 -80)">
        <polygon points="100,80 200,120 100,160 0,120" style="fill:var(--c)"/>
        <polygon points="0,120 100,160 100,182 0,142" style="fill:var(--c)"/><polygon points="0,120 100,160 100,182 0,142" fill="#000" fill-opacity=".32"/>
        <polygon points="100,160 200,120 200,142 100,182" style="fill:var(--c)"/><polygon points="100,160 200,120 200,142 100,182" fill="#000" fill-opacity=".16"/>
        <polygon points="100,80 200,120 100,160 0,120" fill="#fff" fill-opacity=".08"/>
      </g></g>
    </defs>
  </svg>

  <svg class="fx-bg" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <rect width="1200" height="700" fill="url(#fxg)"/>
    <g transform="translate(820 300)" stroke="#06B6D4" stroke-width="1.2">
      <use href="#fxc" transform="scale(1.25) rotate(8)" stroke-opacity=".10"/><use href="#fxc" transform="scale(1.02) rotate(4)" stroke-opacity=".14"/>
      <use href="#fxc" transform="scale(.8)" stroke-opacity=".18"/><use href="#fxc" transform="scale(.58) rotate(-6)" stroke-opacity=".24"/>
      <use href="#fxc" transform="scale(.36) rotate(-10)" stroke-opacity=".32"/>
    </g>
  </svg>

  <div class="fx-wrap">

    <!-- Hero -->
    <section class="fx-hero">
      <div>
        <p class="fx-k" style="color:var(--cyan)">FDTNexa &middot; Geotechnical workspace</p>
        <h1 class="fx-h1">{{ $first !== '' ? 'Welcome back, '.$first.'.' : 'Welcome to FDTNexa.' }}<br><em>Test, calculate, report.</em></h1>
        <p class="fx-lead">Your professional workspace for field density testing and geotechnical engineering calculations. Generate reports and soil layers in seconds.</p>
        <span class="fx-status"><b></b>Engineering workspace active</span>
      </div>
      
<div class="fx-core-stage" aria-label="Holographic geological soil core visualization">
<div class="fx-core-light" aria-hidden="true"></div>
  <span class="fx-core-label top">FDTNEXA // SUBSURFACE SCAN</span>

  <span class="fx-core-corner tl"></span>
  <span class="fx-core-corner tr"></span>
  <span class="fx-core-corner bl"></span>
  <span class="fx-core-corner br"></span>

  <div class="fx-orbit fx-orbit-a"></div>
  <div class="fx-orbit fx-orbit-b"></div>

  <div class="fx-depth" aria-hidden="true">
    <span>00 M</span>
    <span>02 M</span>
    <span>04 M</span>
    <span>06 M</span>
    <span>08 M</span>
  </div>

  <svg class="fx-core-svg" viewBox="0 0 300 340" role="img"
       aria-label="Exploded geological soil core with sand, gravel, clay, weathered rock and bedrock layers">
    <defs>
      <linearGradient id="coreSand" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="#F5D99A"/>
        <stop offset="1" stop-color="#A9824A"/>
      </linearGradient>
      <linearGradient id="coreGravel" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="#AAB7C5"/>
        <stop offset="1" stop-color="#566879"/>
      </linearGradient>
      <linearGradient id="coreClay" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="#A87551"/>
        <stop offset="1" stop-color="#55372D"/>
      </linearGradient>
      <linearGradient id="coreRock" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="#8A91A0"/>
        <stop offset="1" stop-color="#343D50"/>
      </linearGradient>
      <linearGradient id="coreBedrock" x1="0" y1="0" x2="1" y2="1">
        <stop stop-color="#5650A0"/>
        <stop offset="1" stop-color="#191C3C"/>
      </linearGradient>
      <radialGradient id="coreGlow">
        <stop stop-color="#22D3EE" stop-opacity=".4"/>
        <stop offset="1" stop-color="#22D3EE" stop-opacity="0"/>
      </radialGradient>
      <filter id="coreShadow" x="-40%" y="-40%" width="180%" height="180%">
        <feGaussianBlur stdDeviation="8"/>
      </filter>
    </defs>

    <ellipse cx="150" cy="300" rx="100" ry="20" fill="url(#coreGlow)" filter="url(#coreShadow)"/>

    <!-- Core segment 1: sand -->
    <g transform="translate(0 0)">
      <path d="M91 42 L150 18 L209 42 L150 67Z" fill="#F8E3B4" stroke="#67E8F9" stroke-opacity=".8"/>
      <path d="M91 42 L150 67 L150 91 L91 66Z" fill="url(#coreSand)" stroke="#67E8F9" stroke-opacity=".5"/>
      <path d="M150 67 L209 42 L209 66 L150 91Z" fill="#A9824A" stroke="#67E8F9" stroke-opacity=".5"/>
      <path d="M105 43l45-18 43 18-43 18z" fill="#fff" opacity=".08"/>
      <g fill="#fff1c8" opacity=".65">
        <circle cx="120" cy="44" r="2"/><circle cx="145" cy="35" r="1.6"/>
        <circle cx="170" cy="44" r="2"/><circle cx="155" cy="51" r="1.5"/>
      </g>
    </g>

    <!-- Core segment 2: gravel -->
    <g transform="translate(0 48)">
      <path d="M91 42 L150 18 L209 42 L150 67Z" fill="#C2CCD5" stroke="#67E8F9" stroke-opacity=".7"/>
      <path d="M91 42 L150 67 L150 88 L91 63Z" fill="url(#coreGravel)" stroke="#67E8F9" stroke-opacity=".4"/>
      <path d="M150 67 L209 42 L209 63 L150 88Z" fill="#485B6C" stroke="#67E8F9" stroke-opacity=".4"/>
      <g fill="#52677A" stroke="#D8E6F0" stroke-opacity=".45">
        <path d="M112 41l10-8 9 7-7 8z"/><path d="M139 32l9-5 9 8-11 6z"/>
        <path d="M163 43l11-8 9 7-12 8z"/>
      </g>
    </g>

    <!-- Core segment 3: clay -->
    <g transform="translate(0 96)">
      <path d="M91 42 L150 18 L209 42 L150 67Z" fill="#B98461" stroke="#67E8F9" stroke-opacity=".7"/>
      <path d="M91 42 L150 67 L150 88 L91 63Z" fill="url(#coreClay)" stroke="#67E8F9" stroke-opacity=".4"/>
      <path d="M150 67 L209 42 L209 63 L150 88Z" fill="#603C2E" stroke="#67E8F9" stroke-opacity=".4"/>
      <path d="M110 43l40-16 39 16-39 16z" fill="#E7B18B" opacity=".15"/>
      <path d="M110 51l39 16M130 34l20 9" stroke="#E8B99A" stroke-opacity=".35"/>
    </g>

    <!-- Core segment 4: weathered rock -->
    <g transform="translate(0 144)">
      <path d="M91 42 L150 18 L209 42 L150 67Z" fill="#9AA6B3" stroke="#67E8F9" stroke-opacity=".7"/>
      <path d="M91 42 L150 67 L150 88 L91 63Z" fill="url(#coreRock)" stroke="#67E8F9" stroke-opacity=".4"/>
      <path d="M150 67 L209 42 L209 63 L150 88Z" fill="#303B50" stroke="#67E8F9" stroke-opacity=".4"/>
      <path d="M119 42l15-13 11 10-13 10zM158 45l16-13 10 8-16 12z"
            fill="#D1DCE8" opacity=".4"/>
    </g>

    <!-- Core segment 5: bedrock -->
    <g transform="translate(0 192)">
      <path d="M91 42 L150 18 L209 42 L150 67Z" fill="#7773B6" stroke="#67E8F9" stroke-opacity=".8"/>
      <path d="M91 42 L150 67 L150 91 L91 66Z" fill="url(#coreBedrock)" stroke="#67E8F9" stroke-opacity=".5"/>
      <path d="M150 67 L209 42 L209 66 L150 91Z" fill="#24264D" stroke="#67E8F9" stroke-opacity=".5"/>
      <path d="M115 42l35-14 37 14-37 15z" fill="#B8B4FF" opacity=".12"/>
      <path d="M124 48l26 12 25-11" fill="none" stroke="#A5B4FC" stroke-opacity=".5"/>
    </g>

    <!-- Measurement lines -->
    <g fill="none" stroke="#67E8F9" stroke-width="1" opacity=".65">
      <path d="M70 42H88M70 90H88M70 138H88M70 186H88M70 234H88M70 282H88"/>
      <path d="M76 42V282" stroke-dasharray="2 5"/>
      <path d="M212 42h15M212 90h15M212 138h15M212 186h15M212 234h15M212 282h15"/>
    </g>

    <!-- Floating scan target -->
    <g fill="none" stroke="#22D3EE" opacity=".85">
      <circle cx="150" cy="14" r="8" stroke-dasharray="3 3"/>
      <path d="M150 2v-9M150 27v-8M138 14h-9M171 14h-9"/>
    </g>
  </svg>

  <span class="fx-core-label bottom">CORE ANALYSIS // ACTIVE</span>
</div>

    </section>

    <!-- Engineering tools (primary) -->
    <section class="fx-sec" aria-labelledby="fx-tools">
      <h2 class="fx-h2" id="fx-tools">Engineering tools</h2>
      <p class="fx-sub">Select a module to start your work.</p>
      <div class="fx-g2">
        <a href="{{ route('field-tool.index') }}" class="fx-card fx-tool" style="--a:var(--cyan)"><div class="fx-in">
          <div class="fx-art" aria-hidden="true">
            <svg viewBox="0 0 160 150"><g class="l">
              <path class="nf" d="M0 118h160"/><rect x="18" y="113" width="84" height="5"/>
              <path d="M40 113 54 96h12l14 17"/><rect x="52" y="88" width="16" height="8"/>
              <rect x="36" y="34" width="48" height="54" rx="8"/><path d="M36 64h48v16a8 8 0 0 1-8 8H44a8 8 0 0 1-8-8z" style="fill:color-mix(in srgb,var(--a) 28%,transparent);stroke:none"/>
              <path class="nf" d="M36 52h10M36 64h48M36 76h10"/>
              <path class="dm" d="M44 118v22h32v-22M118 118v22M112 118h12M112 140h12"/>
              <circle cx="54" cy="134" r="1.4" fill="var(--a)" stroke="none"/><circle cx="62" cy="130" r="1.4" fill="var(--a)" stroke="none"/><circle cx="68" cy="136" r="1.4" fill="var(--a)" stroke="none"/>
            </g></svg>
          </div>
          <div>
            <div class="fx-row fx-mid"><span class="fx-ico"><svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/><path d="M8 15h8"/></svg></span><span class="fx-tag">Module 01</span></div>
            <h3 class="fx-mt">Field Density Test</h3>
            <p class="fx-d">Generate field density test calculations and professional Excel reports for geotechnical quality control.</p>
            <ul class="fx-chips"><li>Sand-cone method</li><li>Excel report</li></ul>
            <span class="fx-cta">Open Field Density Test <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
          </div>
        </div></a>

        <a href="{{ route('bulk.index') }}" class="fx-card fx-tool" style="--a:var(--teal)"><div class="fx-in">
          <div class="fx-art" aria-hidden="true">
            <svg viewBox="0 0 240 250">
              <g class="s" style="--d:-1.2s"><use href="#fxs" y="140" style="--c:#134e4a"/></g><g class="s" style="--d:-.9s"><use href="#fxs" y="112" style="--c:#0f766e"/></g>
              <g class="s" style="--d:-.6s"><use href="#fxs" y="84" style="--c:#0d9488"/></g><g class="s" style="--d:-.3s"><use href="#fxs" y="56" style="--c:#14b8a6"/></g>
              <g class="s" style="--d:0s"><use href="#fxs" y="28" style="--c:#2dd4bf"/></g>
            </svg>
          </div>
          <div>
            <div class="fx-row fx-mid"><span class="fx-ico"><svg viewBox="0 0 24 24"><path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg></span><span class="fx-tag">Module 02</span></div>
            <h3 class="fx-mt">Bulk Layer Generator</h3>
            <p class="fx-d">Generate multiple soil layers and their corresponding FDT Excel sheets in one operation.</p>
            <ul class="fx-chips"><li>Up to 30 layers</li><li>One Excel workbook</li></ul>
            <span class="fx-cta">Open Bulk Generator <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
          </div>
        </div></a>
      </div>
    </section>

    <i class="fx-vline" aria-hidden="true"></i>

    @if($hasActivity)
    <!-- Generation statistics -->
    @php $h1 = max(3, round($pOne * 0.8)); $h2 = max(3, round($pBulk * 0.8)); @endphp
    <section class="fx-sec" style="margin-top:1.5rem" aria-labelledby="fx-stats">
      <h2 class="fx-h2" id="fx-stats">Your generation statistics</h2>
      <p class="fx-sub">Track your FDT reports and bulk generation activity.</p>
      <div class="fx-g4">

        <!-- KPI 01 · FDT reports -->
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#36D9F5"><div class="fx-kin">
          <div class="fx-kart" style="--d:0s" aria-hidden="true">
            <svg viewBox="0 0 160 160">
              <defs><linearGradient id="k1s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2a5776"/><stop offset="1" stop-color="#0e2236"/></linearGradient>
              <radialGradient id="k1b"><stop offset="0" stop-color="#36D9F5" stop-opacity=".55"/><stop offset="1" stop-color="#36D9F5" stop-opacity="0"/></radialGradient></defs>
              <ellipse cx="80" cy="148" rx="54" ry="9" fill="url(#k1b)"/>
              <g><path d="M34 48l64-18 18 70-64 18z" fill="#071522"/><path d="M34 44l64-18 18 70-64 18z" fill="url(#k1s)" stroke="#36D9F5" stroke-opacity=".3"/></g>
              <g transform="translate(5 7)"><path d="M34 48l64-18 18 70-64 18z" fill="#071522"/><path d="M34 44l64-18 18 70-64 18z" fill="url(#k1s)" stroke="#36D9F5" stroke-opacity=".45"/></g>
              <g transform="translate(10 14)"><path d="M34 48l64-18 18 70-64 18z" fill="#071522"/><path d="M34 44l64-18 18 70-64 18z" fill="url(#k1s)" stroke="#36D9F5" stroke-opacity=".6"/>
                <path d="M34 44l64-18" stroke="#EAF6FF" stroke-opacity=".75" stroke-width="1.4"/>
                <path d="M36.7 54.5l28-8" stroke="#36D9F5" stroke-width="2.2" stroke-linecap="round"/>
                <path d="M39.4 65l46-13M42.1 75.5l46-13M44.8 86l46-13" stroke="#8DA8BC" stroke-opacity=".7" stroke-width="1.4" stroke-linecap="round"/>
                <circle cx="100" cy="86" r="8" fill="#4CE6C0" fill-opacity=".9"/><path d="M96 86l3 3 5-6" fill="none" stroke="#06202a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></g>
              <circle cx="132" cy="38" r="2" fill="#36D9F5"/><circle cx="24" cy="104" r="1.6" fill="#4CE6C0"/>
            </svg>
          </div>
          <span class="fx-kid">KPI &middot; 01</span>
          <div class="fx-kb">
            <span class="fx-knum" data-count="{{ (int) ($fieldReports ?? 0) }}">{{ number_format($fieldReports ?? 0) }}</span>
            <p class="fx-k" style="margin-top:.8rem">FDT Reports Generated</p>
            <p class="fx-d">Individual field density reports</p>
          </div>
        </div></div><i class="fx-conn" aria-hidden="true"></i></div>

        <!-- KPI 02 · bulk files -->
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#4CE6C0"><div class="fx-kin">
          <div class="fx-kart" style="--d:-1.4s" aria-hidden="true">
            <svg viewBox="0 0 160 160">
              <defs><linearGradient id="k2s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1f5a5a"/><stop offset="1" stop-color="#0c2530"/></linearGradient>
              <linearGradient id="k2h" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#4CE6C0"/><stop offset="1" stop-color="#1c9b86"/></linearGradient>
              <radialGradient id="k2b"><stop offset="0" stop-color="#4CE6C0" stop-opacity=".5"/><stop offset="1" stop-color="#4CE6C0" stop-opacity="0"/></radialGradient></defs>
              <ellipse cx="80" cy="148" rx="54" ry="9" fill="url(#k2b)"/>
              <g transform="translate(8 -10)"><path d="M34 50l66 16 14 64-66-16z" fill="#071a1e"/><path d="M34 46l66 16 14 64-66-16z" fill="url(#k2s)" stroke="#4CE6C0" stroke-opacity=".3"/></g>
              <g transform="translate(4 -5)"><path d="M34 50l66 16 14 64-66-16z" fill="#071a1e"/><path d="M34 46l66 16 14 64-66-16z" fill="url(#k2s)" stroke="#4CE6C0" stroke-opacity=".45"/></g>
              <g><path d="M34 50l66 16 14 64-66-16z" fill="#071a1e"/><path d="M34 46l66 16 14 64-66-16z" fill="url(#k2s)" stroke="#4CE6C0" stroke-opacity=".65"/>
                <path d="M34 46l66 16 2.5 11.5-66-16z" fill="url(#k2h)" fill-opacity=".85"/>
                <path d="M39.6 71.6l66 16M42.7 85.7l66 16M45.8 99.8l66 16M55.8 51.3l14 64M77.6 56.6l14 64" stroke="#8DE8D2" stroke-opacity=".38" stroke-width="1"/>
                <circle cx="108" cy="54" r="11" fill="url(#k2h)"/><path d="M103 49l10 10M113 49l-10 10" stroke="#06202a" stroke-width="2.2" stroke-linecap="round"/></g>
              <circle cx="22" cy="86" r="1.8" fill="#4CE6C0"/><circle cx="134" cy="22" r="1.6" fill="#36D9F5"/>
            </svg>
          </div>
          <span class="fx-kid">KPI &middot; 02</span>
          <div class="fx-kb">
            <span class="fx-knum" data-count="{{ (int) ($bulkFiles ?? 0) }}">{{ number_format($bulkFiles ?? 0) }}</span>
            <p class="fx-k" style="margin-top:.8rem">Bulk Files Generated</p>
            <p class="fx-d">Total successful bulk operations</p>
          </div>
        </div></div><i class="fx-conn" aria-hidden="true"></i></div>

        <!-- KPI 03 · soil layers (geological cutaway) -->
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#8B5CF6"><div class="fx-kin">
          <div class="fx-kart" style="--d:-2.6s" aria-hidden="true">
            <svg viewBox="0 0 200 190">
              <defs>
                <pattern id="k3g" width="7" height="7" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1" fill="#d7dee8" fill-opacity=".7"/><circle cx="5.5" cy="5" r=".8" fill="#d7dee8" fill-opacity=".5"/></pattern>
                <radialGradient id="k3b"><stop offset="0" stop-color="#8B5CF6" stop-opacity=".5"/><stop offset="1" stop-color="#36D9F5" stop-opacity="0"/></radialGradient>
              </defs>
              <ellipse cx="100" cy="184" rx="66" ry="9" fill="url(#k3b)"/>
              <g transform="translate(0 26)">
                <!-- strata: sand / gravel / clay / bedrock -->
                <path d="M32 56l68 34v13l-68-34z" fill="#d6b877"/><path d="M32 69l68 34v17l-68-34z" fill="#8b96a6"/><path d="M32 86l68 34v17l-68-34z" fill="#7b5638"/><path d="M32 103l68 34v13l-68-34z" fill="#4a3f86"/>
                <path d="M32 69l68 34v17l-68-34z" fill="url(#k3g)"/><path d="M32 56l68 34v60l-68-34z" fill="#000" fill-opacity=".28"/>
                <path d="M100 90l68-34v13l-68 34z" fill="#c9a865"/><path d="M100 103l68-34v17l-68 34z" fill="#9aa5b4"/><path d="M100 120l68-34v17l-68 34z" fill="#8a6141"/><path d="M100 137l68-34v13l-68 34z" fill="#5a4d9c"/>
                <path d="M100 103l68-34v17l-68 34z" fill="url(#k3g)"/><path d="M100 90l68-34v60l-68 34z" fill="#000" fill-opacity=".1"/>
                <path d="M100 22l68 34-68 34-68-34z" fill="#e8d4a2"/><path d="M100 22l68 34-68 34-68-34z" fill="#36D9F5" fill-opacity=".1"/>
                <path d="M32 56l68-34 68 34M32 56v60M168 56v60M100 90v60" fill="none" stroke="#36D9F5" stroke-opacity=".85" stroke-width="1.3" stroke-linejoin="round"/>
                <path d="M32 116l68 34 68-34" fill="none" stroke="#8B5CF6" stroke-width="1.6" stroke-linejoin="round"/>
                <!-- floating exploded layer + depth gauge -->
                <g class="s" style="--d:-1s"><path d="M100 -6l68 34-68 34-68-34z" fill="#5eead4" fill-opacity=".22" stroke="#5eead4" stroke-opacity=".9"/><path d="M32 28v6l68 34 68-34v-6" fill="none" stroke="#5eead4" stroke-opacity=".55"/></g>
                <path d="M182 56v60M177 56h10M177 116h10" stroke="#EAF6FF" stroke-opacity=".6" stroke-dasharray="3 3"/><circle cx="182" cy="86" r="3" fill="#8B5CF6"/>
              </g>
            </svg>
          </div>
          <span class="fx-kid">KPI &middot; 03</span>
          <div class="fx-kb">
            <span class="fx-knum" data-count="{{ (int) ($bulkLayers ?? 0) }}">{{ number_format($bulkLayers ?? 0) }}</span>
            <p class="fx-k" style="margin-top:.8rem">Soil Layers Generated</p>
            <p class="fx-d">Combined layers across all bulk files</p>
          </div>
        </div></div><i class="fx-conn" aria-hidden="true"></i></div>

        <!-- KPI 04 · FDT sheets (real derived total; holographic bars use real shares) -->
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#F5A524"><div class="fx-kin">
          <div class="fx-kart" style="--d:-3.8s" aria-hidden="true">
            <svg viewBox="0 0 160 160">
              <defs><radialGradient id="k4b"><stop offset="0" stop-color="#F5A524" stop-opacity=".45"/><stop offset="1" stop-color="#36D9F5" stop-opacity="0"/></radialGradient>
              <linearGradient id="k4p" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#36D9F5" stop-opacity=".22"/><stop offset="1" stop-color="#36D9F5" stop-opacity=".04"/></linearGradient></defs>
              <ellipse cx="80" cy="148" rx="58" ry="9" fill="url(#k4b)"/>
              <path d="M20 124 81 94l61 30-61 30z" fill="url(#k4p)" stroke="#36D9F5" stroke-opacity=".7"/>
              <path d="M40.3 114l61 30M60.7 104l61 30M122 114l-61 30M101.7 104l-61 30" stroke="#36D9F5" stroke-opacity=".28"/>
              <g><path d="M48 {{ 124 - $h1 }}l12-6 12 6v{{ $h1 }}l-12 6-12-6z" fill="#1aa3bd"/><path d="M60 {{ 124 - $h1 + 6 }}l12-6v{{ $h1 }}l-12 6z" fill="#36D9F5"/><path d="M48 {{ 124 - $h1 }}l12-6 12 6-12 6z" fill="#b7f3ff"/>
                <text x="60" y="{{ 124 - $h1 - 10 }}" font-size="9" font-weight="700" text-anchor="middle" fill="#EAF6FF">{{ $pOne }}%</text></g>
              <g><path d="M88 {{ 124 - $h2 }}l12-6 12 6v{{ $h2 }}l-12 6-12-6z" fill="#0f8f7e"/><path d="M100 {{ 124 - $h2 + 6 }}l12-6v{{ $h2 }}l-12 6z" fill="#4CE6C0"/><path d="M88 {{ 124 - $h2 }}l12-6 12 6-12 6z" fill="#c4fbee"/>
                <text x="100" y="{{ 124 - $h2 - 10 }}" font-size="9" font-weight="700" text-anchor="middle" fill="#EAF6FF">{{ $pBulk }}%</text></g>
              <ellipse cx="80" cy="16" rx="34" ry="8" fill="none" stroke="#F5A524" stroke-opacity=".75" stroke-dasharray="4 5"/><circle cx="114" cy="16" r="2.6" fill="#F5A524"/>
            </svg>
          </div>
          <span class="fx-kid">KPI &middot; 04</span>
          <div class="fx-kb">
            <span class="fx-knum" data-count="{{ (int) $sheets }}">{{ number_format($sheets) }}</span>
            <p class="fx-k" style="margin-top:.8rem">FDT Sheets Produced</p>
            <p class="fx-d">Single reports plus bulk layers</p>
          </div>
        </div></div></div>

      </div>
    </section>

    <!-- Analytics (all values derived from the three totals) -->
    <section class="fx-sec" aria-label="Generation analytics">
      <div class="fx-g3">
        <div class="fx-card" style="--a:var(--cyan)"><div class="fx-in">
          <h3 class="fx-k">Output mix</h3><p class="fx-d">FDT sheets by how they were made</p>
          <div class="fx-ring">
            <svg class="fx-donut" viewBox="0 0 100 100" role="img" aria-label="{{ $pOne }}% single reports, {{ $pBulk }}% bulk layers">
              <circle class="tr" cx="50" cy="50" r="40"/>
              @if($sheets)
              <circle class="sg" cx="50" cy="50" r="40" stroke="#06B6D4" stroke-dasharray="{{ $a }} {{ $C }}"/>
              <circle class="sg" cx="50" cy="50" r="40" stroke="#14B8A6" stroke-dasharray="{{ $b }} {{ $C }}" stroke-dashoffset="-{{ $a }}"/>
              @endif
              <text x="50" y="52" font-size="17">{{ number_format($sheets) }}</text><text class="sm" x="50" y="63">FDT sheets</text>
            </svg>
            <ul class="fx-leg">
              <li><span><i style="--c:#06B6D4"></i>Single reports</span><b>{{ $pOne }}%</b></li>
              <li><span><i style="--c:#14B8A6"></i>Bulk layers</span><b>{{ $pBulk }}%</b></li>
            </ul>
          </div>
        </div></div>

        <div class="fx-card" style="--a:var(--teal)"><div class="fx-in">
          <h3 class="fx-k">Bulk efficiency</h3><p class="fx-d">Average layers per bulk file</p>
          <div class="fx-ring">
            <svg class="fx-donut" viewBox="0 0 100 100" role="img" aria-label="{{ $avg }} layers per bulk file on average, out of a maximum of 30">
              <circle class="tr" cx="50" cy="50" r="40"/>
              @if($avg)<circle class="sg" cx="50" cy="50" r="40" stroke="#14B8A6" stroke-linecap="round" stroke-dasharray="{{ $g }} {{ $C }}"/>@endif
              <text x="50" y="52" font-size="19">{{ $avg }}</text><text class="sm" x="50" y="63">of 30 max</text>
            </svg>
            <ul class="fx-leg">
              <li><span><i style="--c:#38BDF8"></i>Bulk files</span><b>{{ number_format($bf) }}</b></li>
              <li><span><i style="--c:#14B8A6"></i>Soil layers</span><b>{{ number_format($bl) }}</b></li>
              <li><span><i style="--c:#64748B"></i>Max per file</span><b>30</b></li>
            </ul>
          </div>
          @unless($bf)<p class="fx-note">Run the bulk generator to see efficiency.</p>@endunless
        </div></div>

        <div class="fx-card fx-last" style="--a:var(--sky)"><div class="fx-in">
          <h3 class="fx-k">Relative volume</h3><p class="fx-d">Each total compared with your largest</p>
          <div class="fx-bar"><div><span>FDT reports</span><b>{{ number_format($fr) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#06B6D4;width:{{ round($fr / $max * 100) }}%"></div></div></div>
          <div class="fx-bar"><div><span>Bulk files</span><b>{{ number_format($bf) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#38BDF8;width:{{ round($bf / $max * 100) }}%"></div></div></div>
          <div class="fx-bar"><div><span>Soil layers</span><b>{{ number_format($bl) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#14B8A6;width:{{ round($bl / $max * 100) }}%"></div></div></div>
        </div></div>
      </div>
    </section>
    @else
    <!-- Empty state: no activity yet -->
    <section class="fx-sec" style="margin-top:1.5rem" aria-labelledby="fx-stats">
      <div class="fx-card" style="--a:var(--sky)"><div class="fx-in fx-start">
        <span class="fx-ico"><svg viewBox="0 0 24 24"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
        <div class="fx-min" style="flex:1;min-width:220px">
          <h2 class="fx-h2" id="fx-stats">Your statistics start here</h2>
          <p class="fx-d">Run a Field Density Test or the Bulk Layer Generator. Your generation statistics and charts appear here after your first successful generation.</p>
        </div>
      </div></div>
    </section>
    @endif

    <!-- Account & services -->
    <section class="fx-sec" aria-labelledby="fx-acc">
      <h2 class="fx-h2" id="fx-acc" style="margin-bottom:1.25rem">Account &amp; services</h2>
      <div class="fx-g2">
        <a href="{{ route('pricing') }}" class="fx-card" style="--a:var(--sky)"><div class="fx-in fx-row fx-mid fx-gap">
          <span class="fx-ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg></span>
          <span class="fx-min"><span class="fx-t fx-blk" style="margin:0">Plans &amp; Pricing</span><span class="fx-d fx-blk">Explore FDTNexa plans and subscriptions.</span></span>
        </div></a>
        <a href="{{ route('profile.edit') }}" class="fx-card" style="--a:var(--teal)"><div class="fx-in fx-row fx-mid fx-gap">
          <span class="fx-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
          <span class="fx-min"><span class="fx-t fx-blk" style="margin:0">My Profile</span><span class="fx-d fx-blk">Manage your account information and settings.</span></span>
        </div></a>
      </div>
    </section>

 
<footer class="fx-foot">

    <!-- Animated background credit -->
    <div class="fx-credit-track" aria-hidden="true">
        <span class="fx-credit">MADE BY M. ADNAN</span>
    </div>

    <!-- Main footer content -->
    <div class="fx-foot-content">

        <div class="fx-foot-brand">
            <svg width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="#14B8A6" stroke-width="2.2"
                 stroke-linejoin="round" stroke-linecap="round"
                 aria-hidden="true">
                <path d="m12 3 9 5-9 5-9-5z"/>
                <path d="m3 13 9 5 9-5"/>
            </svg>

            <span>
                &copy; {{ date('Y') }} FDTNexa
                &middot; Geotechnical engineering software
            </span>
        </div>

        <div class="fx-foot-product">
            &copy; {{ date('Y') }} Product of DreamForge Studio
        </div>

    </div>
</footer>
<footer>
<!-- Construction site scene -->
<div class="fx-scene" aria-hidden="true">
    <svg viewBox="0 70 1200 150">
        <rect x="-900" y="170" width="3000" height="80" fill="url(#fxgd)"/>
        <rect x="-900" y="170" width="3000" height="80" fill="url(#fxh)"/>
        <path d="M-900 187H2100M-900 204H2100"
              fill="none" stroke="#38BDF8" stroke-opacity=".3"
              stroke-dasharray="2 6"/>
        <path d="M-900 170H2100" fill="none"
              stroke="#22D3EE" stroke-opacity=".65" stroke-width="1.6"/>

        <g class="tl">
            <rect x="100" y="152" width="124" height="18" rx="9"/>
            <circle cx="116" cy="161" r="5"/>
            <circle cx="208" cy="161" r="5"/>
            <path d="M120 152v-14h66v14"/>
            <path d="M132 138v-26h38l16 26"/>
            <path d="M140 118h22v14h-22z"/>
            <path class="nf" d="M186 128 252 74 312 118"/>
            <path d="M312 118l14 30-26-4z"/>
            <path class="nf" d="M196 140 238 100"/>

            <circle cx="470" cy="146" r="24"/>
            <circle cx="470" cy="146" r="6"/>
            <g class="fx-spin nf">
                <path d="M470 124v44M448 146h44M454 130l32 32M486 130l-32 32"/>
            </g>

            <circle cx="596" cy="152" r="18"/>
            <circle cx="596" cy="152" r="4"/>
            <path d="M470 122h72l14-26h40v40"/>
            <path d="M552 96V74h34v22"/>
            <path d="M500 104h40v18h-40z"/>
            <path class="dm" d="M446 62H620M446 56v12M620 56v12"/>

            <rect x="728" y="166" width="76" height="4"/>
            <path d="M752 166l16-18h16l16 18"/>
            <rect x="760" y="140" width="16" height="8"/>
            <rect x="748" y="96" width="56" height="44" rx="8"/>
            <path class="nf" d="M748 112h56"/>
            <path class="dm" d="M752 170v16h28v-16M834 170v16M828 170h12M828 186h12"/>

            <path class="nf" d="M975 112 940 170M975 112 975 170M975 112 1010 170"/>
            <rect x="963" y="94" width="24" height="18" rx="3"/>
            <rect x="956" y="78" width="38" height="10" rx="3"/>
            <path class="dm" d="M975 112v50"/>
            <path d="M971 162h8l-4 8z"/>

            <rect x="1086" y="92" width="12" height="78"/>
            <path class="nf" d="M1086 106h7M1086 120h7M1086 134h7M1086 148h7M1086 162h7"/>
            <path d="M44 170V132l22 7-22 8"/>
        </g>
    </svg>
</div>

</div> <!-- Closing fx-root -->
</footer>
<script>

(function () {
    if (
        !matchMedia('(hover:hover) and (pointer:fine)').matches ||
        matchMedia('(prefers-reduced-motion:reduce)').matches
    ) return;

    /* Existing card tilt — preserved */
    document.querySelectorAll('a.fx-card,.fx-kpi').forEach(function (c) {
        var f;

        c.addEventListener('pointermove', function (e) {
            var b = c.getBoundingClientRect();
            var x = (e.clientX - b.left) / b.width;
            var y = (e.clientY - b.top) / b.height;

            cancelAnimationFrame(f);

            f = requestAnimationFrame(function () {
                c.style.setProperty('--ry', ((x - .5) * 7).toFixed(2) + 'deg');
                c.style.setProperty('--rx', ((.5 - y) * 5).toFixed(2) + 'deg');
                c.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
                c.style.setProperty('--my', (y * 100).toFixed(1) + '%');
            });
        });

        c.addEventListener('pointerleave', function () {
            c.style.removeProperty('--rx');
            c.style.removeProperty('--ry');
        });
    });

    /* New holographic soil-core movement */
    var stage = document.querySelector('.fx-core-stage');
    if (!stage) return;

    var frame;

    stage.addEventListener('pointermove', function (e) {
        var b = stage.getBoundingClientRect();

        var x = (e.clientX - b.left) / b.width - .5;
        var y = (e.clientY - b.top) / b.height - .5;

        cancelAnimationFrame(frame);

        frame = requestAnimationFrame(function () {
            stage.style.setProperty('--stage-rx', (-y * 5).toFixed(2) + 'deg');
            stage.style.setProperty('--stage-ry', (x * 7).toFixed(2) + 'deg');
        });
    });

    stage.addEventListener('pointerleave', function () {
        stage.style.setProperty('--stage-rx', '0deg');
        stage.style.setProperty('--stage-ry', '0deg');
    });


/* Background parallax */
var root = document.querySelector('.fx-root');
var bg = root && root.querySelector('.fx-bg');

if (root && bg) {
    var bgFrame;

    root.addEventListener('pointermove', function (e) {
        var x = (e.clientX / window.innerWidth - .5);
        var y = (e.clientY / window.innerHeight - .5);

        cancelAnimationFrame(bgFrame);

        bgFrame = requestAnimationFrame(function () {
            bg.style.setProperty('--bg-x', (-x * 12).toFixed(2) + 'px');
            bg.style.setProperty('--bg-y', (-y * 12).toFixed(2) + 'px');
        });
    });

    root.addEventListener('pointerleave', function () {
        bg.style.setProperty('--bg-x', '0px');
        bg.style.setProperty('--bg-y', '0px');
    });
}

})();
</script>
<script>
(function(){
  if(matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  var f=new Intl.NumberFormat('en-US');
  document.querySelectorAll('[data-count]').forEach(function(el){
    var to=parseInt(el.getAttribute('data-count'),10);if(!(to>0))return;
    var d=Math.min(1400,500+to*30),t0=null;
    function step(t){if(t0===null)t0=t;var p=Math.min(1,(t-t0)/d),e=1-Math.pow(1-p,3);el.textContent=f.format(Math.round(to*e));if(p<1)requestAnimationFrame(step)}
    el.textContent=f.format(0);requestAnimationFrame(step);
  });
})();
</script>
</x-app-layout>
