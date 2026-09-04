<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
$key = (string)($_GET['key'] ?? '');
if (!hash_equals('agenda-2026-rclsmo', $key)) { http_response_code(404); exit('Not found'); }
$html = file_get_contents(__DIR__ . '/../index.html');
if ($html === false) { http_response_code(500); exit('Preview unavailable'); }
$style = <<<'HTML'
<style>
.agenda-preview .c26-intro{position:relative;padding:clamp(36px,4vw,52px) 0;background:linear-gradient(180deg,rgba(5,19,34,.96),rgba(7,25,43,.98));overflow:hidden}
.agenda-preview .c26-intro:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 10% 25%,rgba(79,219,240,.07),transparent 31%),linear-gradient(115deg,transparent 58%,rgba(91,159,218,.035));pointer-events:none}
.agenda-preview .c26-intro .container{max-width:1180px}
.agenda-preview .c26-intro__grid{position:relative;display:grid;grid-template-columns:minmax(0,.86fr) minmax(0,1.14fr);gap:clamp(28px,4vw,52px);align-items:center}
.agenda-preview .c26-intro .c26-section-heading{min-width:0;max-width:475px;margin:0;text-align:left}
.agenda-preview .c26-intro .c26-section-heading>span{margin-bottom:12px}
.agenda-preview .c26-intro .c26-section-heading h2{max-width:475px;margin:0 0 14px;font-size:clamp(30px,3.25vw,43px);line-height:1.05;letter-spacing:-.045em;text-align:left;text-wrap:balance}
.agenda-preview .c26-intro .c26-section-heading p{max-width:460px;margin:0;color:#a8bac9;font-size:14px;line-height:1.58;text-align:left;text-wrap:pretty}
.agenda-preview .c26-principles{display:grid;gap:0;padding:5px 18px;border:1px solid rgba(79,219,240,.17);border-radius:23px;background:linear-gradient(135deg,rgba(18,46,70,.82),rgba(7,26,44,.88));box-shadow:0 24px 64px rgba(0,9,20,.20),inset 0 1px rgba(255,255,255,.025);overflow:hidden}
.agenda-preview .c26-principles article{position:relative;display:grid;grid-template-columns:42px minmax(170px,.9fr) minmax(0,1.3fr);gap:15px;align-items:center;min-height:74px;margin:0;padding:14px 4px;border:0;border-bottom:1px solid rgba(109,169,198,.13);border-radius:0;background:transparent;box-shadow:none}
.agenda-preview .c26-principles article:last-child{border-bottom:0}
.agenda-preview .c26-principles article:before,.agenda-preview .c26-principles article:after{display:none}
.agenda-preview .c26-principles article>span{display:grid;width:34px;height:34px;place-items:center;margin:0;border:1px solid rgba(79,219,240,.18);border-radius:10px;background:rgba(79,219,240,.055);color:#55ddf1;font-size:10px;font-weight:900;letter-spacing:.08em}
.agenda-preview .c26-principles article h3{margin:0;color:#edf6fa;font-size:15px;line-height:1.3;font-weight:760;text-wrap:balance}
.agenda-preview .c26-principles article p{margin:0;color:#91a8ba;font-size:12px;line-height:1.48;text-wrap:pretty}
.agenda-preview .c26-principles article:hover{background:linear-gradient(90deg,rgba(79,219,240,.025),transparent)}
.agenda-preview .c26-program{position:relative;isolation:isolate;overflow:hidden}
.agenda-preview .c26-program:before{content:"";position:absolute;z-index:-1;inset:0;background:radial-gradient(circle at 9% 13%,rgba(63,220,243,.08),transparent 27%),radial-gradient(circle at 91% 36%,rgba(196,71,225,.07),transparent 30%);pointer-events:none}
.agenda-preview .c26-program .container{max-width:1180px}
.agenda-preview .c26-section-heading{max-width:900px;margin-inline:auto;text-align:center}
.agenda-preview .c26-section-heading>span{display:inline-flex;align-items:center;gap:9px}
.agenda-preview .c26-section-heading>span:before{content:"";width:7px;height:7px;border-radius:50%;background:#50def2;box-shadow:0 0 0 5px rgba(80,222,242,.10),0 0 20px rgba(80,222,242,.32)}
.agenda-preview .c26-section-heading h2{max-width:830px;margin-inline:auto;text-wrap:balance}
.agenda-preview .c26-section-heading p{max-width:720px;margin-inline:auto;text-wrap:pretty}
.agenda-private-note{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:8px 12px;border:1px solid rgba(80,222,242,.16);border-radius:999px;background:rgba(9,29,49,.72);color:#91aabd;font-size:11px;font-weight:750;letter-spacing:.04em}
.agenda-private-note:before{content:"";width:6px;height:6px;border-radius:50%;background:#f5c566;box-shadow:0 0 12px rgba(245,197,102,.55)}
.agenda-preview .c26-program-disclosure{border:1px solid rgba(80,222,242,.18);border-radius:26px;background:linear-gradient(180deg,rgba(8,27,47,.92),rgba(5,21,37,.96));box-shadow:0 28px 80px rgba(0,8,19,.26),inset 0 1px 0 rgba(255,255,255,.03);overflow:hidden}
.agenda-preview .c26-program-disclosure summary{min-height:90px;padding:22px 26px;background:linear-gradient(90deg,rgba(80,222,242,.055),transparent 44%,rgba(196,71,225,.035));border-radius:25px;transition:background .2s ease}
.agenda-preview .c26-program-disclosure summary:hover{background-color:rgba(80,222,242,.035)}
.agenda-preview .c26-program-disclosure[open] summary{border-bottom:1px solid rgba(80,222,242,.12);border-bottom-left-radius:0;border-bottom-right-radius:0}
.agenda-preview .c26-program-disclosure summary small{color:#61e4f4}
.agenda-preview .c26-program-disclosure summary em{font-variant-numeric:tabular-nums}
.c26-agenda{position:relative;padding:0 26px 34px}
.agenda-overview{position:relative;margin:26px 0 20px;padding:24px;border:1px solid rgba(80,222,242,.13);border-radius:22px;background:linear-gradient(135deg,rgba(19,48,73,.62),rgba(8,29,50,.38));overflow:hidden}
.agenda-overview:after{content:"";position:absolute;right:-76px;top:-108px;width:260px;height:260px;border-radius:50%;border:48px solid rgba(80,222,242,.025);pointer-events:none}
.agenda-dayline{position:relative;z-index:1;display:grid;grid-template-columns:minmax(0,1.35fr) minmax(210px,.65fr);gap:28px;align-items:end;margin-bottom:20px}
.agenda-dayline__eyebrow{display:block;margin-bottom:8px;color:#56dff2;font-size:11px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
.agenda-dayline__date{display:block;color:#f4f9fc;font-size:clamp(25px,3.4vw,39px);font-weight:850;line-height:1.06;letter-spacing:-.035em;text-wrap:balance}
.agenda-dayline__hours{justify-self:end;text-align:right}
.agenda-dayline__hours span{display:block;color:#8ea7bb;font-size:11px;font-weight:850;letter-spacing:.12em;text-transform:uppercase}
.agenda-dayline__hours strong{display:block;margin-top:6px;color:#dff7fb;font-size:20px;font-variant-numeric:tabular-nums}
.agenda-progress{position:relative;height:3px;margin-bottom:20px;border-radius:999px;background:rgba(255,255,255,.06);overflow:hidden}
.agenda-progress:before{content:"";position:absolute;inset:0;width:100%;background:linear-gradient(90deg,#48d9ef 0 47%,#a66ee8 72%,#d457dd 100%)}
.agenda-facts{position:relative;z-index:1;display:grid;grid-template-columns:.7fr .7fr 1.6fr;gap:10px}
.agenda-fact{min-width:0;padding:13px 15px;border:1px solid rgba(80,222,242,.11);border-radius:14px;background:rgba(4,20,36,.38)}
.agenda-fact small{display:block;margin-bottom:4px;color:#7f9aae;font-size:9px;font-weight:850;letter-spacing:.13em;text-transform:uppercase}
.agenda-fact strong{display:block;color:#eaf6fa;font-size:14px;font-weight:760;line-height:1.35;overflow-wrap:anywhere}
.agenda-nav{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin:0 0 30px}
.agenda-nav a{position:relative;display:flex;min-width:0;min-height:62px;align-items:center;gap:10px;padding:11px 13px;border:1px solid rgba(80,222,242,.10);border-radius:15px;background:rgba(255,255,255,.018);color:#9eb3c3;text-decoration:none;font-size:11px;font-weight:700;line-height:1.3;transition:transform .18s ease,border-color .18s ease,background .18s ease,color .18s ease}
.agenda-nav a:hover,.agenda-nav a:focus-visible{transform:translateY(-2px);border-color:rgba(80,222,242,.30);background:rgba(80,222,242,.055);color:#eef9fb;outline:none}
.agenda-nav b{flex:0 0 auto;display:grid;width:32px;height:32px;place-items:center;border-radius:10px;background:rgba(80,222,242,.08);color:#57def1;font-size:11px;letter-spacing:.05em}
.agenda-timeline{position:relative}
.agenda-timeline:before{content:"";position:absolute;left:139px;top:16px;bottom:18px;width:1px;background:linear-gradient(180deg,rgba(80,222,242,.06),rgba(80,222,242,.38) 14%,rgba(190,79,225,.32) 82%,rgba(80,222,242,.06))}
.agenda-block{--block-accent:#50def2;scroll-margin-top:96px;position:relative;margin:34px 0 10px;padding:25px 28px 24px 164px;border:1px solid color-mix(in srgb,var(--block-accent) 25%,transparent);border-radius:22px;background:linear-gradient(115deg,rgba(18,47,72,.94),rgba(7,25,43,.96));box-shadow:0 16px 42px rgba(0,10,23,.16);overflow:hidden}
.agenda-block:nth-of-type(2){--block-accent:#79d7ff}
.agenda-block:nth-of-type(3){--block-accent:#9c83ee}
.agenda-block:nth-of-type(4){--block-accent:#d26ae0}
.agenda-block:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,color-mix(in srgb,var(--block-accent) 8%,transparent),transparent 52%),radial-gradient(circle at 91% 12%,color-mix(in srgb,var(--block-accent) 12%,transparent),transparent 32%);pointer-events:none}
.agenda-block:after{content:"";position:absolute;left:0;top:18px;bottom:18px;width:3px;border-radius:0 4px 4px 0;background:var(--block-accent);box-shadow:0 0 18px color-mix(in srgb,var(--block-accent) 38%,transparent)}
.agenda-block__num{position:absolute;left:25px;top:50%;transform:translateY(-50%);font-size:58px;line-height:1;font-weight:900;letter-spacing:-.06em;color:color-mix(in srgb,var(--block-accent) 19%,transparent)}
.agenda-block__label{position:relative;z-index:1;display:block;margin-bottom:7px;color:var(--block-accent);font-size:10px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
.agenda-block h3{position:relative;z-index:1;max-width:790px;margin:0;color:#f4f8fb;font-size:clamp(20px,2.3vw,29px);line-height:1.2;text-wrap:balance}
.c26-agenda__item.agenda-talk{position:relative;display:grid;grid-template-columns:116px minmax(0,1fr);gap:26px;margin:2px 0;padding:17px 20px;border:1px solid transparent;border-radius:17px;background:transparent;transition:transform .18s ease,background .18s ease,border-color .18s ease}
.c26-agenda__item.agenda-talk:hover{transform:translateX(3px);border-color:rgba(80,222,242,.08);background:rgba(80,222,242,.035)}
.c26-agenda__item.agenda-talk:after{content:"";position:absolute;left:137px;top:28px;width:7px;height:7px;border-radius:50%;background:#50def2;box-shadow:0 0 0 5px #071a2d,0 0 17px rgba(80,222,242,.42)}
.c26-agenda__item.agenda-talk time{padding-top:1px;color:#56dff2;font-size:13px;font-weight:850;letter-spacing:.01em;font-variant-numeric:tabular-nums;white-space:nowrap}
.agenda-talk__meta{display:flex;align-items:center;margin-bottom:11px}
.agenda-talk__tag{position:relative;display:block;padding-left:38px;color:#8fa9bb;font-size:9px;line-height:1.2;font-weight:850;letter-spacing:.13em;text-transform:uppercase;white-space:nowrap;font-variant-numeric:tabular-nums}
.agenda-talk__tag:before{content:"";position:absolute;left:0;top:50%;width:26px;height:2px;border-radius:999px;background:linear-gradient(90deg,#54dff2,rgba(84,223,242,.15));box-shadow:0 0 13px rgba(84,223,242,.28);transform:translateY(-50%)}
.c26-agenda__item.agenda-talk h3{max-width:840px;margin:0 0 8px;color:#f1f7fa;font-size:17px;line-height:1.4;font-weight:720;text-wrap:pretty;overflow-wrap:anywhere}
.agenda-speaker{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin:0!important;color:#9db1c2!important;font-size:13px!important;line-height:1.5!important;overflow-wrap:anywhere}
.agenda-speaker strong{color:#dce8f0;font-weight:720}
.agenda-speaker strong:before{content:"";display:inline-block;width:5px;height:5px;margin:0 8px 2px 0;border-radius:50%;background:#718da4}
.agenda-service{position:relative;display:grid;grid-template-columns:116px minmax(0,1fr);gap:26px;align-items:center;margin:8px 0;padding:14px 20px;border:1px solid rgba(255,255,255,.045);border-radius:15px;background:rgba(255,255,255,.022)}
.agenda-service time{color:#90a8ba;font-size:12px;font-weight:850;font-variant-numeric:tabular-nums;white-space:nowrap}
.agenda-service>div{min-width:0}
.agenda-service>div>div{display:flex;align-items:center;justify-content:space-between;gap:16px}
.agenda-service span{color:#849cad;font-size:9px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}
.agenda-service h3{margin:0;color:#d8e4ec;font-size:14px;overflow-wrap:anywhere}
.agenda-service .agenda-speaker{margin-top:5px!important}
.agenda-opening{border-color:rgba(80,222,242,.13);background:linear-gradient(90deg,rgba(80,222,242,.045),rgba(80,222,242,.015))}
.agenda-opening h3{font-size:16px;color:#eff9fb}
@media(max-width:900px){
 .agenda-preview .c26-intro__grid{grid-template-columns:1fr;gap:24px}
 .agenda-preview .c26-intro .c26-section-heading{max-width:720px}
 .agenda-preview .c26-intro .c26-section-heading h2,.agenda-preview .c26-intro .c26-section-heading p{max-width:700px}
 .agenda-preview .c26-principles article{grid-template-columns:42px minmax(180px,.82fr) minmax(0,1.18fr)}
}
@media(max-width:720px){
 .agenda-preview .c26-intro{padding:34px 0}
 .agenda-preview .c26-intro .container{padding-inline:18px}
 .agenda-preview .c26-intro__grid{gap:21px}
 .agenda-preview .c26-intro .c26-section-heading h2{margin-bottom:13px;font-size:clamp(28px,9vw,36px);line-height:1.08}
 .agenda-preview .c26-intro .c26-section-heading p{font-size:13px;line-height:1.55}
 .agenda-preview .c26-principles{padding:5px 16px;border-radius:19px}
 .agenda-preview .c26-principles article{grid-template-columns:38px minmax(0,1fr);grid-template-rows:auto auto;gap:5px 12px;min-height:0;padding:17px 0}
 .agenda-preview .c26-principles article>span{grid-row:1/3;width:31px;height:31px}
 .agenda-preview .c26-principles article h3{grid-column:2;font-size:15px}
 .agenda-preview .c26-principles article p{grid-column:2;font-size:12px;line-height:1.52}
}
@media(max-width:900px){
 .agenda-preview .c26-program .container{padding-inline:20px}
 .c26-agenda{padding-inline:18px}
 .agenda-nav{grid-template-columns:repeat(2,minmax(0,1fr))}
 .agenda-block{padding-left:148px}
}
@media(max-width:720px){
 .agenda-preview .c26-section-heading{text-align:left}
 .agenda-preview .c26-section-heading h2,.agenda-preview .c26-section-heading p{margin-inline:0}
 .agenda-preview .c26-program .container{padding-inline:14px}
 .agenda-preview .c26-program-disclosure{border-radius:20px}
 .agenda-preview .c26-program-disclosure summary{min-height:0;padding:18px;border-radius:19px}
 .agenda-preview .c26-program-disclosure summary em{display:none}
 .c26-agenda{padding:0 12px 25px}
 .agenda-overview{margin:18px 0 14px;padding:18px;border-radius:18px}
 .agenda-dayline{grid-template-columns:1fr;gap:14px;align-items:start}
 .agenda-dayline__date{font-size:27px}
 .agenda-dayline__hours{justify-self:start;text-align:left}
 .agenda-dayline__hours strong{font-size:17px}
 .agenda-facts{grid-template-columns:repeat(2,minmax(0,1fr))}
 .agenda-fact:last-child{grid-column:1/-1}
 .agenda-nav{display:flex;gap:8px;margin:0 -12px 25px;padding:3px 12px 10px;overflow-x:auto;overscroll-behavior-inline:contain;scroll-snap-type:x proximity;scrollbar-width:none}
 .agenda-nav::-webkit-scrollbar{display:none}
 .agenda-nav a{flex:0 0 208px;min-height:58px;scroll-snap-align:start}
 .agenda-timeline:before{left:26px;top:10px}
 .agenda-block{margin:25px 0 10px;padding:21px 17px 20px 65px;border-radius:18px}
 .agenda-block__num{left:15px;font-size:38px}
 .agenda-block h3{font-size:19px}
 .c26-agenda__item.agenda-talk,.agenda-service{grid-template-columns:1fr;gap:7px;margin:1px 0;padding:16px 13px 16px 47px;border-radius:14px}
 .c26-agenda__item.agenda-talk:hover{transform:none}
 .c26-agenda__item.agenda-talk:after{left:23px;top:23px}
 .c26-agenda__item.agenda-talk time,.agenda-service time{font-size:11px}
 .c26-agenda__item.agenda-talk h3{font-size:15px;line-height:1.42}
 .agenda-service>div>div{display:block}
 .agenda-service span{display:block;margin-bottom:4px}
 .agenda-service h3{font-size:14px}
 .agenda-speaker{font-size:12px!important}
}
@media(max-width:390px){
 .agenda-private-note{align-items:flex-start;border-radius:14px;line-height:1.35}
 .agenda-facts{grid-template-columns:1fr}
 .agenda-fact:last-child{grid-column:auto}
 .agenda-block{padding-left:58px}
 .agenda-block__num{font-size:34px}
 .agenda-nav a{flex-basis:188px}
}

.agenda-speaker-row{display:flex;align-items:center;gap:15px;margin-top:12px}
.agenda-speaker-row .agenda-speaker{margin:0}
.agenda-speaker-profile{position:relative;display:block;flex:0 0 auto;padding:0;border:0;border-radius:18px;color:inherit;background:transparent;cursor:zoom-in}
.agenda-speaker-profile:focus-visible{outline:2px solid #55dcf1;outline-offset:4px}
.agenda-speaker-photo{display:block;width:74px;height:74px;border:1px solid rgba(85,220,241,.32);border-radius:18px;background:#102a40;box-shadow:0 10px 26px rgba(0,9,21,.24);object-fit:cover;object-position:var(--speaker-focus,50% 28%);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
.agenda-speaker-profile:hover .agenda-speaker-photo,.agenda-speaker-profile:focus-visible .agenda-speaker-photo{transform:translateY(-2px) scale(1.04);border-color:rgba(85,220,241,.62);box-shadow:0 15px 36px rgba(23,181,215,.2)}
.agenda-speaker-copy{min-width:0}
.agenda-speaker-hint{display:none}
.speaker-popover-shade{position:fixed;z-index:9998;inset:0;background:rgba(1,9,18,.72);backdrop-filter:blur(5px);opacity:0;visibility:hidden;pointer-events:none;transition:opacity .18s ease,visibility .18s ease}
.speaker-popover{position:fixed;z-index:9999;left:16px;top:16px;width:min(330px,calc(100vw - 32px));max-height:calc(100vh - 32px);max-height:calc(100dvh - 32px);padding:10px;border:1px solid rgba(85,220,241,.40);border-radius:22px;background:linear-gradient(155deg,#10314c,#061625 82%);box-shadow:0 28px 90px rgba(0,5,15,.64),inset 0 1px rgba(255,255,255,.04);overflow:auto;overscroll-behavior:contain;opacity:0;visibility:hidden;pointer-events:none;transform:translateY(8px) scale(.97);transition:opacity .18s ease,visibility .18s ease,transform .18s ease}
.speaker-popover.is-open{opacity:1;visibility:visible;pointer-events:auto;transform:none}
.speaker-popover__close{position:absolute;z-index:2;top:18px;right:18px;display:grid;width:34px;height:34px;place-items:center;padding:0;border:1px solid rgba(255,255,255,.18);border-radius:50%;background:rgba(2,14,25,.72);color:#fff;font-size:24px;line-height:1;cursor:pointer;transition:background .18s ease,border-color .18s ease}
.speaker-popover__close:hover,.speaker-popover__close:focus-visible{border-color:rgba(85,220,241,.7);background:#123a55;outline:none}
.speaker-popover__photo{display:block;width:100%;height:260px;border-radius:15px;background:#102a40;object-fit:cover;object-position:var(--speaker-focus,50% 28%)}
.speaker-popover__copy{display:block;padding:15px 9px 10px;text-align:left}
.speaker-popover__copy strong,.speaker-popover__copy small{display:block}
.speaker-popover__copy strong{padding-right:34px;color:#f5fbff;font-size:17px;line-height:1.32}
.speaker-popover__copy small{margin-top:7px;color:#a9bdca;font-size:12px;font-weight:500;line-height:1.5;letter-spacing:0;text-transform:none}
@media(min-width:721px) and (hover:hover) and (pointer:fine){.speaker-popover-shade{display:none}.speaker-popover__close{display:none}}
@media(max-width:720px),(hover:none),(pointer:coarse){
 .c26-agenda__item.agenda-talk{margin:8px 0;padding:17px 14px 17px 49px;border-color:rgba(80,222,242,.10);background:linear-gradient(135deg,rgba(13,39,61,.82),rgba(5,23,40,.72));box-shadow:0 10px 28px rgba(0,8,18,.13)}
 .c26-agenda__item.agenda-talk:hover{border-color:rgba(80,222,242,.10);background:linear-gradient(135deg,rgba(13,39,61,.82),rgba(5,23,40,.72))}
 .agenda-speaker-row{display:grid;grid-template-columns:68px minmax(0,1fr);gap:13px;align-items:center;margin-top:14px}
 .agenda-speaker-row--text{grid-template-columns:1fr}
 .agenda-speaker-photo{width:68px;height:68px;border-radius:16px}
 .agenda-speaker-profile{align-self:start;cursor:pointer}
 .agenda-speaker-profile:after{content:"+";position:absolute;right:-4px;bottom:-4px;display:grid;width:23px;height:23px;place-items:center;border:2px solid #092039;border-radius:50%;background:#55dcf1;color:#052033;font-size:17px;font-weight:800;line-height:1;box-shadow:0 5px 15px rgba(0,9,21,.34)}
 .agenda-speaker-copy .agenda-speaker{font-size:13px!important;line-height:1.42!important}
 .agenda-speaker-copy .agenda-speaker strong{display:block;color:#e8f4f8}
 .agenda-speaker-hint{display:block;margin-top:6px;color:#71cbd8;font-size:9px;font-weight:750;line-height:1.35;letter-spacing:.035em}
 .agenda-talk__meta{margin-bottom:12px}
 .agenda-talk__tag{padding-left:33px;color:#9bb1c0;font-size:8.5px;letter-spacing:.115em}
 .agenda-talk__tag:before{width:22px}
 .speaker-popover-shade.is-open{opacity:1;visibility:visible;pointer-events:auto}
 .speaker-popover{top:50%;left:50%;width:min(350px,calc(100vw - 28px));max-height:calc(100vh - 28px);max-height:calc(100dvh - 28px);transform:translate(-50%,-46%) scale(.97)}
 .speaker-popover.is-open{transform:translate(-50%,-50%) scale(1)}
 .speaker-popover__photo{height:min(370px,55dvh)}
 .speaker-popover__copy{padding:14px 9px 11px}
 .speaker-popover__copy strong{font-size:16px}
 body.speaker-popover-open{overflow:hidden}
}
@media(max-width:720px){
 .agenda-preview .c26-program .container{padding-inline:10px}
 .agenda-preview .c26-program-disclosure{border-radius:18px}
 .c26-agenda{padding:0 8px 24px}
 .agenda-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:0 0 24px;padding:0;overflow:visible}
 .agenda-nav a{min-width:0;min-height:64px;padding:10px;gap:9px;border-radius:14px;font-size:10px;overflow-wrap:anywhere}
 .agenda-nav b{width:30px;height:30px;border-radius:9px}
 .agenda-timeline:before{display:none}
 .agenda-block{margin:24px 0 12px;padding:18px;border-radius:18px}
 .agenda-block:after{top:15px;bottom:15px;width:3px}
 .agenda-block__num{display:none}
 .agenda-block__label{margin-bottom:7px;font-size:8.5px;line-height:1.35;letter-spacing:.13em}
 .agenda-block h3{max-width:none;font-size:18px;line-height:1.28;letter-spacing:-.018em;text-wrap:pretty}
 .c26-agenda__item.agenda-talk{grid-template-columns:auto minmax(0,1fr);gap:10px 12px;margin:10px 0;padding:17px;border-radius:17px;opacity:1;transform:none}
 .c26-agenda__item.agenda-talk>div{display:contents}
 .c26-agenda__item.agenda-talk:after{display:none}
 .c26-agenda__item.agenda-talk time{grid-column:1;align-self:center;padding:0;font-size:12px;line-height:1.2}
 .agenda-talk__meta{grid-column:2;align-self:center;justify-self:end;margin:0}
 .agenda-preview .c26-agenda__item .agenda-talk__tag{display:block;margin:0;padding-left:22px;color:#91aabd;font-size:8px;line-height:1.2;font-weight:850;letter-spacing:.095em;text-transform:uppercase;white-space:nowrap}
 .agenda-preview .c26-agenda__item .agenda-talk__tag:before{width:13px}
 .c26-agenda__item.agenda-talk h3{grid-column:1/-1;max-width:none;margin:5px 0 0;font-size:16px;line-height:1.38;font-weight:740;text-align:left;text-wrap:pretty}
 .agenda-speaker-row{grid-column:1/-1;grid-template-columns:54px minmax(0,1fr);gap:11px;align-items:center;margin-top:4px;padding:9px 10px;border:1px solid rgba(80,222,242,.10);border-radius:14px;background:rgba(4,20,35,.38)}
 .agenda-speaker-row--text{grid-template-columns:1fr;padding:0;border:0;background:none}
 .agenda-speaker-photo{width:54px;height:54px;border-radius:13px}
 .agenda-speaker-profile:after{right:-3px;bottom:-3px;width:18px;height:18px;border-width:2px;font-size:13px}
 .agenda-speaker-copy .agenda-speaker{display:block;margin:0!important;font-size:12px!important;line-height:1.4!important;text-align:left}
 .agenda-speaker-copy .agenda-speaker strong:before{display:none}
 .agenda-preview .c26-agenda__item .agenda-speaker-hint{display:block;margin:4px 0 0;color:#75b9c6;font-size:9px;font-weight:650;line-height:1.35;letter-spacing:0;text-transform:none}
 .agenda-service{grid-template-columns:1fr;gap:7px;margin:9px 0;padding:16px 17px;border-radius:16px}
 .agenda-service time{font-size:11px}
 .agenda-service>div>div{display:block}
 .agenda-service span{margin-bottom:5px}
 .agenda-service h3{font-size:14px;line-height:1.4}
 .agenda-opening .agenda-speaker{display:grid;gap:5px;margin-top:9px!important}
 .agenda-opening .agenda-speaker strong{display:block}
 .agenda-preview .back-to-top{display:none!important}
}
@media(max-width:390px){.c26-agenda__item.agenda-talk{padding:14px}.agenda-speaker-row{grid-template-columns:50px minmax(0,1fr);gap:10px;padding:8px}.agenda-speaker-row--text{grid-template-columns:1fr;padding:0}.agenda-speaker-photo{width:50px;height:50px}.agenda-preview .c26-agenda__item .agenda-speaker-hint{font-size:8.5px}.speaker-popover{width:calc(100vw - 20px);max-height:calc(100dvh - 20px)}.speaker-popover__photo{height:min(330px,52dvh)}}
@media(prefers-reduced-motion:reduce){.agenda-nav a,.c26-agenda__item.agenda-talk{transition:none}.agenda-nav a:hover,.c26-agenda__item.agenda-talk:hover{transform:none}}
</style>
HTML;
$script = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded',()=>{
 document.documentElement.classList.add('agenda-preview');
 const intro=document.querySelector('.c26-intro .c26-section-heading');
 if(intro){const introTitle=intro.querySelector('h2');const introLead=intro.querySelector('p');if(introTitle) introTitle.textContent='Лабораторный результат — часть маршрута пациента';if(introLead) introLead.textContent='Как данные лаборатории помогают раньше выявлять заболевание, подтверждать диагноз и доводить пациента до следующего этапа помощи.';}
 document.querySelectorAll('.c26-tracks, .c26-audience').forEach(section=>section.remove());
 const root=document.querySelector('.c26-program'); if(!root) return;
 const eyebrow=root.querySelector('.c26-section-heading > span'); if(eyebrow) eyebrow.textContent='Программа конференции';
 const title=root.querySelector('.c26-section-heading h2'); if(title) title.textContent='Один день — четыре содержательных блока';
 const lead=root.querySelector('.c26-section-heading p'); if(lead){lead.textContent='Последовательность выступлений сформирована. Сетка показана в рабочем закрытом контуре до публикации.';lead.insertAdjacentHTML('afterend','<div class="agenda-private-note">Закрытый предварительный просмотр · ссылка не размещена в публичной навигации</div>');}
 const sum=root.querySelector('[data-agenda-disclosure] summary em'); if(sum) sum.textContent='4 блока · 20 докладов · 09:30–18:00';
 const disclosure=root.querySelector('[data-agenda-disclosure]');
 const label=root.querySelector('[data-agenda-toggle-label]');
 const syncLabel=()=>{if(label) label.textContent=disclosure?.open?'Свернуть полную программу':'Показать полную программу';};
 if(disclosure){disclosure.open=true;disclosure.addEventListener('toggle',syncLabel);} syncLabel();
 const agenda=root.querySelector('.c26-agenda'); if(!agenda) return;
 const attr=value=>String(value).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
 const talk=(time,n,title,speaker,photo='',credentials='',focus='50% 28%')=>`<article class="c26-agenda__item agenda-talk"><time>${time}</time><div><div class="agenda-talk__meta"><span class="agenda-talk__tag">${String(n).padStart(2,'0')} · выступление</span></div><h3>${title}</h3><div class="agenda-speaker-row${photo?'':' agenda-speaker-row--text'}">${photo?`<button class="agenda-speaker-profile" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Открыть фотографию и сведения: ${attr(speaker)}" data-speaker-name="${attr(speaker)}" data-speaker-credentials="${attr(credentials)}" data-speaker-photo="${attr(photo)}" data-speaker-focus="${attr(focus)}"><img class="agenda-speaker-photo" src="/images/speakers/2026/${photo}" alt="${attr(speaker)}" loading="lazy" decoding="async" style="--speaker-focus:${focus}"></button>`:''}<div class="agenda-speaker-copy"><p class="agenda-speaker"><strong>${speaker}</strong></p>${photo?`<span class="agenda-speaker-hint">Нажмите для подробностей</span>`:''}</div></div></div></article>`;
 const service=(time,label,title,extra='',cls='')=>`<article class="agenda-service ${cls}"><time>${time}</time><div><div><span>${label}</span><h3>${title}</h3>${extra?`<p class="agenda-speaker">${extra}</p>`:''}</div></div></article>`;
 const block=(n,title)=>`<section class="agenda-block" id="agenda-block-${n}"><div class="agenda-block__num">0${n}</div><span class="agenda-block__label">Тематический блок ${n}</span><h3>${title}</h3></section>`;
 agenda.innerHTML=`
 <div class="agenda-overview">
  <div class="agenda-dayline"><div><span class="agenda-dayline__eyebrow">Научно-практическая конференция</span><strong class="agenda-dayline__date">7 октября 2026</strong></div><div class="agenda-dayline__hours"><span>Продолжительность</span><strong>09:30–18:00</strong></div></div>
  <div class="agenda-progress" aria-hidden="true"></div>
  <div class="agenda-facts"><div class="agenda-fact"><small>Программа</small><strong>4 тематических блока</strong></div><div class="agenda-fact"><small>Содержание</small><strong>20 докладов</strong></div><div class="agenda-fact"><small>Площадка</small><strong>Дом Правительства Московской области</strong></div></div>
 </div>
 <nav class="agenda-nav" aria-label="Навигация по тематическим блокам">
  <a href="#agenda-block-1"><b>01</b><span>Национальные приоритеты</span></a>
  <a href="#agenda-block-2"><b>02</b><span>Маршрут пациента</span></a>
  <a href="#agenda-block-3"><b>03</b><span>Здоровое долголетие</span></a>
  <a href="#agenda-block-4"><b>04</b><span>Цифровой контур</span></a>
 </nav>
 <div class="agenda-timeline">
 ${service('09:30–10:00','Сбор участников','Регистрация участников')}
 ${service('10:00–10:15','Открытие','Открытие конференции','<strong>Максим Васильевич Забелин</strong><strong>Иван Михайлович Гольцев</strong>','agenda-opening')}
 ${block(1,'Лабораторная служба в реализации национальных приоритетов')}
 ${talk('10:15–10:30','1','Национальные проекты — как реализовать лабораторный потенциал?','Татьяна Ивановна Долгих','dolgikh.webp','д.м.н., профессор; ПИУВ — филиал РМАНПО Минздрава России; Учебный центр и ЦВКК')}
 ${talk('10:30–10:45','2','Первичная профилактика в кардиологии — основа здорового долголетия. Как правильно оценить лабораторные показатели','Александр Польевич Ройтман','roytman.webp','д.м.н., профессор; РМАНПО Минздрава России; главный внештатный специалист по КЛД Минздрава России по ЦФО')}
 ${talk('10:45–11:00','3','Централизация лабораторной службы Республики Башкортостан: переход от показателей деятельности лабораторий к показателям здоровья населения','Фаниль Салимович Билалов','bilalov.webp','д.м.н., доцент; главный врач ГБУЗ «Республиканский медико-генетический центр»')}
 ${talk('11:00–11:15','4','Масштаб, качество и доступность: чему государственная лабораторная сеть может научиться у частного сектора','Дмитрий Геннадьевич Денисов','denisov.webp','медицинский директор Лабораторной службы «ХЕЛИКС»')}
 ${talk('11:15–11:40','5','Слепые зоны процессов: потери из-за наших привычек и способы их изменения','Мария Георгиевна Ламбакахар','lambakakhar.webp','к.м.н., доцент кафедры КЛД с курсом лабораторной иммунологии ФГАОУ ДПО РМАНПО')}
 ${service('11:40–11:55','Перерыв','Кофе-брейк')}
 ${block(2,'От исследования к решению: рациональная диагностика и маршрут пациента')}
 ${talk('11:55–12:10','6','Посев: клиническая необходимость или рутинный анализ?','Евгений Юрьевич Никитин','nikitin.webp','врач — клинический фармаколог, к.м.н.')}
 ${talk('12:10–12:25','7','От диагностики к гипердиагностике: как получить ответы, а не новые вопросы','Екатерина Игоревна Ким','kim.webp','врач-эндокринолог, к.м.н.; ФГБУ «НМИЦ эндокринологии им. академика И. И. Дедова» Минздрава России')}
 ${talk('12:25–12:40','8','Гепатит C. Подтверждение, внесение в регистр, лечение и контроль устойчивого вирусологического ответа','Павел Олегович Богомолов')}
 ${talk('12:40–12:55','9','Онкология. Текущие перспективные скрининговые направления в регионах','Тигран Гагикович Геворкян','gevorkyan.webp','заместитель директора по реализации федеральных проектов ФГБУ «НМИЦ онкологии им. Н. Н. Блохина» Минздрава России')}
 ${service('12:55–13:40','Перерыв','Обеденный перерыв')}
 ${block(3,'Профилактика и здоровое долголетие: возможности лабораторной диагностики')}
 ${talk('13:40–13:55','10','От риска к контролю: лабораторный маршрут пациента. Диабет, сердечно-сосудистый и почечный риск в системе диспансеризации','Галина Викторовна Волкова','volkova.webp','заведующая отделением медицинской профилактики, врач-терапевт ГБУЗ МО «Одинцовская областная больница»')}
 ${talk('13:55–14:10','11','Модель реализации лабораторной части программы репродуктивной диспансеризации','Антонина Николаевна Зинина','zinina.webp','руководитель направления КЛД ООО «ИнтерЛабСервис»')}
 ${talk('14:10–14:25','12','Биологический возраст: медицинский инструмент или маркетинговая конструкция?','Ольга Николаевна Ткачева','tkacheva.webp','д.м.н., профессор, член-корреспондент РАН; директор Российского геронтологического научно-клинического центра; главный внештатный гериатр Минздрава России')}
 ${talk('14:25–14:40','13','Биомаркеры старения: что рутинно внедрить в лабораторную службу уже сегодня?','Светлана Александровна Бернс','berns.webp','д.м.н., профессор; ФГБУ «НМИЦ терапии и профилактической медицины» Минздрава России')}
 ${service('14:40–15:55','Перерыв','Перерыв')}
 ${block(4,'Единый цифровой и технологический контур')}
 ${talk('15:55–16:10','14','Влияние качества вакуумных систем на результаты лабораторных исследований','Анна Сергеевна Омельянович','omelyanovich.webp','ведущий специалист по продукции направления преаналитики ООО «ОМБ»')}
 ${talk('16:10–16:25','15','Как видеть потерянную пробу до появления жалобы пациента?','Мария Сергеевна Извекова','izvekova.webp','руководитель службы лабораторной диагностики ГБУЗ МО «Истринская клиническая больница»')}
 ${talk('16:25–16:40','16','ЕМИАС–ЛИС без разрывов: от назначения до результата и дальнейшей маршрутизации','Татьяна Сергеевна Сидорова','sidorova.webp','руководитель проектов ООО «Формит»')}
 ${talk('16:40–16:55','17','Антимикробная резистентность: единый региональный контур регистрации и анализа','Марина Витальевна Сухорукова')}
 ${talk('16:55–17:10','18','Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети','Михаил Васильевич Иконников','ikonnikov.webp','генеральный директор АО «Эрба Рус»')}
 ${talk('17:10–17:25','19','Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети','Андрей Викторович Варивода','varivoda.webp','председатель Совета директоров ГК «ДИАКОН»')}
 ${talk('17:25–17:40','20','ИИ в лаборатории: выявление аномальных назначений и интерпретация исследований','Мария · уточняется')}
 ${service('17:40–18:00','Завершение','Подведение итогов и заключительное слово')}
 </div>`;

 document.body.insertAdjacentHTML('beforeend',`<div class="speaker-popover-shade" data-speaker-shade></div><aside class="speaker-popover" data-speaker-popover role="dialog" aria-modal="false" aria-hidden="true" aria-labelledby="speaker-popover-name"><button class="speaker-popover__close" type="button" aria-label="Закрыть">×</button><img class="speaker-popover__photo" src="" alt=""><span class="speaker-popover__copy"><strong id="speaker-popover-name"></strong><small></small></span></aside>`);
 const popover=document.querySelector('[data-speaker-popover]');
 const shade=document.querySelector('[data-speaker-shade]');
 const popoverPhoto=popover.querySelector('.speaker-popover__photo');
 const popoverName=popover.querySelector('.speaker-popover__copy strong');
 const popoverCredentials=popover.querySelector('.speaker-popover__copy small');
 const closeButton=popover.querySelector('.speaker-popover__close');
 const finePointer=()=>matchMedia('(min-width:721px) and (hover:hover) and (pointer:fine)').matches;
 let activeTrigger=null;
 let closeTimer=0;

 const cancelClose=()=>{window.clearTimeout(closeTimer);};
 const positionPopover=trigger=>{
  if(!finePointer()){popover.style.removeProperty('left');popover.style.removeProperty('top');return;}
  const gap=14;
  const edge=16;
  const triggerRect=trigger.getBoundingClientRect();
  const popoverRect=popover.getBoundingClientRect();
  let left=triggerRect.right+gap;
  if(left+popoverRect.width>innerWidth-edge) left=triggerRect.left-popoverRect.width-gap;
  left=Math.max(edge,Math.min(left,innerWidth-popoverRect.width-edge));
  let top=triggerRect.top+(triggerRect.height-popoverRect.height)/2;
  top=Math.max(edge,Math.min(top,innerHeight-popoverRect.height-edge));
  popover.style.left=`${Math.round(left)}px`;
  popover.style.top=`${Math.round(top)}px`;
 };
 const openPopover=trigger=>{
  cancelClose();
  if(activeTrigger&&activeTrigger!==trigger) activeTrigger.setAttribute('aria-expanded','false');
  activeTrigger=trigger;
  popoverPhoto.src=`/images/speakers/2026/${trigger.dataset.speakerPhoto}`;
  popoverPhoto.alt=trigger.dataset.speakerName;
  popoverPhoto.style.setProperty('--speaker-focus',trigger.dataset.speakerFocus||'50% 28%');
  popoverName.textContent=trigger.dataset.speakerName;
  popoverCredentials.textContent=trigger.dataset.speakerCredentials;
  popoverCredentials.hidden=!trigger.dataset.speakerCredentials;
  trigger.setAttribute('aria-expanded','true');
  popover.setAttribute('aria-hidden','false');
  popover.setAttribute('aria-modal',finePointer()?'false':'true');
  popover.classList.add('is-open');
  if(finePointer()){shade.classList.remove('is-open');document.body.classList.remove('speaker-popover-open');requestAnimationFrame(()=>positionPopover(trigger));}
  else{shade.classList.add('is-open');document.body.classList.add('speaker-popover-open');closeButton.focus({preventScroll:true});}
 };
 const closePopover=(returnFocus=false)=>{
  cancelClose();
  const trigger=activeTrigger;
  if(trigger) trigger.setAttribute('aria-expanded','false');
  activeTrigger=null;
  popover.classList.remove('is-open');
  shade.classList.remove('is-open');
  popover.setAttribute('aria-hidden','true');
  document.body.classList.remove('speaker-popover-open');
  if(returnFocus&&trigger) trigger.focus({preventScroll:true});
 };
 const scheduleClose=()=>{cancelClose();closeTimer=window.setTimeout(()=>closePopover(),140);};

 agenda.querySelectorAll('.agenda-speaker-profile').forEach(trigger=>{
  trigger.addEventListener('pointerenter',()=>{if(finePointer()) openPopover(trigger);});
  trigger.addEventListener('pointerleave',()=>{if(finePointer()) scheduleClose();});
  trigger.addEventListener('focus',()=>{if(finePointer()) openPopover(trigger);});
  trigger.addEventListener('blur',()=>{if(finePointer()) scheduleClose();});
  trigger.addEventListener('click',event=>{event.preventDefault();if(finePointer()) openPopover(trigger);else if(activeTrigger===trigger&&popover.classList.contains('is-open')) closePopover();else openPopover(trigger);});
 });
 popover.addEventListener('pointerenter',()=>{if(finePointer()) cancelClose();});
 popover.addEventListener('pointerleave',()=>{if(finePointer()) scheduleClose();});
 closeButton.addEventListener('click',()=>closePopover(true));
 shade.addEventListener('click',()=>closePopover(true));
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&popover.classList.contains('is-open')) closePopover(true);});
 document.addEventListener('pointerdown',event=>{if(finePointer()&&popover.classList.contains('is-open')&&!popover.contains(event.target)&&!event.target.closest('.agenda-speaker-profile')) closePopover();});
 addEventListener('resize',()=>{if(activeTrigger) positionPopover(activeTrigger);},{passive:true});
 addEventListener('scroll',()=>{if(activeTrigger&&finePointer()) positionPopover(activeTrigger);},{passive:true});
});
</script>
HTML;
$html = str_replace('</head>', $style . '</head>', $html);
$html = str_replace('</body>', $script . '</body>', $html);
$html = str_replace('<head>', '<head><meta name="robots" content="noindex,nofollow,noarchive">', $html);
echo $html;
