<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
$key = (string)($_GET['key'] ?? '');
if (!hash_equals('agenda-2026-rclsmo', $key)) { http_response_code(404); exit('Not found'); }
ob_start();
include __DIR__ . '/../program-closed/index.php';
$html = ob_get_clean();
$extra = <<<'HTML'
<style>
.org-preview-section{position:relative;padding:66px 0 72px;background:linear-gradient(180deg,#06192c 0%,#071b2f 100%);border-top:1px solid rgba(93,211,235,.10);overflow:hidden}
.org-preview-section:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 18% 30%,rgba(79,219,240,.08),transparent 30%),radial-gradient(circle at 82% 60%,rgba(177,92,225,.07),transparent 28%);pointer-events:none}
.org-preview-section .container{position:relative;max-width:1180px}
.org-preview-head{max-width:760px;margin:0 auto 34px;text-align:center}
.org-preview-kicker{display:inline-flex;align-items:center;gap:10px;margin-bottom:12px;color:#56dff2;font-size:11px;font-weight:900;letter-spacing:.16em;text-transform:uppercase}
.org-preview-kicker:before{content:"";width:26px;height:2px;background:#56dff2;border-radius:999px}
.org-preview-head h2{margin:0 0 12px;color:#f4f9fc;font-size:clamp(30px,4vw,48px);line-height:1.05;letter-spacing:-.04em}
.org-preview-head p{max-width:650px;margin:0 auto;color:#9db2c2;font-size:14px;line-height:1.62}
.org-preview-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:900px;margin:0 auto}
.org-preview-card{display:grid;grid-template-columns:120px minmax(0,1fr);gap:22px;align-items:center;min-height:170px;padding:24px 26px;border:1px solid rgba(93,211,235,.15);border-radius:24px;background:linear-gradient(135deg,rgba(18,47,72,.82),rgba(7,25,43,.92));box-shadow:0 22px 54px rgba(0,7,17,.18)}
.org-preview-logo{display:grid;place-items:center;width:112px;height:112px;border-radius:22px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.05)}
.org-preview-logo img{display:block;max-width:88px;max-height:88px;object-fit:contain}
.org-preview-card h3{margin:0 0 8px;color:#eef7fa;font-size:20px;line-height:1.2}
.org-preview-card p{margin:0;color:#93aabc;font-size:13px;line-height:1.55}
.org-preview-card strong{color:#dceaf1;font-weight:750}
.org-preview-legal{max-width:900px;margin:18px auto 0;padding:16px 20px;border:1px solid rgba(93,211,235,.09);border-radius:16px;background:rgba(255,255,255,.018);color:#8199aa;font-size:12px;line-height:1.6;text-align:center}
.org-preview-legal strong{color:#b8cbd6;font-weight:700}
.org-preview-footer-note{max-width:1180px;margin:0 auto;padding:16px 20px;color:#7f95a5;font-size:11px;line-height:1.55;text-align:center;border-top:1px solid rgba(255,255,255,.05)}
@media(max-width:760px){.org-preview-section{padding:48px 0 54px}.org-preview-grid{grid-template-columns:1fr}.org-preview-card{grid-template-columns:92px minmax(0,1fr);min-height:0;padding:20px}.org-preview-logo{width:88px;height:88px}.org-preview-logo img{max-width:70px;max-height:70px}.org-preview-card h3{font-size:18px}.org-preview-head{text-align:left}.org-preview-head p{margin:0}}
@media(max-width:440px){.org-preview-card{grid-template-columns:1fr;text-align:center}.org-preview-logo{margin:0 auto}.org-preview-legal{text-align:left}}
</style>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const program=document.querySelector('.c26-program');
 if(program && !document.querySelector('.org-preview-section')){
  const section=document.createElement('section');
  section.className='org-preview-section';
  section.innerHTML=`<div class="container"><div class="org-preview-head"><span class="org-preview-kicker">Организаторы</span><h2>Форум создаётся внутри системы здравоохранения Московской области</h2><p>Организационная площадка объединяет МОНИКИ и Центр внедрения изменений. Референс-центр формирует содержательную программу форума.</p></div><div class="org-preview-grid"><article class="org-preview-card"><div class="org-preview-logo"><img src="/images/moniki-logo-preview.svg" alt="МОНИКИ"></div><div><h3>МОНИКИ им. М. Ф. Владимирского</h3><p><strong>Организатор форума.</strong><br>На базе института работает Референс-центр лабораторной службы Московской области.</p></div></article><article class="org-preview-card"><div class="org-preview-logo"><img src="/images/cvimz-logo.png" alt="ЦВИОД"></div><div><h3>ГКУ МО «ЦВИОД»</h3><p><strong>Организатор форума.</strong><br>Организационное сопровождение мероприятия.</p></div></article></div><div class="org-preview-legal"><strong>РЦЛСМО</strong> — структурное подразделение ГБУЗ МО МОНИКИ им. М. Ф. Владимирского.</div></div>`;
  program.insertAdjacentElement('afterend',section);
 }
 const footer=document.querySelector('footer,[data-site-shell="footer"]');
 if(footer && !document.querySelector('.org-preview-footer-note')){
  const note=document.createElement('div');note.className='org-preview-footer-note';note.textContent='Референс-центр лабораторной службы Московской области — структурное подразделение ГБУЗ МО МОНИКИ им. М. Ф. Владимирского.';footer.parentNode.insertBefore(note,footer);
 }
});
</script>
HTML;
$html = str_replace('</head>', '<meta name="robots" content="noindex,nofollow,noarchive">' . '</head>', $html);
$html = str_replace('</body>', $extra . '</body>', $html);
echo $html;
