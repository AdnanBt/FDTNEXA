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
  $img = fn ($f) => file_exists(public_path('images/dashboard/'.$f)) ? asset('images/dashboard/'.$f) : null; // optional rendered images
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

/* ===== Stage 2 · future-tech layer ===== */
@keyframes fx-boot{from{opacity:0;transform:translateY(16px)}}
@keyframes fx-scan{0%{transform:translateY(-110%)}55%,100%{transform:translateY(270%)}}
@keyframes fx-sweep{0%{transform:translateY(0);opacity:0}12%,88%{opacity:1}100%{transform:translateY(480%);opacity:0}}
@keyframes fx-rotz{to{transform:rotateX(74deg) rotateZ(360deg)}}
@keyframes fx-dash{to{stroke-dashoffset:-28}}
@keyframes fx-cs{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
.fx-off,.fx-off *,.fx-off *::before,.fx-off *::after{animation-play-state:paused!important}

/* boot-up sequence (CSS only, content stays visible without it) */
.fx-hero>*,.fx-tool,.fx-g4>.fx-cell{animation:fx-boot .9s cubic-bezier(.2,.8,.2,1) backwards}
.fx-hero>:nth-child(2){animation-delay:.12s}
.fx-g2>.fx-tool:nth-child(1){animation-delay:.25s}.fx-g2>.fx-tool:nth-child(2){animation-delay:.35s}
.fx-g4>:nth-child(1){animation-delay:.45s}.fx-g4>:nth-child(2){animation-delay:.55s}.fx-g4>:nth-child(3){animation-delay:.65s}.fx-g4>:nth-child(4){animation-delay:.75s}

/* hero: core-sample hologram */
.fx-stage{--px:0;--py:0;position:relative;width:min(100%,430px);height:clamp(300px,42vw,430px);justify-self:center}
.fx-stage::before{content:"";position:absolute;left:50%;top:-12%;width:64%;height:44%;transform:translateX(-50%);opacity:.75;pointer-events:none;
  background:linear-gradient(180deg,rgba(54,217,245,.28),transparent);clip-path:polygon(38% 0,62% 0,100% 100%,0 100%)}
.fx-halo{position:absolute;left:50%;top:46%;width:135%;aspect-ratio:1;pointer-events:none;transition:transform .3s ease-out;
  transform:translate(-50%,-50%) translate3d(calc(var(--px)*-10px),calc(var(--py)*-8px),0);
  background:radial-gradient(closest-side,rgba(54,217,245,.22),rgba(139,92,246,.1) 46%,transparent 72%)}
.fx-rings{position:absolute;inset:0;perspective:700px;pointer-events:none;transition:transform .3s ease-out;transform:translate3d(calc(var(--px)*22px),calc(var(--py)*14px),0)}
.fx-orb{position:absolute;left:50%;border-radius:50%;border:1px dashed rgba(54,217,245,.5);transform:rotateX(74deg);animation:fx-rotz 24s linear infinite}
.fx-orb::after{content:"";position:absolute;left:50%;top:-3px;width:6px;height:6px;border-radius:50%;background:#36D9F5;box-shadow:0 0 10px #36D9F5}
.fx-orb.r1{width:320px;height:320px;top:44%;margin:-160px 0 0 -160px}
.fx-orb.r2{width:250px;height:250px;top:72%;margin:-125px 0 0 -125px;border-color:rgba(76,230,192,.45);animation-direction:reverse;animation-duration:30s}
.fx-core{position:absolute;inset:0;display:grid;place-items:center;transition:transform .3s ease-out;transform:translate3d(calc(var(--px)*14px),calc(var(--py)*9px),0)}
.fx-coresvg,.fx-coreimg{height:92%;width:auto;max-width:100%;filter:drop-shadow(0 22px 24px rgba(0,0,0,.55))}
.cs{animation:fx-cs 6s ease-in-out infinite;animation-delay:var(--d)}
.fx-beam{position:absolute;left:14%;right:14%;top:4%;height:16%;pointer-events:none;animation:fx-sweep 5.5s ease-in-out infinite;
  background:linear-gradient(180deg,transparent,rgba(54,217,245,.26));border-bottom:1.5px solid rgba(180,245,255,.85);box-shadow:0 8px 18px -6px rgba(54,217,245,.7)}
.fx-ruler{position:absolute;left:5%;top:8%;bottom:8%;width:12px;border-left:1px solid rgba(54,217,245,.55);pointer-events:none;
  background:repeating-linear-gradient(180deg,rgba(54,217,245,.65) 0 1px,transparent 1px 14px) left/10px 100% no-repeat}
.fx-ruler span{position:absolute;left:16px;top:38%;writing-mode:vertical-rl;font:600 .6rem ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.22em;color:var(--t3)}
.fx-lay{position:absolute;inset:0;list-style:none;margin:0;padding:0;pointer-events:none;transition:transform .3s ease-out;transform:translate3d(calc(var(--px)*30px),calc(var(--py)*18px),0)}
.fx-lay li{position:absolute;left:calc(50% + 78px);transform:translateY(-50%);padding:.3rem .5rem;font:600 .6rem/1 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.16em;text-transform:uppercase;
  color:var(--t2);border:1px solid rgba(54,217,245,.3);background:rgba(7,17,31,.62)}
.fx-lay li::before{content:"";position:absolute;right:100%;top:50%;width:34px;border-top:1px dashed rgba(54,217,245,.6)}

/* energy conduit: hero core -> tool portals */
.fx-conduit{display:none;height:70px;margin-top:.5rem}
.fx-conduit svg{width:100%;height:100%;overflow:visible}
.fx-conduit path{fill:none;vector-effect:non-scaling-stroke;stroke:url(#fxe);stroke-width:1.6;stroke-dasharray:5 9;animation:fx-dash 1.6s linear infinite}
.fx-conduit path.g{stroke-width:6;stroke-opacity:.14;stroke-dasharray:none;animation:none}
.fx-conduit circle{fill:#36D9F5;filter:drop-shadow(0 0 6px #36D9F5)}
@media(min-width:768px){.fx-conduit{display:block}}

/* HUD KPI panels: cut corners, brackets, scan band */
.fx-g4 .fx-cell::before{content:"";position:absolute;inset:16px 8px -8px;background:rgba(0,0,0,.5);filter:blur(16px);z-index:-1;pointer-events:none}
.fx-kpi{border:0;border-radius:0;clip-path:polygon(0 0,calc(100% - 24px) 0,100% 24px,100% 100%,24px 100%,0 calc(100% - 24px));
  box-shadow:inset 0 0 0 1px rgba(141,168,188,.22),inset 0 -18px 30px -24px color-mix(in srgb,var(--a) 40%,transparent);
  background:
   linear-gradient(var(--a),var(--a)) left 9px top 9px/16px 1.5px no-repeat,linear-gradient(var(--a),var(--a)) left 9px top 9px/1.5px 16px no-repeat,
   linear-gradient(var(--a),var(--a)) right 9px bottom 9px/16px 1.5px no-repeat,linear-gradient(var(--a),var(--a)) right 9px bottom 9px/1.5px 16px no-repeat,
   linear-gradient(155deg,#1b2f48 0%,#0e1b2d 55%,#0a1422 100%)}
@media(hover:hover){.fx-kpi:hover{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--a) 55%,transparent),inset 0 -18px 30px -24px color-mix(in srgb,var(--a) 55%,transparent)}}
.fx-kin::before,.fx-kin::after{content:"";position:absolute;width:24px;height:24px;pointer-events:none;
  background:linear-gradient(to bottom left,transparent calc(50% - .75px),var(--a) calc(50% - .75px),var(--a) calc(50% + .75px),transparent calc(50% + .75px))}
.fx-kin::before{top:0;right:0}.fx-kin::after{bottom:0;left:0}
.fx-scan{position:absolute;left:0;right:0;top:0;height:42%;z-index:0;pointer-events:none;animation:fx-scan 7s linear infinite;
  background:linear-gradient(180deg,transparent,color-mix(in srgb,var(--a) 12%,transparent) 70%,color-mix(in srgb,var(--a) 36%,transparent) 99%,transparent)}
.fx-g4>:nth-child(2) .fx-scan{animation-delay:-1.8s}.fx-g4>:nth-child(3) .fx-scan{animation-delay:-3.6s}.fx-g4>:nth-child(4) .fx-scan{animation-delay:-5.4s}

/* tool portals: angular panels + glowing platform */
.fx-tool{border:0;border-radius:4px;clip-path:polygon(0 0,calc(100% - 34px) 0,100% 34px,100% 100%,34px 100%,0 calc(100% - 34px));box-shadow:inset 0 0 0 1px rgba(141,168,188,.22)}
@media(hover:hover){a.fx-tool:hover{box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--a) 55%,transparent)}}
.fx-tool .fx-in::before,.fx-tool .fx-in::after{content:"";position:absolute;width:34px;height:34px;pointer-events:none;
  background:linear-gradient(to bottom left,transparent calc(50% - .75px),var(--a) calc(50% - .75px),var(--a) calc(50% + .75px),transparent calc(50% + .75px))}
.fx-tool .fx-in::before{top:0;right:0}.fx-tool .fx-in::after{bottom:0;left:0}
.fx-art{position:relative}
.fx-art::before{content:"";position:absolute;left:50%;bottom:4%;width:84%;aspect-ratio:2.2/1;transform:translateX(-50%);border-radius:50%;pointer-events:none;
  border:1px solid color-mix(in srgb,var(--a) 55%,transparent);background:radial-gradient(closest-side,color-mix(in srgb,var(--a) 26%,transparent),transparent 72%)}
.fx-art svg,.fx-artimg{position:relative;z-index:1}
.fx-artimg{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 16px 18px rgba(0,0,0,.55))}

/* ===== Stage 3 · orbiting illustrations ===== */
@keyframes fx-orbit{
  0%,100%{translate:7px 0;scale:1}12.5%{translate:5px -4px;scale:.98}25%{translate:0 -6px;scale:.96}37.5%{translate:-5px -4px;scale:.98}
  50%{translate:-7px 0;scale:1}62.5%{translate:-5px 4px;scale:1.02}75%{translate:0 6px;scale:1.04}87.5%{translate:5px 4px;scale:1.02}}
@keyframes fx-sway{from{transform:perspective(520px) rotateY(-12deg)}to{transform:perspective(520px) rotateY(12deg)}}
@keyframes fx-orbz{to{transform:perspective(500px) rotateX(72deg) rotateZ(360deg)}}
.fx-kart{will-change:translate,transform;animation:fx-orbit 16s linear infinite,fx-sway 8s ease-in-out infinite alternate;animation-delay:var(--d,0s)}
.fx-art svg,.fx-artimg{animation:fx-orbit 16s linear infinite,fx-sway 8s ease-in-out infinite alternate}
.fx-kart::before,.fx-art::after{content:"";position:absolute;border-radius:50%;pointer-events:none;border:1px dashed color-mix(in srgb,var(--a) 42%,transparent);
  background:radial-gradient(circle at 97% 50%,var(--a) 0 3px,transparent 4px);transform:perspective(500px) rotateX(72deg);animation:fx-orbz 14s linear infinite}
.fx-kart::before{inset:2%}
.fx-art::after{left:50%;top:0;height:100%;aspect-ratio:1/1;translate:-50% 0}
.fx-g4>:nth-child(even) .fx-kart,.fx-g2>.fx-tool:nth-child(2) .fx-art svg,.fx-g2>.fx-tool:nth-child(2) .fx-artimg{animation-direction:reverse,alternate}

/* ===== Stage 4 · true 3D turntable objects (desktop; SVG art stays as fallback) ===== */
@keyframes fx-turn{to{transform:rotateX(-22deg) rotateY(360deg)}}
.fx-tt{position:absolute;inset:0;display:grid;place-items:center;perspective:640px;pointer-events:none}
.fx-tt3d{position:relative;width:0;height:0;scale:var(--k,1);transform-style:preserve-3d;transform:rotateX(-22deg) rotateY(0);animation:fx-turn 22s linear infinite;will-change:transform}
.fx-tt3d i{position:absolute;left:50%;top:50%;display:grid;place-items:center;font:700 11px/1 system-ui,sans-serif;color:#06202a}
.fx-kart.has3d{animation:fx-orbit 16s linear infinite;animation-delay:var(--d,0s)}
.fx-kart.has3d::after{content:"";position:absolute;left:16%;right:16%;bottom:4%;height:11%;border-radius:50%;background:radial-gradient(closest-side,rgba(0,0,0,.55),transparent)}
@media(max-width:1023px){.fx-art .fx-tt3d{scale:calc(var(--k,1)*.8)}}

/* ===== Stage 5 · mobile & small-screen polish ===== */
.has3d>svg,.has3d>.fx-artimg{display:none!important}
.fx-cta{max-width:100%}
@media(max-width:767px){
  .fx-root{--gap:1rem;padding-top:1.25rem}
  .fx-hero{gap:1rem}
  .fx-stage{width:min(100%,250px);height:250px}
  .fx-sec{margin-top:2rem}
  .fx-tool{clip-path:polygon(0 0,calc(100% - 22px) 0,100% 22px,100% 100%,22px 100%,0 calc(100% - 22px))}
  .fx-tool .fx-in{padding:1.25rem}
  .fx-tool .fx-in::before,.fx-tool .fx-in::after{width:22px;height:22px}
  .fx-art{height:120px}
  .fx-cta{width:100%;justify-content:center}
  .fx-kpi,.fx-kin{min-height:216px}
}
@media(max-width:480px){
  .fx-wrap{padding:0 .75rem}
  .fx-h1{font-size:clamp(1.75rem,9vw,2.2rem)}
  .fx-kart{width:42%}
  .fx-donut{width:116px;height:116px}
  .fx-mt{font-size:1.25rem}
  .fx-leg{min-width:0}
}

/* ===== THE NEW ANDROID 12+ RULE ===== */
@media (max-width: 767px) {
  @supports (transform-style: preserve-3d) and (backdrop-filter: blur(10px)) {
    .fx-bg { animation: fx-drift 40s ease-in-out infinite alternate; }
    .fx-kart { animation: fx-orbit 16s linear infinite, fx-sway 8s ease-in-out infinite alternate; }
    .fx-kart.has3d { animation: fx-orbit 16s linear infinite; }
    .fx-kart.has3d::after { display: block; }
    .fx-art svg, .fx-artimg { animation: fx-orbit 16s linear infinite, fx-sway 8s ease-in-out infinite alternate; }
    .fx-tt { display: grid; }
    .fx-tool::before { animation: fx-electric 9s linear infinite; }
    .fx-scene .fx-spin { animation: fx-rot 9s linear infinite; }
    .fx-donut .sg { animation: fx-draw 1.1s ease-out both; }
  }
}

@media(prefers-reduced-motion:reduce){.fx-root *,.fx-root *::before,.fx-root *::after{animation:none!important;transition:none!important}}
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
      <div class="fx-stage" aria-hidden="true">
        <div class="fx-halo"></div>
        <div class="fx-ruler"><span>DEPTH</span></div>
        <div class="fx-rings"><i class="fx-orb r1"></i><i class="fx-orb r2"></i></div>
        <div class="fx-core">
          @if($__c = $img('soil-core.webp'))
          <img class="fx-coreimg" src="{{ $__c }}" alt="" width="900" height="1200" decoding="async" fetchpriority="high">
          @else
          @php $segs = [[42,44,'c1','#f1dfae',0],[104,50,'c2','#b9c3d0',1],[172,50,'c3','#b98557',2],[240,46,'c4','#9ca8b8',3],[306,38,'c5','#8470e0',4]]; @endphp
          <svg class="fx-coresvg" viewBox="0 0 220 360">
            <defs>
              <linearGradient id="c1" x1="0" x2="1"><stop offset="0" stop-color="#7d6638"/><stop offset=".4" stop-color="#ecd49a"/><stop offset="1" stop-color="#5f4d2a"/></linearGradient>
              <linearGradient id="c2" x1="0" x2="1"><stop offset="0" stop-color="#46505d"/><stop offset=".4" stop-color="#aab4c2"/><stop offset="1" stop-color="#353c46"/></linearGradient>
              <linearGradient id="c3" x1="0" x2="1"><stop offset="0" stop-color="#432a19"/><stop offset=".4" stop-color="#a2714a"/><stop offset="1" stop-color="#2f1c11"/></linearGradient>
              <linearGradient id="c4" x1="0" x2="1"><stop offset="0" stop-color="#3a4552"/><stop offset=".4" stop-color="#8793a3"/><stop offset="1" stop-color="#28303a"/></linearGradient>
              <linearGradient id="c5" x1="0" x2="1"><stop offset="0" stop-color="#2a2152"/><stop offset=".4" stop-color="#7461d1"/><stop offset="1" stop-color="#1b1437"/></linearGradient>
              <pattern id="cgp" width="9" height="9" patternUnits="userSpaceOnUse"><circle cx="2.5" cy="2.5" r="1.3" fill="#dbe3ee" fill-opacity=".55"/><circle cx="6.5" cy="6.5" r="1" fill="#dbe3ee" fill-opacity=".4"/></pattern>
            </defs>
            @foreach($segs as [$sy, $sh, $sg, $st, $si])
            <g class="cs" style="--d:-{{ $si * 0.9 }}s">
              <path d="M65 {{ $sy }}v{{ $sh }}a45 12 0 0 0 90 0v-{{ $sh }}a45 12 0 0 0-90 0z" fill="url(#{{ $sg }})"/>
              @if($sg === 'c2')<path d="M65 {{ $sy }}v{{ $sh }}a45 12 0 0 0 90 0v-{{ $sh }}a45 12 0 0 0-90 0z" fill="url(#cgp)"/>@endif
              @if($sg === 'c4')<path d="M72 {{ $sy + 10 }}l18 14 14-8 20 16M96 {{ $sy + 28 }}l16-10 22 8" fill="none" stroke="#e5ecf5" stroke-opacity=".3"/>@endif
              <path d="M65 {{ $sy }}v{{ $sh }}" stroke="#fff" stroke-opacity=".25"/>
              <ellipse cx="110" cy="{{ $sy }}" rx="45" ry="12" fill="{{ $st }}" stroke="#36D9F5" stroke-opacity=".6"/>
            </g>
            @endforeach
          </svg>
          @endif
          <i class="fx-beam"></i>
        </div>
        <ul class="fx-lay"><li style="top:20.4%">Sand</li><li style="top:37%">Gravel</li><li style="top:54.3%">Clay</li><li style="top:71.2%">Rock</li><li style="top:87.1%">Bedrock</li></ul>
      </div>
    </section>

    <div class="fx-conduit" aria-hidden="true"><svg viewBox="0 0 1000 90" preserveAspectRatio="none">
      <defs><linearGradient id="fxe" x1="0" x2="1"><stop offset="0" stop-color="#36D9F5"/><stop offset="1" stop-color="#4CE6C0"/></linearGradient></defs>
      <path class="g" d="M812 0C812 50 250 30 250 90"/><path class="g" d="M812 0C812 50 750 40 750 90"/>
      <path d="M812 0C812 50 250 30 250 90"/><path d="M812 0C812 50 750 40 750 90"/><circle cx="812" cy="3" r="4"/>
    </svg></div>

    <!-- Engineering tools (primary) -->
    <section class="fx-sec" aria-labelledby="fx-tools">
      <h2 class="fx-h2" id="fx-tools">Engineering tools</h2>
      <p class="fx-sub">Select a module to start your work.</p>
      <div class="fx-g2">
        <a href="{{ route('field-tool.index') }}" class="fx-card fx-tool" style="--a:var(--cyan)"><div class="fx-in">
          <div class="fx-art" data-tt="cone" data-dy="50" data-k="1.45" data-sp="20" aria-hidden="true">
            @if($__a = $img('sand-cone.webp'))<img class="fx-artimg" src="{{ $__a }}" alt="" width="600" height="600" loading="lazy" decoding="async">@else
            <svg viewBox="0 0 160 150"><g class="l">
              <path class="nf" d="M0 118h160"/><rect x="18" y="113" width="84" height="5"/>
              <path d="M40 113 54 96h12l14 17"/><rect x="52" y="88" width="16" height="8"/>
              <rect x="36" y="34" width="48" height="54" rx="8"/><path d="M36 64h48v16a8 8 0 0 1-8 8H44a8 8 0 0 1-8-8z" style="fill:color-mix(in srgb,var(--a) 28%,transparent);stroke:none"/>
              <path class="nf" d="M36 52h10M36 64h48M36 76h10"/>
              <path class="dm" d="M44 118v22h32v-22M118 118v22M112 118h12M112 140h12"/>
              <circle cx="54" cy="134" r="1.4" fill="var(--a)" stroke="none"/><circle cx="62" cy="130" r="1.4" fill="var(--a)" stroke="none"/><circle cx="68" cy="136" r="1.4" fill="var(--a)" stroke="none"/>
            </g></svg>
            @endif
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
          <div class="fx-art" data-tt="soil5" data-k="1.3" data-sp="24" aria-hidden="true">
            @if($__a = $img('soil-layers.webp'))<img class="fx-artimg" src="{{ $__a }}" alt="" width="600" height="600" loading="lazy" decoding="async">@else
            <svg viewBox="0 0 240 250">
              <g class="s" style="--d:-1.2s"><use href="#fxs" y="140" style="--c:#134e4a"/></g><g class="s" style="--d:-.9s"><use href="#fxs" y="112" style="--c:#0f766e"/></g>
              <g class="s" style="--d:-.6s"><use href="#fxs" y="84" style="--c:#0d9488"/></g><g class="s" style="--d:-.3s"><use href="#fxs" y="56" style="--c:#14b8a6"/></g>
              <g class="s" style="--d:0s"><use href="#fxs" y="28" style="--c:#2dd4bf"/></g>
            </svg>
            @endif
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
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#36D9F5"><div class="fx-kin"><i class="fx-scan" aria-hidden="true"></i>
          <div class="fx-kart" style="--d:0s" data-tt="docs" data-k="1.15" data-sp="24" aria-hidden="true">
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
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#4CE6C0"><div class="fx-kin"><i class="fx-scan" aria-hidden="true"></i>
          <div class="fx-kart" style="--d:-1.4s" data-tt="book" data-k="1.1" data-sp="26" aria-hidden="true">
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
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#8B5CF6"><div class="fx-kin"><i class="fx-scan" aria-hidden="true"></i>
          <div class="fx-kart" style="--d:-2.6s" data-tt="soil4" data-k="1.05" data-sp="22" aria-hidden="true">
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
        <div class="fx-cell"><div class="fx-card fx-kpi" style="--a:#F5A524"><div class="fx-kin"><i class="fx-scan" aria-hidden="true"></i>
          <div class="fx-kart" style="--d:-3.8s" data-tt="bars" data-a="{{ $h1 }}" data-b="{{ $h2 }}" data-dy="44" data-k=".95" data-sp="28" aria-hidden="true">
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
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#14B8A6" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/></svg>
      <span>&copy; {{ date('Y') }} FDTNexa &middot; Geotechnical engineering software</span>
    </footer>
  </div>

  <!-- construction-site scene -->
  <div class="fx-scene" aria-hidden="true">
    <svg viewBox="0 0 1200 220">
      <rect x="-900" y="170" width="3000" height="80" fill="url(#fxgd)"/><rect x="-900" y="170" width="3000" height="80" fill="url(#fxh)"/>
      <path d="M-900 187H2100M-900 204H2100" fill="none" stroke="#38BDF8" stroke-opacity=".3" stroke-dasharray="2 6"/>
      <path d="M-900 170H2100" fill="none" stroke="#22D3EE" stroke-opacity=".65" stroke-width="1.6"/>
      <g class="tl">
        <rect x="100" y="152" width="124" height="18" rx="9"/><circle cx="116" cy="161" r="5"/><circle cx="208" cy="161" r="5"/>
        <path d="M120 152v-14h66v14"/><path d="M132 138v-26h38l16 26"/><path d="M140 118h22v14h-22z"/>
        <path class="nf" d="M186 128 252 74 312 118"/><path d="M312 118l14 30-26-4z"/><path class="nf" d="M196 140 238 100"/>
        <circle cx="470" cy="146" r="24"/><circle cx="470" cy="146" r="6"/>
        <g class="fx-spin nf"><path d="M470 124v44M448 146h44M454 130l32 32M486 130l-32 32"/></g>
        <circle cx="596" cy="152" r="18"/><circle cx="596" cy="152" r="4"/>
        <path d="M470 122h72l14-26h40v40"/><path d="M552 96V74h34v22"/><path d="M500 104h40v18h-40z"/>
        <path class="dm" d="M446 62H620M446 56v12M620 56v12"/>
        <rect x="728" y="166" width="76" height="4"/><path d="M752 166l16-18h16l16 18"/><rect x="760" y="140" width="16" height="8"/>
        <rect x="748" y="96" width="56" height="44" rx="8"/><path class="nf" d="M748 112h56"/>
        <path class="dm" d="M752 170v16h28v-16M834 170v16M828 170h12M828 186h12"/>
        <path class="nf" d="M975 112 940 170M975 112 975 170M975 112 1010 170"/><rect x="963" y="94" width="24" height="18" rx="3"/>
        <rect x="956" y="78" width="38" height="10" rx="3"/><path class="dm" d="M975 112v50"/><path d="M971 162h8l-4 8z"/>
        <rect x="1086" y="92" width="12" height="78"/><path class="nf" d="M1086 106h7M1086 120h7M1086 134h7M1086 148h7M1086 162h7"/>
        <path d="M44 170V132l22 7-22 8"/>
      </g>
    </svg>
  </div>
</div>

<script>
(function(){
  if(!matchMedia('(hover:hover) and (pointer:fine)').matches||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  document.querySelectorAll('a.fx-card,.fx-kpi').forEach(function(c){
    var f;
    c.addEventListener('pointermove',function(e){
      var b=c.getBoundingClientRect(),x=(e.clientX-b.left)/b.width,y=(e.clientY-b.top)/b.height;
      cancelAnimationFrame(f);
      f=requestAnimationFrame(function(){
        c.style.setProperty('--ry',((x-.5)*7).toFixed(2)+'deg');c.style.setProperty('--rx',((.5-y)*5).toFixed(2)+'deg');
        c.style.setProperty('--mx',(x*100).toFixed(1)+'%');c.style.setProperty('--my',(y*100).toFixed(1)+'%');
      });
    });
    c.addEventListener('pointerleave',function(){c.style.removeProperty('--rx');c.style.removeProperty('--ry')});
  });
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
<script>
(function(){
  var root=document.querySelector('.fx-root');if(!root)return;
  if(window.IntersectionObserver){
    var io=new IntersectionObserver(function(es){es.forEach(function(e){e.target.classList.toggle('fx-off',!e.isIntersecting)})},{rootMargin:'80px'});
    root.querySelectorAll('.fx-hero,.fx-sec,.fx-conduit,.fx-scene').forEach(function(el){io.observe(el)});
  }
  document.addEventListener('visibilitychange',function(){root.classList.toggle('fx-off',document.hidden)});
  var st=root.querySelector('.fx-stage');
  if(!st||!matchMedia('(hover:hover) and (pointer:fine)').matches||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  var h=root.querySelector('.fx-hero'),f;
  h.addEventListener('pointermove',function(e){
    var b=h.getBoundingClientRect(),x=(e.clientX-b.left)/b.width-.5,y=(e.clientY-b.top)/b.height-.5;
    cancelAnimationFrame(f);f=requestAnimationFrame(function(){st.style.setProperty('--px',(x*2).toFixed(3));st.style.setProperty('--py',(y*2).toFixed(3))});
  });
  h.addEventListener('pointerleave',function(){st.style.setProperty('--px',0);st.style.setProperty('--py',0)});
})();
</script>
<script>
var FXT=(function(){
  var PI=Math.PI,r1=function(n){return Math.round(n*100)/100};
  function sh(bg,a){return a>0?'linear-gradient(rgba(0,0,0,'+a+'),rgba(0,0,0,'+a+')),'+bg:(a<0?'linear-gradient(rgba(255,255,255,'+(-a)+'),rgba(255,255,255,'+(-a)+')),'+bg:bg)}
  function T(x,y,z){return 'translate3d('+r1(x)+'px,'+r1(y)+'px,'+r1(z)+'px) '}
  /* box centred on (x,y,z). o: s side bg, t top bg, f flat colour, ff front bg, fb back bg, css extra, a alpha shading off */
  function box(w,h,d,x,y,z,o){
    var s=o.s,t=o.t||s,F=[],P=T(x,y,z),c=o.css||'';
    function f(fw,fh,tr,bg,a){F.push({w:fw,h:fh,t:P+tr,bg:sh(bg,a),fc:o.f,css:c,a:a})}
    f(w,h,'translateZ('+d/2+'px)',o.ff||s,0);
    f(w,h,'rotateY(180deg) translateZ('+d/2+'px)',o.fb||s,.32);
    f(d,h,'rotateY(90deg) translateZ('+w/2+'px)',s,.2);
    f(d,h,'rotateY(-90deg) translateZ('+w/2+'px)',s,.3);
    f(w,d,'rotateX(90deg) translateZ('+h/2+'px)',t,-.12);
    f(w,d,'rotateX(-90deg) translateZ('+h/2+'px)',s,.55);
    return F;
  }
  /* frustum (rb bottom radius, rt top radius), n facets; rt=rb gives a cylinder */
  function frus(rb,rt,h,n,x,y,z,o){
    var F=[],P=T(x,y,z),k=Math.cos(PI/n),ab=rb*k,at=rt*k,am=(ab+at)/2,L=Math.sqrt(h*h+(ab-at)*(ab-at)),al=Math.atan((ab-at)/h)*180/PI;
    var fwb=2*rb*Math.sin(PI/n)+.8,fwt=2*rt*Math.sin(PI/n)+.8,ins=(fwb-fwt)/2/fwb*100,tilt=Math.abs(ab-at)>.01;
    for(var i=0;i<n;i++){
      F.push({w:fwb,h:L,t:P+'rotateY('+r1(i*360/n)+'deg) translateZ('+r1(am)+'px)'+(tilt?' rotateX('+r1(al)+'deg)':''),
        bg:o.s,fc:o.f,css:o.css||'',clip:tilt?'polygon('+r1(ins)+'% 0,'+r1(100-ins)+'% 0,100% 100%,0 100%)':'',a:.15});
    }
    if(!o.open){
      F.push({w:rt*2,h:rt*2,t:P+'rotateX(90deg) translateZ('+h/2+'px)',bg:o.t||o.s,fc:o.ft||o.f,r:1,css:o.css||'',a:-.1});
      F.push({w:rb*2,h:rb*2,t:P+'rotateX(-90deg) translateZ('+h/2+'px)',bg:o.s,fc:o.f,r:1,css:o.css||'',a:.5});
    }
    return F;
  }
  function disc(r,x,y,z,bg,fc,txt){ // flat circle facing the viewer
    return [{w:r*2,h:r*2,t:T(x,y,z),bg:bg,fc:fc,r:1,txt:txt||'',css:'',a:0}];
  }
  function ring(r,x,y,z,col){ // flat horizontal dashed ring
    return [{w:r*2,h:r*2,t:T(x,y,z)+'rotateX(90deg)',bg:'transparent',fc:null,r:1,css:'border:1px dashed '+col,a:0}];
  }

  var SOIL=[
    {n:'sand',s:'radial-gradient(circle,rgba(120,90,40,.38) .7px,transparent 1px) 0 0/5px 5px,#d6b877',t:'#e8d4a2',f:'#d6b877'},
    {n:'gravel',s:'radial-gradient(circle,#e3e9f1 1.2px,transparent 1.8px) 0 0/8px 8px,radial-gradient(circle,#667180 1px,transparent 1.6px) 3px 4px/9px 9px,#8b96a6',t:'#9aa5b4',f:'#8b96a6'},
    {n:'clay',s:'repeating-linear-gradient(0deg,rgba(0,0,0,.14) 0 2px,transparent 2px 5px),#7b5638',t:'#8a6141',f:'#7b5638'},
    {n:'fill',s:'repeating-linear-gradient(0deg,rgba(255,255,255,.07) 0 1px,transparent 1px 4px),#5b4a3a',t:'#6a5846',f:'#5b4a3a'},
    {n:'rock',s:'linear-gradient(135deg,rgba(255,255,255,.14) 0 22%,transparent 22% 62%,rgba(0,0,0,.22) 62%),#4a3f86',t:'#5a4d9c',f:'#4a3f86'}
  ];
  var RIM='box-shadow:inset 0 0 0 1px rgba(54,217,245,.55)';
  function soil(count,w,h,gap,glass){
    var F=[],i,y0=-(count-1)*(h+gap)/2,idx=count===4?[0,1,2,4]:[0,1,2,3,4];
    for(i=0;i<count;i++){var L=SOIL[idx[i]];F=F.concat(box(w,h,w,0,y0+i*(h+gap),0,{s:L.s,t:L.t,f:L.f,css:i===0?RIM:''}))}
    if(glass)F=F.concat(box(w+10,5,w+10,0,y0-(h+gap)-6,0,{s:'rgba(94,234,212,.22)',t:'rgba(94,234,212,.28)',f:'#5eead4',css:'box-shadow:inset 0 0 0 1px rgba(94,234,212,.8)'}));
    return F;
  }
  function sheets(n,w,h,front,back,side,fc,badge,gap){gap=gap||6;
    var F=[],i;
    for(i=0;i<n;i++){
      var z=(i-(n-1)/2)*gap,x=(i-(n-1)/2)*-2;
      F=F.concat(box(w,h,2.4,x,0,z,{s:side,ff:i===n-1?front:side,fb:i===0?back:side,f:fc}));
    }
    if(badge)F=F.concat(disc(9,w/2-12+((n-1)/2)*2,h/2-14,(n-1)/2*gap+2,badge.bg,badge.fc,badge.t));
    return F;
  }
  var scenes={
    soil5:function(){return soil(5,104,14,5,true)},
    soil4:function(){return soil(4,96,13,5,true)},
    docs:function(){
      return sheets(5,76,98,
        'repeating-linear-gradient(0deg,transparent 0 8px,rgba(141,168,188,.7) 8px 9.2px) 10px 30px/56px 52px no-repeat,linear-gradient(#36D9F5,#36D9F5) 10px 12px/28px 3px no-repeat,linear-gradient(150deg,#2a5776,#0e2236)',
        'repeating-linear-gradient(0deg,transparent 0 8px,rgba(141,168,188,.35) 8px 9.2px) 10px 14px/56px 70px no-repeat,linear-gradient(150deg,#1d3d57,#0b1b2c)',
        '#16324a','#2a5776',{bg:'#4CE6C0',fc:'#4CE6C0',t:'✓'},5.5);
    },
    book:function(){
      var grid='linear-gradient(rgba(141,232,210,.45) 1px,transparent 1px) 0 18px/100% 14px,linear-gradient(90deg,rgba(141,232,210,.45) 1px,transparent 1px) 0 0/22px 100%,';
      return sheets(4,84,78,
        'linear-gradient(90deg,#4CE6C0,#1c9b86) 0 0/100% 16px no-repeat,'+grid+'linear-gradient(150deg,#1f5a5a,#0c2530)',
        grid+'linear-gradient(150deg,#174a4c,#0a2128)','#14464a','#1f5a5a',{bg:'#4CE6C0',fc:'#4CE6C0',t:'✕'},6);
    },
    bars:function(a,b){
      var F=box(128,6,86,0,0,0,{s:'rgba(54,217,245,.20)',t:'rgba(54,217,245,.26)',f:'#1b6b80',css:'box-shadow:inset 0 0 0 1px rgba(54,217,245,.7)'});
      F=F.concat(box(24,a,24,-28,-3-a/2,0,{s:'#1aa3bd',t:'#b7f3ff',f:'#1aa3bd'}));
      F=F.concat(box(24,b,24,28,-3-b/2,0,{s:'#0f8f7e',t:'#c4fbee',f:'#0f8f7e'}));
      return F.concat(ring(46,0,-3-Math.max(a,b)-14,0,'rgba(245,165,36,.8)'));
    },
    cone:function(){
      var F=[],glass='linear-gradient(90deg,rgba(255,255,255,.22),rgba(180,230,255,.10) 45%,rgba(0,0,0,.22)),rgba(150,210,240,.16)';
      var metal='linear-gradient(90deg,#d8dee6,#8a97a8 50%,#4d5967)',brass='linear-gradient(90deg,#f1cf8a,#c99a4b 50%,#7a5a22)';
      F=F.concat(box(104,4,104,0,0,0,{s:'#7d8a9b',t:'#a9b6c6',f:'#7d8a9b'}));
      F=F.concat(disc(14,0,-2.2,0,'#0b1218','#0b1218').map(function(f){f.t=T(0,-2.3,0)+'rotateX(90deg)';return f}));
      F=F.concat(frus(27,9,26,14,0,-15,0,{s:metal,t:'#b4bfcc',f:'#8a97a8',open:true}));
      F=F.concat(frus(9,9,8,12,0,-32,0,{s:brass,t:'#e0b868',f:'#c99a4b'}));
      F=F.concat(frus(25,25,40,14,0,-56,0,{s:'linear-gradient(90deg,#f0d9a0,#d9bd7f 50%,#8f7740)',t:'#ecd49a',f:'#d9bd7f'}));
      F=F.concat(frus(29,29,60,14,0,-66,0,{s:glass,t:'rgba(160,220,250,.25)',f:'#9fd3ec',css:'box-shadow:inset 0 0 0 1px rgba(54,217,245,.35)'}));
      F=F.concat(frus(30,30,6,14,0,-99,0,{s:metal,t:'#b4bfcc',f:'#8a97a8'}));
      return F;
    }
  };
  return {scenes:scenes,build:function(name,a,b){return scenes[name](a,b)},r1:r1};
})();

(function(){
  if(matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  document.querySelectorAll('[data-tt]').forEach(function(el,n){
    if(el.querySelector('img'))return;
    var F,dy=parseFloat(el.getAttribute('data-dy'))||0;
    try{F=FXT.build(el.getAttribute('data-tt'),parseFloat(el.getAttribute('data-a'))||0,parseFloat(el.getAttribute('data-b'))||0)}catch(e){return}
    var s=document.createElement('div'),r=document.createElement('div'),fr=document.createDocumentFragment();
    s.className='fx-tt';r.className='fx-tt3d';
    r.style.setProperty('--k',el.getAttribute('data-k')||1);r.style.animationDuration=(el.getAttribute('data-sp')||22)+'s';
    if(n%2)r.style.animationDirection='reverse';
    F.forEach(function(f){
      var i=document.createElement('i'),st=i.style;
      st.width=f.w+'px';st.height=f.h+'px';st.margin=(-f.h/2)+'px 0 0 '+(-f.w/2)+'px';
      st.transform=(dy?'translate3d(0,'+dy+'px,0) ':'')+f.t;st.background=f.bg;
      if(f.r)st.borderRadius='50%';if(f.clip)st.clipPath=f.clip;if(f.txt)i.textContent=f.txt;if(f.css)st.cssText+=';'+f.css;
      fr.appendChild(i);
    });
    r.appendChild(fr);s.appendChild(r);el.appendChild(s);el.classList.add('has3d');
  });
})();
</script>
</x-app-layout>