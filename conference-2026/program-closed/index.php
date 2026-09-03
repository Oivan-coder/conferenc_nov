<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
$key = (string)($_GET['key'] ?? '');
if (!hash_equals('agenda-2026-rclsmo', $key)) { http_response_code(404); exit('Not found'); }
$html = file_get_contents(__DIR__ . '/../index.html');
if ($html === false) { http_response_code(500); exit('Preview unavailable'); }
$style = <<<'HTML'
<style>
.c26-program .c26-section-heading p{max-width:760px}
.c26-program-disclosure[open] summary{border-bottom-left-radius:0;border-bottom-right-radius:0}
.c26-agenda{position:relative;padding:18px 0 8px}
.c26-agenda:before{content:"";position:absolute;left:141px;top:34px;bottom:34px;width:1px;background:linear-gradient(180deg,rgba(69,216,241,.10),rgba(69,216,241,.38),rgba(205,72,221,.28),rgba(69,216,241,.08))}
.agenda-dayline{display:grid;grid-template-columns:118px 1fr;gap:24px;align-items:center;margin:2px 0 22px;padding:0 22px;color:#8fa5bb}
.agenda-dayline__time{font-size:12px;font-weight:800;letter-spacing:.12em;color:#4eddf3;text-transform:uppercase}
.agenda-dayline__track{display:flex;align-items:center;gap:10px;font-size:12px;letter-spacing:.05em}
.agenda-dayline__track:before,.agenda-dayline__track:after{content:"";height:1px;flex:1;background:rgba(78,221,243,.16)}
.agenda-block{position:relative;margin:30px 0 12px;padding:24px 26px 22px 166px;border:1px solid rgba(78,221,243,.18);border-radius:22px;background:linear-gradient(135deg,rgba(17,42,65,.95),rgba(8,25,43,.92));overflow:hidden}
.agenda-block:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 88% 12%,rgba(199,66,224,.10),transparent 31%),linear-gradient(90deg,rgba(78,221,243,.035),transparent 40%);pointer-events:none}
.agenda-block__num{position:absolute;left:24px;top:20px;font-size:54px;line-height:1;font-weight:900;letter-spacing:-.05em;color:rgba(78,221,243,.17)}
.agenda-block__label{position:relative;z-index:1;display:block;margin-bottom:7px;font-size:11px;font-weight:900;letter-spacing:.15em;text-transform:uppercase;color:#4eddf3}
.agenda-block h3{position:relative;z-index:1;margin:0;color:#f4f8fb;font-size:clamp(20px,2.3vw,29px);line-height:1.18}
.c26-agenda__item.agenda-talk{position:relative;display:grid;grid-template-columns:118px minmax(0,1fr);gap:24px;margin:0;padding:18px 22px;border:0;border-radius:16px;background:transparent;transition:.18s ease}
.c26-agenda__item.agenda-talk:hover{background:rgba(78,221,243,.035)}
.c26-agenda__item.agenda-talk:after{content:"";position:absolute;left:140px;top:29px;width:5px;height:5px;border-radius:50%;background:#4eddf3;box-shadow:0 0 0 5px #071829,0 0 16px rgba(78,221,243,.45)}
.c26-agenda__item.agenda-talk time{padding-top:2px;color:#4eddf3;font-size:14px;font-weight:800;letter-spacing:.02em}
.agenda-talk__meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:7px}
.agenda-talk__tag{font-size:10px;line-height:1;padding:5px 7px;border:1px solid rgba(78,221,243,.18);border-radius:999px;color:#8edfeb;text-transform:uppercase;letter-spacing:.09em;font-weight:800}
.c26-agenda__item.agenda-talk h3{margin:0 0 8px;color:#f2f7fb;font-size:18px;line-height:1.36;font-weight:730}
.agenda-speaker{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin:0!important;color:#aebed0!important;font-size:14px!important;line-height:1.5!important}
.agenda-speaker strong{color:#dce7f0;font-weight:720}
.agenda-service{display:grid;grid-template-columns:118px minmax(0,1fr);gap:24px;align-items:center;margin:8px 0;padding:13px 22px;border-radius:14px;background:rgba(255,255,255,.025);border:1px solid rgba(255,255,255,.045)}
.agenda-service time{color:#91a8bc;font-size:13px;font-weight:800}
.agenda-service div{display:flex;align-items:center;justify-content:space-between;gap:15px}
.agenda-service span{color:#8ca1b5;font-size:11px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}
.agenda-service h3{margin:0;color:#d8e4ed;font-size:15px}
.agenda-opening{border-color:rgba(78,221,243,.13);background:rgba(78,221,243,.035)}
.agenda-opening h3{font-size:17px;color:#eff8fb}
.agenda-opening .agenda-speaker{margin-top:5px!important}
.agenda-facts{display:flex;gap:10px;flex-wrap:wrap;margin:0 0 22px;padding:0 22px}
.agenda-fact{padding:8px 11px;border-radius:999px;background:rgba(78,221,243,.055);border:1px solid rgba(78,221,243,.12);color:#9fb6c8;font-size:11px;font-weight:750}
.agenda-fact strong{color:#e9f5f9}
@media(max-width:720px){.c26-agenda:before{left:25px}.agenda-dayline{grid-template-columns:1fr;padding:0 16px}.agenda-dayline__track{display:none}.agenda-facts{padding:0 16px}.agenda-block{padding:22px 18px 20px 70px;margin:24px 10px 12px}.agenda-block__num{left:18px;font-size:40px}.c26-agenda__item.agenda-talk,.agenda-service{grid-template-columns:1fr;gap:7px;padding:17px 16px 17px 48px}.c26-agenda__item.agenda-talk:after{left:23px;top:25px}.c26-agenda__item.agenda-talk time,.agenda-service time{font-size:12px}.agenda-service div{display:block}.agenda-service span{display:block;margin-bottom:4px}.c26-agenda__item.agenda-talk h3{font-size:16px}.agenda-speaker{font-size:13px!important}}
</style>
HTML;
$script = <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const root=document.querySelector('.c26-program'); if(!root) return;
 const eyebrow=root.querySelector('.c26-section-heading > span'); if(eyebrow) eyebrow.textContent='Программа конференции';
 const title=root.querySelector('.c26-section-heading h2'); if(title) title.textContent='Один день — четыре содержательных блока';
 const lead=root.querySelector('.c26-section-heading p'); if(lead) lead.textContent='Последовательность выступлений сформирована. Сетка показана в рабочем закрытом контуре до публикации.';
 const sum=root.querySelector('[data-agenda-disclosure] summary em'); if(sum) sum.textContent='4 блока · 20 докладов · 09:30–18:00';
 const label=root.querySelector('[data-agenda-toggle-label]'); if(label) label.textContent='Показать полную программу';
 const agenda=root.querySelector('.c26-agenda'); if(!agenda) return;
 const talk=(time,n,title,speaker)=>`<article class="c26-agenda__item agenda-talk"><time>${time}</time><div><div class="agenda-talk__meta"><span class="agenda-talk__tag">Доклад ${n}</span></div><h3>${title}</h3><p class="agenda-speaker"><strong>${speaker}</strong></p></div></article>`;
 const service=(time,label,title,extra='',cls='')=>`<article class="agenda-service ${cls}"><time>${time}</time><div><div><span>${label}</span><h3>${title}</h3>${extra?`<p class="agenda-speaker">${extra}</p>`:''}</div></div></article>`;
 const block=(n,title)=>`<section class="agenda-block"><div class="agenda-block__num">0${n}</div><span class="agenda-block__label">Тематический блок</span><h3>${title}</h3></section>`;
 agenda.innerHTML=`
 <div class="agenda-dayline"><div class="agenda-dayline__time">7 октября 2026</div><div class="agenda-dayline__track"><span>09:30</span><span>18:00</span></div></div>
 <div class="agenda-facts"><span class="agenda-fact"><strong>4</strong> тематических блока</span><span class="agenda-fact"><strong>20</strong> докладов</span><span class="agenda-fact">Дом Правительства Московской области</span></div>
 ${service('09:30–10:00','Сбор участников','Регистрация участников')}
 ${service('10:00–10:15','Открытие','Открытие конференции','<strong>Максим Васильевич Забелин</strong> · <strong>Иван Михайлович Гольцев</strong>','agenda-opening')}
 ${block(1,'Лабораторная служба в реализации национальных приоритетов')}
 ${talk('10:15–10:30','1','Национальные проекты — как реализовать лабораторный потенциал?','Татьяна Ивановна Долгих')}
 ${talk('10:30–10:45','2','Первичная профилактика в кардиологии — основа здорового долголетия. Как правильно оценить лабораторные показатели','Александр Польевич Ройтман')}
 ${talk('10:45–11:00','3','Централизация лабораторной службы Республики Башкортостан: переход от показателей деятельности лабораторий к показателям здоровья населения','Фаниль Салимович Билалов')}
 ${talk('11:00–11:15','4','Масштаб, качество и доступность: чему государственная лабораторная сеть может научиться у частного сектора','Дмитрий Геннадьевич Денисов')}
 ${talk('11:15–11:40','5','Слепые зоны процессов: потери из-за наших привычек и способы их изменения','Мария Георгиевна Ламбакахар')}
 ${service('11:40–11:55','Перерыв','Кофе-брейк')}
 ${block(2,'От исследования к решению: рациональная диагностика и маршрут пациента')}
 ${talk('11:55–12:10','6','Посев: клиническая необходимость или рутинный анализ?','Евгений Юрьевич Никитин')}
 ${talk('12:10–12:25','7','От диагностики к гипердиагностике: как получить ответы, а не новые вопросы','Екатерина Игоревна Ким')}
 ${talk('12:25–12:40','8','Гепатит C. Подтверждение, внесение в регистр, лечение и контроль устойчивого вирусологического ответа','Павел Олегович Богомолов')}
 ${talk('12:40–12:55','9','Онкология. Текущие перспективные скрининговые направления в регионах','Тигран Гагикович Геворкян')}
 ${service('12:55–13:40','Перерыв','Обеденный перерыв')}
 ${block(3,'Профилактика и здоровое долголетие: возможности лабораторной диагностики')}
 ${talk('13:40–13:55','10','От риска к контролю: лабораторный маршрут пациента. Диабет, сердечно-сосудистый и почечный риск в системе диспансеризации','Галина Викторовна Волкова')}
 ${talk('13:55–14:10','11','Модель реализации лабораторной части программы репродуктивной диспансеризации','Антонина Николаевна Зинина')}
 ${talk('14:10–14:25','12','Биологический возраст: медицинский инструмент или маркетинговая конструкция?','Ольга Николаевна Ткачева')}
 ${talk('14:25–14:40','13','Биомаркеры старения: что рутинно внедрить в лабораторную службу уже сегодня?','Светлана Александровна Бернс')}
 ${service('14:40–15:55','Перерыв','Перерыв')}
 ${block(4,'Единый цифровой и технологический контур')}
 ${talk('15:55–16:10','14','Влияние качества вакуумных систем на результаты лабораторных исследований','Анна Сергеевна Омельянович')}
 ${talk('16:10–16:25','15','Как видеть потерянную пробу до появления жалобы пациента?','Мария Сергеевна Извекова')}
 ${talk('16:25–16:40','16','ЕМИАС–ЛИС без разрывов: от назначения до результата и дальнейшей маршрутизации','Татьяна Сергеевна Сидорова')}
 ${talk('16:40–16:55','17','Антимикробная резистентность: единый региональный контур регистрации и анализа','Марина Витальевна Сухорукова')}
 ${talk('16:55–17:10','18','Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети','Михаил Васильевич Иконников')}
 ${talk('17:10–17:25','19','Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети','Андрей Викторович Варивода')}
 ${talk('17:25–17:40','20','ИИ в лаборатории: выявление аномальных назначений и интерпретация исследований','Мария · уточняется')}
 ${service('17:40–18:00','Завершение','Подведение итогов и заключительное слово')}`;
});
</script>
HTML;
$html = str_replace('</head>', $style . '</head>', $html);
$html = str_replace('</body>', $script . '</body>', $html);
$html = str_replace('<head>', '<head><meta name="robots" content="noindex,nofollow,noarchive">', $html);
echo $html;
