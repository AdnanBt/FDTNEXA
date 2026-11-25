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
@endphp
<style>
.fx-root{--navy:#0B1220;--graph:#111827;--slate:#182235;--cyan:#06B6D4;--teal:#14B8A6;--sky:#38BDF8;--white:#F8FAFC;--mute:#94A3B8;
  position:relative;isolation:isolate;overflow:hidden;background:radial-gradient(1200px 520px at 70% -10%,#12304a 0%,transparent 60%),var(--navy);
  color:var(--white);padding:2rem 0 0;min-height:calc(100vh - 4rem);box-sizing:border-box}
.fx-root *,.fx-root *::before,.fx-root *::after{box-sizing:border-box}
.fx-wrap{max-width:80rem;margin:0 auto;padding:0 1rem}
@media(min-width:640px){.fx-wrap{padding:0 1.5rem}}@media(min-width:1024px){.fx-wrap{padding:0 2rem}}
.fx-bg{position:absolute;inset:-4%;z-index:-1;width:108%;height:108%;pointer-events:none;
  -webkit-mask-image:radial-gradient(ellipse at 50% 28%,#000 15%,transparent 72%);mask-image:radial-gradient(ellipse at 50% 28%,#000 15%,transparent 72%);
  animation:fx-drift 40s ease-in-out infinite alternate}
@keyframes fx-drift{to{transform:translate3d(-2.5%,1.5%,0)}}
@keyframes fx-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@keyframes fx-pulse{0%{box-shadow:0 0 0 0 rgba(20,184,166,.55)}100%{box-shadow:0 0 0 9px rgba(20,184,166,0)}}
@keyframes fx-flow{to{background-position:0 -24px}}@keyframes fx-flowx{to{background-position:24px 0}}
@keyframes fx-draw{from{stroke-dasharray:0 252}}@keyframes fx-grow{from{transform:scaleX(0)}}
/* layout */
.fx-g2,.fx-g3{display:grid;gap:1.5rem;grid-template-columns:1fr}
@media(min-width:640px){.fx-g2,.fx-g3{grid-template-columns:repeat(2,minmax(0,1fr))}.fx-last{grid-column:span 2}}
@media(min-width:1024px){.fx-g3{grid-template-columns:repeat(3,minmax(0,1fr))}.fx-last{grid-column:auto}}
.fx-sec{margin-top:2.5rem}.fx-row{display:flex;align-items:flex-start;justify-content:space-between;gap:.75rem}.fx-mid{align-items:center}
.fx-gap{gap:1rem}.fx-min{min-width:0}.fx-blk{display:block}
/* hero */
.fx-hero{display:grid;gap:1.5rem;align-items:center}
@media(min-width:768px){.fx-hero{grid-template-columns:1.25fr .75fr}}
.fx-brand{display:inline-flex;align-items:center;gap:.6rem;font-weight:700;letter-spacing:-.01em}
.fx-brand i{display:grid;place-items:center;width:34px;height:34px;border-radius:10px;background:linear-gradient(145deg,#22d3ee,#0d9488);box-shadow:0 4px 0 #0a5c63,0 10px 18px -6px rgba(6,182,212,.6)}
.fx-h1{font-size:clamp(2rem,5.2vw,3.4rem);line-height:1.04;font-weight:800;letter-spacing:-.03em;margin:1rem 0 .8rem;color:var(--white)}
.fx-h1 em{font-style:normal;background:linear-gradient(90deg,var(--cyan),var(--teal));-webkit-background-clip:text;background-clip:text;color:transparent}
.fx-lead{color:var(--mute);max-width:46ch;line-height:1.65;margin:0}
.fx-status{display:inline-flex;align-items:center;gap:.55rem;margin-top:1.4rem;padding:.45rem .9rem;border-radius:99px;font-size:.82rem;color:#99f6e4;background:rgba(20,184,166,.1);border:1px solid rgba(20,184,166,.28)}
.fx-status b{width:8px;height:8px;border-radius:50%;background:var(--teal);animation:fx-pulse 1.8s ease-out infinite}
.fx-iso{display:none;justify-self:center;width:min(100%,340px)}@media(min-width:768px){.fx-iso{display:block}}
.fx-iso .s{animation:fx-float 7s ease-in-out infinite;animation-delay:var(--d)}
/* 3D plate cards */
.fx-card{--a:var(--cyan);--rx:0deg;--ry:0deg;--ty:0px;position:relative;display:block;color:inherit;text-decoration:none;border-radius:18px;
  background:linear-gradient(160deg,var(--slate),var(--graph) 70%);border:1px solid rgba(148,163,184,.16);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.07),0 7px 0 -3px #0e1626,0 14px 0 -6px #0a111e,0 26px 38px -14px rgba(0,0,0,.7);
  transform:perspective(900px) rotateX(var(--rx)) rotateY(var(--ry)) translateY(var(--ty));transition:transform .18s ease-out,border-color .25s;overflow:hidden}
.fx-card::before{content:"";position:absolute;inset:0 0 auto 0;height:2px;background:linear-gradient(90deg,transparent,var(--a),transparent)}
.fx-card::after{content:"";position:absolute;inset:0;pointer-events:none;opacity:0;transition:opacity .25s;background:radial-gradient(260px circle at var(--mx,50%) var(--my,0%),color-mix(in srgb,var(--a) 22%,transparent),transparent 65%)}
.fx-card:hover,.fx-card:focus-visible{--ty:-4px;border-color:color-mix(in srgb,var(--a) 55%,transparent)}
.fx-card:hover::after,.fx-card:focus-visible::after{opacity:1}.fx-card:focus-visible{outline:2px solid var(--a);outline-offset:3px}
.fx-in{position:relative;z-index:1;padding:1.35rem}.fx-tool .fx-in{padding:1.6rem}
.fx-ico{display:grid;place-items:center;width:46px;height:46px;border-radius:13px;color:#031318;flex:none;background:linear-gradient(145deg,color-mix(in srgb,var(--a) 70%,#fff),var(--a));
  box-shadow:inset 0 1px 0 rgba(255,255,255,.55),0 4px 0 color-mix(in srgb,var(--a) 45%,#000),0 10px 16px -4px color-mix(in srgb,var(--a) 50%,transparent)}
.fx-ico svg,.fx-arrow svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.fx-num{font-size:2.6rem;line-height:1;font-weight:800;letter-spacing:-.03em;font-variant-numeric:tabular-nums;margin-top:1.2rem}
.fx-t{font-weight:600;margin:.35rem 0 0;color:var(--white)}.fx-d{color:var(--mute);font-size:.88rem;margin:.2rem 0 0;line-height:1.5}
.fx-tag{font-size:.72rem;padding:.2rem .6rem;border-radius:99px;white-space:nowrap;color:color-mix(in srgb,var(--a) 70%,#fff);background:color-mix(in srgb,var(--a) 13%,transparent);border:1px solid color-mix(in srgb,var(--a) 28%,transparent)}
.fx-ticks{height:8px;margin-top:1.1rem;opacity:.55;background:repeating-linear-gradient(90deg,var(--a) 0 2px,transparent 2px 9px);-webkit-mask-image:linear-gradient(90deg,#000,transparent);mask-image:linear-gradient(90deg,#000,transparent)}
.fx-h2{font-size:1.25rem;font-weight:700;letter-spacing:-.015em;margin:0;color:var(--white)}.fx-sub{color:var(--mute);font-size:.9rem;margin:.2rem 0 1.25rem}
.fx-arrow{display:inline-flex;align-items:center;gap:.4rem;color:var(--a);font-weight:600;font-size:.88rem;margin-top:1.1rem}
.fx-arrow svg{width:18px;height:18px;transition:transform .2s}.fx-card:hover .fx-arrow svg{transform:translateX(4px)}
/* analytics widgets */
.fx-ring{display:flex;align-items:center;gap:1.2rem;margin-top:1rem;flex-wrap:wrap}
.fx-donut{width:132px;height:132px;flex:none}
.fx-donut .tr{fill:none;stroke:rgba(148,163,184,.14);stroke-width:11}
.fx-donut .sg{fill:none;stroke-width:11;transform:rotate(-90deg);transform-origin:50% 50%;animation:fx-draw 1.1s ease-out both}
.fx-donut text{fill:var(--white);font-weight:800;text-anchor:middle}.fx-donut .sm{fill:var(--mute);font-weight:500;font-size:7px}
.fx-leg{list-style:none;margin:0;padding:0;display:grid;gap:.6rem;font-size:.88rem;flex:1;min-width:130px}
.fx-leg li{display:flex;align-items:center;justify-content:space-between;gap:.6rem;color:var(--mute)}
.fx-leg i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:.5rem;background:var(--c)}.fx-leg b{color:var(--white);font-variant-numeric:tabular-nums}
.fx-bar{margin-top:1rem}.fx-bar>div:first-child{display:flex;justify-content:space-between;font-size:.88rem;color:var(--mute);margin-bottom:.4rem}.fx-bar b{color:var(--white)}
.fx-trk{height:9px;border-radius:9px;background:rgba(148,163,184,.14);overflow:hidden}
.fx-fill{height:100%;border-radius:9px;background:linear-gradient(90deg,color-mix(in srgb,var(--c) 70%,#000),var(--c));transform-origin:left;animation:fx-grow .9s ease-out both;box-shadow:0 0 12px color-mix(in srgb,var(--c) 45%,transparent)}
.fx-empty{color:var(--mute);font-size:.85rem;margin-top:.9rem}
/* connectors */
.fx-cell{position:relative}.fx-conn{display:none}
@media(min-width:1024px){.fx-conn{display:block;position:absolute;top:50%;right:-1.5rem;width:1.5rem;height:2px;z-index:2;background:repeating-linear-gradient(90deg,var(--cyan) 0 6px,transparent 6px 12px);background-size:24px 2px;animation:fx-flowx 1.6s linear infinite}
  .fx-conn::after{content:"";position:absolute;right:-3px;top:-3px;width:8px;height:8px;border-radius:50%;background:var(--cyan);box-shadow:0 0 10px var(--cyan)}}
.fx-vline{display:block;width:2px;height:36px;margin:1.1rem auto 0;position:relative;background:repeating-linear-gradient(180deg,var(--teal) 0 6px,transparent 6px 12px);background-size:2px 24px;animation:fx-flow 1.6s linear infinite}
.fx-vline::after{content:"";position:absolute;left:-3px;bottom:-4px;width:8px;height:8px;border-radius:50%;background:var(--teal);box-shadow:0 0 10px var(--teal)}
.fx-foot{margin-top:2.6rem;padding-top:1.3rem;border-top:1px solid rgba(148,163,184,.14);display:flex;justify-content:center;align-items:center;gap:.6rem;color:#64748b;font-size:.8rem}
/* construction-site scene */
@keyframes fx-rot{to{transform:rotate(360deg)}}
.fx-scene{position:relative;height:clamp(132px,18.4vw,350px);overflow:hidden;margin-top:1.5rem;pointer-events:none}
.fx-scene svg{position:absolute;bottom:0;left:50%;width:max(100%,720px);height:auto;transform:translateX(-50%);overflow:visible}
.fx-scene .tl{fill:rgba(6,182,212,.07);stroke:#38BDF8;stroke-opacity:.7;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
.fx-scene .nf{fill:none}.fx-scene .dm{fill:none;stroke-dasharray:3 5;stroke-opacity:.55}
.fx-spin{transform-box:fill-box;transform-origin:center;animation:fx-rot 9s linear infinite}
@media(prefers-reduced-motion:reduce){.fx-root *,.fx-bg{animation:none!important;transition:none!important}}
</style>

<div class="fx-root">
  <svg class="fx-bg" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <defs>
      <pattern id="fxg" width="48" height="27.7" patternUnits="userSpaceOnUse"><path d="M0 13.85 24 0l24 13.85M0 13.85l24 13.85 24-13.85" fill="none" stroke="#38BDF8" stroke-opacity=".11"/></pattern>
      <path id="fxc" d="M0-210C160-220 300-140 340-30 380 80 280 180 120 210-40 240-220 190-280 80-340-30-160-200 0-210Z" fill="none"/>
    </defs>
    <rect width="1200" height="700" fill="url(#fxg)"/>
    <g transform="translate(820 300)" stroke="#06B6D4" stroke-width="1.2">
      <use href="#fxc" transform="scale(1.25) rotate(8)" stroke-opacity=".10"/><use href="#fxc" transform="scale(1.02) rotate(4)" stroke-opacity=".14"/>
      <use href="#fxc" transform="scale(.8)" stroke-opacity=".18"/><use href="#fxc" transform="scale(.58) rotate(-6)" stroke-opacity=".24"/>
      <use href="#fxc" transform="scale(.36) rotate(-10)" stroke-opacity=".32"/>
    </g>
  </svg>

  <div class="fx-wrap">

    <section class="fx-hero">
      <div>
        <div class="fx-brand"><i><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#031318" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"><path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/></svg></i><span>FDTNexa</span></div>
        <h1 class="fx-h1">{{ $first !== '' ? 'Welcome back, '.$first.'.' : 'Welcome to FDTNexa.' }}<br><em>Test, calculate, report.</em></h1>
        <p class="fx-lead">Your professional workspace for field density testing and geotechnical engineering calculations. Generate reports and soil layers in seconds.</p>
        <span class="fx-status"><b></b>Engineering workspace active</span>
      </div>
      <svg class="fx-iso" viewBox="0 0 240 250" aria-hidden="true">
        <defs><g id="fxs">
          <g transform="translate(20 -80)">
            <polygon points="100,80 200,120 100,160 0,120" style="fill:var(--c)"/>
            <polygon points="0,120 100,160 100,182 0,142" style="fill:var(--c)"/><polygon points="0,120 100,160 100,182 0,142" fill="#000" fill-opacity=".32"/>
            <polygon points="100,160 200,120 200,142 100,182" style="fill:var(--c)"/><polygon points="100,160 200,120 200,142 100,182" fill="#000" fill-opacity=".16"/>
            <polygon points="100,80 200,120 100,160 0,120" fill="#fff" fill-opacity=".08"/>
          </g></g></defs>
        <g class="s" style="--d:-.9s"><use href="#fxs" y="132" style="--c:#164e63"/></g><g class="s" style="--d:-.6s"><use href="#fxs" y="98" style="--c:#0f766e"/></g>
        <g class="s" style="--d:-.3s"><use href="#fxs" y="64" style="--c:#0891b2"/></g><g class="s" style="--d:0s"><use href="#fxs" y="30" style="--c:#22d3ee"/></g>
        <path d="M120 8v78" stroke="#F8FAFC" stroke-opacity=".7" stroke-dasharray="3 4"/><circle cx="120" cy="8" r="4" fill="#06B6D4"/>
      </svg>
    </section>

    <section class="fx-sec">
      <h2 class="fx-h2">Your generation statistics</h2>
      <p class="fx-sub">Track your FDT reports and bulk generation activity.</p>
      <div class="fx-g3">
        <div class="fx-cell"><div class="fx-card" style="--a:var(--cyan)"><div class="fx-in">
          <div class="fx-row"><span class="fx-ico"><svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="m9 14 2 2 4-4"/></svg></span><span class="fx-tag">FDT tool</span></div>
          <div class="fx-num">{{ number_format($fieldReports ?? 0) }}</div><div class="fx-t">FDT Reports Generated</div><div class="fx-d">Individual field density reports</div><div class="fx-ticks"></div>
        </div></div><i class="fx-conn" aria-hidden="true"></i></div>
        <div class="fx-cell"><div class="fx-card" style="--a:var(--sky)"><div class="fx-in">
          <div class="fx-row"><span class="fx-ico"><svg viewBox="0 0 24 24"><rect x="8" y="3" width="12" height="14" rx="2"/><path d="M4 7v12a2 2 0 0 0 2 2h10"/></svg></span><span class="fx-tag">Bulk tool</span></div>
          <div class="fx-num">{{ number_format($bulkFiles ?? 0) }}</div><div class="fx-t">Bulk Files Generated</div><div class="fx-d">Total successful bulk operations</div><div class="fx-ticks"></div>
        </div></div><i class="fx-conn" aria-hidden="true"></i></div>
        <div class="fx-cell fx-last"><div class="fx-card" style="--a:var(--teal)"><div class="fx-in">
          <div class="fx-row"><span class="fx-ico"><svg viewBox="0 0 24 24"><path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg></span><span class="fx-tag">Total layers</span></div>
          <div class="fx-num">{{ number_format($bulkLayers ?? 0) }}</div><div class="fx-t">Soil Layers Generated</div><div class="fx-d">Combined layers across all bulk files</div><div class="fx-ticks"></div>
        </div></div></div>
      </div>
    </section>

    <!-- Analytics (all values derived from the three totals above) -->
    <section class="fx-sec">
      <div class="fx-g3">
        <div class="fx-card" style="--a:var(--cyan)"><div class="fx-in">
          <h3 class="fx-t" style="margin:0">Output mix</h3><p class="fx-d">FDT sheets by how they were made</p>
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
          @unless($sheets)<p class="fx-empty">Generate your first report to see your mix.</p>@endunless
        </div></div>

        <div class="fx-card" style="--a:var(--teal)"><div class="fx-in">
          <h3 class="fx-t" style="margin:0">Bulk efficiency</h3><p class="fx-d">Average layers per bulk file (max 30)</p>
          <div class="fx-ring">
            <svg class="fx-donut" viewBox="0 0 100 100" role="img" aria-label="{{ $avg }} layers per bulk file on average">
              <circle class="tr" cx="50" cy="50" r="40"/>
              @if($avg)<circle class="sg" cx="50" cy="50" r="40" stroke="#14B8A6" stroke-linecap="round" stroke-dasharray="{{ $g }} {{ $C }}"/>@endif
              <text x="50" y="52" font-size="19">{{ $avg }}</text><text class="sm" x="50" y="63">layers / file</text>
            </svg>
            <ul class="fx-leg">
              <li><span><i style="--c:#38BDF8"></i>Bulk files</span><b>{{ number_format($bf) }}</b></li>
              <li><span><i style="--c:#14B8A6"></i>Soil layers</span><b>{{ number_format($bl) }}</b></li>
            </ul>
          </div>
          @unless($bf)<p class="fx-empty">Run the bulk generator to see efficiency.</p>@endunless
        </div></div>

        <div class="fx-card fx-last" style="--a:var(--sky)"><div class="fx-in">
          <h3 class="fx-t" style="margin:0">Relative volume</h3><p class="fx-d">Each total compared with your largest</p>
          <div class="fx-bar"><div><span>FDT reports</span><b>{{ number_format($fr) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#06B6D4;width:{{ round($fr / $max * 100) }}%"></div></div></div>
          <div class="fx-bar"><div><span>Bulk files</span><b>{{ number_format($bf) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#38BDF8;width:{{ round($bf / $max * 100) }}%"></div></div></div>
          <div class="fx-bar"><div><span>Soil layers</span><b>{{ number_format($bl) }}</b></div><div class="fx-trk"><div class="fx-fill" style="--c:#14B8A6;width:{{ round($bl / $max * 100) }}%"></div></div></div>
        </div></div>
      </div>
    </section>

    <i class="fx-vline" aria-hidden="true"></i>

    <section class="fx-sec" style="margin-top:1.5rem">
      <h2 class="fx-h2">Engineering tools</h2>
      <p class="fx-sub">Select a module to start your work.</p>
      <div class="fx-g2">
        <a href="{{ route('field-tool.index') }}" class="fx-card fx-tool" style="--a:var(--cyan)"><div class="fx-in">
          <div class="fx-row fx-mid"><span class="fx-ico" style="width:54px;height:54px"><svg viewBox="0 0 24 24"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 2 3h10a2 2 0 0 0 2-3l-5-9V3"/><path d="M8 15h8"/></svg></span><span class="fx-tag">Excel reports</span></div>
          <h3 class="fx-t" style="font-size:1.2rem;margin-top:1.2rem">Field Density Test</h3>
          <p class="fx-d" style="font-size:.93rem">Generate field density test calculations and Excel reports for geotechnical quality control.</p>
          <span class="fx-arrow">Open module <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
        </div></a>
        <a href="{{ route('bulk.index') }}" class="fx-card fx-tool" style="--a:var(--teal)"><div class="fx-in">
          <div class="fx-row fx-mid"><span class="fx-ico" style="width:54px;height:54px"><svg viewBox="0 0 24 24"><path d="m12 2 10 5-10 5L2 7z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg></span><span class="fx-tag">Up to 30 layers</span></div>
          <h3 class="fx-t" style="font-size:1.2rem;margin-top:1.2rem">Bulk Layer Generator</h3>
          <p class="fx-d" style="font-size:.93rem">Generate multiple soil layers and their corresponding FDT Excel sheets in one operation.</p>
          <span class="fx-arrow">Open module <svg viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></span>
        </div></a>
      </div>
    </section>

    <section class="fx-sec">
      <h2 class="fx-h2" style="margin-bottom:1.25rem">Account &amp; services</h2>
      <div class="fx-g2">
        <a href="{{ route('pricing') }}" class="fx-card" style="--a:var(--sky)"><div class="fx-in fx-row fx-mid fx-gap" style="justify-content:flex-start">
          <span class="fx-ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg></span>
          <span class="fx-min"><span class="fx-t fx-blk" style="margin:0">Plans &amp; Pricing</span><span class="fx-d fx-blk">Explore FDTNexa plans and subscriptions.</span></span>
        </div></a>
        <a href="{{ route('profile.edit') }}" class="fx-card" style="--a:var(--teal)"><div class="fx-in fx-row fx-mid fx-gap" style="justify-content:flex-start">
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

  <!-- construction-site scene: excavator, roller, sand-cone test, theodolite, levelling staff on a soil profile -->
  <div class="fx-scene" aria-hidden="true">
    <svg viewBox="0 0 1200 220">
      <defs>
        <pattern id="fxh" width="10" height="10" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0v10" stroke="#38BDF8" stroke-opacity=".16"/></pattern>
        <linearGradient id="fxgd" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#14566a" stop-opacity=".6"/><stop offset="1" stop-color="#0B1220" stop-opacity="0"/></linearGradient>
      </defs>
      <rect x="-900" y="170" width="3000" height="80" fill="url(#fxgd)"/><rect x="-900" y="170" width="3000" height="80" fill="url(#fxh)"/>
      <path d="M-900 187H2100M-900 204H2100" class="nf" stroke="#38BDF8" stroke-opacity=".3" stroke-dasharray="2 6"/>
      <path d="M-900 170H2100" class="nf" stroke="#22D3EE" stroke-opacity=".65" stroke-width="1.6"/>

      <g class="tl">
        <!-- excavator -->
        <rect x="100" y="152" width="124" height="18" rx="9"/><circle cx="116" cy="161" r="5"/><circle cx="208" cy="161" r="5"/>
        <path d="M120 152v-14h66v14"/><path d="M132 138v-26h38l16 26"/><path d="M140 118h22v14h-22z"/>
        <path class="nf" d="M186 128 252 74 312 118"/><path d="M312 118l14 30-26-4z"/><path class="nf" d="M196 140 238 100"/>
        <!-- roller compactor -->
        <circle cx="470" cy="146" r="24"/><circle cx="470" cy="146" r="6"/>
        <g class="fx-spin nf"><path d="M470 124v44M448 146h44M454 130l32 32M486 130l-32 32"/></g>
        <circle cx="596" cy="152" r="18"/><circle cx="596" cy="152" r="4"/>
        <path d="M470 122h72l14-26h40v40"/><path d="M552 96V74h34v22"/><path d="M500 104h40v18h-40z"/>
        <path class="dm" d="M446 62H620M446 56v12M620 56v12"/>
        <!-- sand-cone density test -->
        <rect x="728" y="166" width="76" height="4"/><path d="M752 166l16-18h16l16 18"/><rect x="760" y="140" width="16" height="8"/>
        <rect x="748" y="96" width="56" height="44" rx="8"/><path class="nf" d="M748 112h56"/>
        <path class="dm" d="M752 170v16h28v-16M834 170v16M828 170h12M828 186h12"/>
        <!-- theodolite on tripod -->
        <path class="nf" d="M975 112 940 170M975 112 975 170M975 112 1010 170"/><rect x="963" y="94" width="24" height="18" rx="3"/>
        <rect x="956" y="78" width="38" height="10" rx="3"/><path class="dm" d="M975 112v50"/><path d="M971 162h8l-4 8z"/>
        <!-- levelling staff + stake -->
        <rect x="1086" y="92" width="12" height="78"/><path class="nf" d="M1086 106h7M1086 120h7M1086 134h7M1086 148h7M1086 162h7"/>
        <path d="M44 170V132l22 7-22 8"/>
      </g>
    </svg>
  </div>
</div>

<script>
(function(){
  if(!matchMedia('(hover:hover) and (pointer:fine)').matches||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
  document.querySelectorAll('.fx-card').forEach(function(c){
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
</x-app-layout>
