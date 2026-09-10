<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/moderator.php');

function qaModeratorProgram(): array
{
    return [
        ['number' => 1, 'time' => '10:15–10:30', 'speaker' => 'Татьяна Ивановна Долгих', 'title' => 'Национальные проекты — как реализовать лабораторный потенциал?'],
        ['number' => 2, 'time' => '10:30–10:45', 'speaker' => 'Александр Польевич Ройтман', 'title' => 'Первичная профилактика в кардиологии — основа здорового долголетия. Как правильно оценить лабораторные показатели'],
        ['number' => 3, 'time' => '10:45–11:00', 'speaker' => 'Фаниль Салимович Билалов', 'title' => 'Централизация лабораторной службы Республики Башкортостан: переход от показателей деятельности лабораторий к показателям здоровья населения'],
        ['number' => 4, 'time' => '11:00–11:15', 'speaker' => 'Дмитрий Геннадьевич Денисов', 'title' => 'Масштаб, качество и доступность: чему государственная лабораторная сеть может научиться у частного сектора'],
        ['number' => 5, 'time' => '11:15–11:40', 'speaker' => 'Мария Георгиевна Ламбакахар', 'title' => 'Слепые зоны процессов: потери из-за наших привычек и способы их изменения'],
        ['number' => 6, 'time' => '11:55–12:10', 'speaker' => 'Евгений Юрьевич Никитин', 'title' => 'Посев: клиническая необходимость или рутинный анализ?'],
        ['number' => 7, 'time' => '12:10–12:25', 'speaker' => 'Екатерина Игоревна Ким', 'title' => 'От диагностики к гипердиагностике: как получить ответы, а не новые вопросы'],
        ['number' => 8, 'time' => '12:25–12:40', 'speaker' => 'Павел Олегович Богомолов', 'title' => 'Гепатит C. Подтверждение, внесение в регистр, лечение и контроль устойчивого вирусологического ответа'],
        ['number' => 9, 'time' => '12:40–12:55', 'speaker' => 'Тигран Гагикович Геворкян', 'title' => 'Текущие и перспективные направления скрининга онкологических заболеваний в регионах'],
        ['number' => 10, 'time' => '13:40–13:55', 'speaker' => 'Галина Викторовна Волкова', 'title' => 'От риска к контролю: лабораторный маршрут пациента. Диабет, сердечно-сосудистый и почечный риск в системе диспансеризации'],
        ['number' => 11, 'time' => '13:55–14:10', 'speaker' => 'Антонина Николаевна Зинина', 'title' => 'Модель реализации лабораторной части программы репродуктивной диспансеризации'],
        ['number' => 12, 'time' => '14:10–14:25', 'speaker' => 'Ольга Николаевна Ткачева', 'title' => 'Биологический возраст: медицинский инструмент или маркетинговая конструкция?'],
        ['number' => 13, 'time' => '14:25–14:40', 'speaker' => 'Светлана Александровна Бернс', 'title' => 'Биомаркеры старения: что рутинно внедрить в лабораторную службу уже сегодня?'],
        ['number' => 14, 'time' => '15:55–16:10', 'speaker' => 'Анна Сергеевна Омельянович', 'title' => 'Влияние качества вакуумных систем на результаты лабораторных исследований'],
        ['number' => 15, 'time' => '16:10–16:25', 'speaker' => 'Мария Сергеевна Извекова', 'title' => 'Как видеть потерянную пробу до появления жалобы пациента?'],
        ['number' => 16, 'time' => '16:25–16:40', 'speaker' => 'Татьяна Сергеевна Сидорова', 'title' => 'ЕМИАС–ЛИС без разрывов: от назначения до результата и дальнейшей маршрутизации'],
        ['number' => 17, 'time' => '16:40–16:55', 'speaker' => 'Марина Витальевна Сухорукова', 'title' => 'Антимикробная резистентность: единый региональный контур регистрации и анализа'],
        ['number' => 18, 'time' => '16:55–17:10', 'speaker' => 'Михаил Васильевич Иконников', 'title' => 'Развитие производства медицинских изделий в России: текущие тенденции и перспективы'],
        ['number' => 19, 'time' => '17:10–17:25', 'speaker' => 'Андрей Викторович Варивода', 'title' => 'Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети'],
        ['number' => 20, 'time' => '17:25–17:40', 'speaker' => 'Мария Аркадьевна Мальцева', 'title' => 'ИИ в лаборатории: выявление аномальных назначений и интерпретация исследований'],
    ];
}

function qaModeratorNameKey(string $value): string
{
    $value = str_replace('ё', 'е', mb_strtolower(trim($value)));
    $parts = preg_split('/\s+/u', $value) ?: [];
    sort($parts, SORT_STRING);
    return implode(' ', $parts);
}

function qaModeratorProgramIndex(?array $session, array $program): ?int
{
    if (!$session) return null;
    $speakerKey = qaModeratorNameKey((string)($session['speaker_name'] ?? ''));
    $title = trim((string)($session['title'] ?? ''));
    foreach ($program as $index => $item) {
        if ($speakerKey !== '' && $speakerKey === qaModeratorNameKey((string)$item['speaker'])) return $index;
        if ($title !== '' && $title === (string)$item['title']) return $index;
    }
    return null;
}

function qaModeratorSetSession(PDO $pdo, array $item): void
{
    $pdo->beginTransaction();
    try {
        $clear = $pdo->prepare('UPDATE conference_sessions SET is_current = 0 WHERE event_id = :event_id AND is_current = 1');
        $clear->execute([':event_id' => QA_EVENT_ID]);
        $insert = $pdo->prepare('INSERT INTO conference_sessions (event_id, title, speaker_name, is_current) VALUES (:event_id, :title, :speaker_name, 1)');
        $insert->execute([
            ':event_id' => QA_EVENT_ID,
            ':title' => (string)$item['title'],
            ':speaker_name' => (string)$item['speaker'],
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

$isStateRequest = (string)($_GET['state'] ?? '') === '1';
$program = qaModeratorProgram();
$error = '';
$currentSession = null;
$currentProgramIndex = null;
$questions = [];
$counts = ['new' => 0, 'on_air' => 0, 'answered' => 0, 'hidden' => 0];

if ($isStateRequest && !$authorized) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($authorized) {
    try {
        $pdo = qa_pdo();
        qa_ensure_schema($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            qa_verify_csrf();
            $action = (string)$_POST['action'];

            if ($action === 'set_session') {
                $indexRaw = $_POST['program_index'] ?? null;
                $index = filter_var($indexRaw, FILTER_VALIDATE_INT);
                if ($index === false || !isset($program[$index])) {
                    throw new InvalidArgumentException('Выберите доклад из программы.');
                }
                qaModeratorSetSession($pdo, $program[$index]);
                header('Location: /qa/moderator.php');
                exit;
            }

            if ($action === 'previous_session' || $action === 'next_session') {
                $session = qa_current_session($pdo);
                $currentIndex = qaModeratorProgramIndex($session, $program);
                if ($currentIndex === null) {
                    $targetIndex = 0;
                } else {
                    $targetIndex = $currentIndex + ($action === 'next_session' ? 1 : -1);
                    $targetIndex = max(0, min(count($program) - 1, $targetIndex));
                }
                qaModeratorSetSession($pdo, $program[$targetIndex]);
                header('Location: /qa/moderator.php');
                exit;
            }

            if ($action === 'clear_session') {
                $stmt = $pdo->prepare('UPDATE conference_sessions SET is_current = 0 WHERE event_id = :event_id AND is_current = 1');
                $stmt->execute([':event_id' => QA_EVENT_ID]);
                header('Location: /qa/moderator.php');
                exit;
            }

            if (in_array($action, ['air', 'answered', 'hide', 'restore'], true)) {
                $id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
                if (!$id) throw new InvalidArgumentException('Некорректный номер вопроса.');

                if ($action === 'air') {
                    $pdo->beginTransaction();
                    $reset = $pdo->prepare("UPDATE conference_messages SET status = 'new' WHERE event_id = :event_id AND message_type = 'question' AND status = 'on_air'");
                    $reset->execute([':event_id' => QA_EVENT_ID]);
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'on_air', approved_at = COALESCE(approved_at, NOW()), on_air_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                    $pdo->commit();
                } elseif ($action === 'answered') {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'answered', answered_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                } elseif ($action === 'hide') {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'hidden', hidden_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                } else {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'new', hidden_at = NULL WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                }

                header('Location: /qa/moderator.php');
                exit;
            }
        }

        $currentSession = qa_current_session($pdo);
        $currentProgramIndex = qaModeratorProgramIndex($currentSession, $program);

        $countStmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM conference_messages WHERE event_id = :event_id AND message_type = 'question' GROUP BY status");
        $countStmt->execute([':event_id' => QA_EVENT_ID]);
        foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (array_key_exists($row['status'], $counts)) $counts[$row['status']] = (int)$row['cnt'];
        }

        $stmt = $pdo->prepare(
            "SELECT
                m.id,
                m.participant_name,
                m.organization,
                m.message_text AS question_text,
                m.status,
                m.created_at,
                m.reply_to_id,
                s.title AS session_title,
                s.speaker_name,
                (SELECT COUNT(*) FROM conference_message_votes v WHERE v.message_id = m.id) AS votes
             FROM conference_messages m
             LEFT JOIN conference_sessions s ON s.id = m.session_id
             WHERE m.event_id = :event_id
               AND m.message_type = 'question'
             ORDER BY
                CASE m.status WHEN 'on_air' THEN 0 WHEN 'new' THEN 1 WHEN 'answered' THEN 2 ELSE 3 END,
                votes DESC,
                m.created_at DESC
             LIMIT 200"
        );
        $stmt->execute([':event_id' => QA_EVENT_ID]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (InvalidArgumentException $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'Ошибка работы вопросов. Проверьте подключение к БД.';
    }
}

if ($isStateRequest) {
    header('Content-Type: application/json; charset=utf-8');
    if ($error !== '') {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    echo json_encode([
        'ok' => true,
        'session' => $currentSession,
        'current_program_index' => $currentProgramIndex,
        'counts' => $counts,
        'questions' => $questions,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$statusLabels = ['new' => 'Новый', 'on_air' => 'У спикера', 'answered' => 'Отвечен', 'hidden' => 'Скрыт'];
$csrf = qa_csrf_token();
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Модератор — вопросы спикеру</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
<style>
.program-select{margin-top:7px}.program-help{margin:8px 0 0;color:#71827a;font-size:12.5px;line-height:1.45}.program-nav{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:9px}.btn:disabled{opacity:.45;cursor:not-allowed}.question-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0 4px}.question-tab{border:1px solid #d5e0da;background:#f6f8f7;color:#365749;border-radius:999px;padding:8px 11px;font:inherit;font-size:13px;font-weight:800;cursor:pointer}.question-tab.active{background:#214f3b;color:#fff;border-color:#214f3b}.question-tab b{font-size:12px;opacity:.82;margin-left:4px}.question.status-new{border-color:#e1bd70;background:#fffaf0;box-shadow:inset 4px 0 0 #c99223}.question.status-hidden{opacity:.72}.queue-head-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}.poll-pill.ok{background:#e6f5eb;color:#23603e}.poll-pill.error{background:#fff0e8;color:#8a4d2f}.sound-btn{border:1px solid #d5e0da;background:#fff;color:#214f3b;border-radius:10px;padding:9px 12px;font:inherit;font-weight:700;cursor:pointer}.sound-btn.off{color:#708078;background:#f4f6f5}.question-list.flash{animation:qaFlash .65s ease}@keyframes qaFlash{0%{box-shadow:0 0 0 0 rgba(201,146,35,.32)}60%{box-shadow:0 0 0 8px rgba(201,146,35,.08)}100%{box-shadow:0 0 0 0 rgba(201,146,35,0)}}@media(max-width:760px){.program-nav{grid-template-columns:1fr}.queue-head-right{justify-content:flex-start}.question-tabs{gap:6px}.question-tab{padding:7px 9px;font-size:12px}}
</style>
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Вопросы · модератор') ?>
<?php else: ?>
<div class="wrap">
<div class="topbar">
    <div class="nav"><a href="/discussion/poster.php" target="_blank">QR для зала ↗</a><a href="/qa/speaker.php" target="_blank">Экран спикера ↗</a><button type="button" class="sound-btn" id="soundToggle" aria-pressed="true">🔔 Звук: вкл</button></div>
    <form method="post"><button class="btn ghost" name="qa_logout" value="1">Выйти</button></form>
</div>
<section class="hero"><div class="brand" style="color:#b9d2c7">Панель модератора</div><h1>Вопросы спикеру</h1><p>Сюда попадают вопросы и от онлайн-участников, и от участников в зале. Для зала используется общий QR-код; онлайн задаёт вопрос прямо на странице трансляции.</p></section>
<?php if ($error): ?><div class="notice"><?= qa_h($error) ?></div><?php endif; ?>
<div class="stats" style="margin-bottom:16px">
    <div class="stat"><span class="brand">Новые</span><b id="statNew"><?= $counts['new'] ?></b></div>
    <div class="stat"><span class="brand">У спикера</span><b id="statAir"><?= $counts['on_air'] ?></b></div>
    <div class="stat"><span class="brand">Отвечены</span><b id="statAnswered"><?= $counts['answered'] ?></b></div>
    <div class="stat"><span class="brand">Скрыты</span><b id="statHidden"><?= $counts['hidden'] ?></b></div>
</div>
<div class="grid">
<section class="card two">
<div class="brand">Текущий доклад</div>
<div class="session" style="margin-top:8px">
    <div>
        <strong id="currentSpeaker"><?= $currentSession ? qa_h($currentSession['speaker_name']) : 'Не выбран' ?></strong>
        <p class="muted" id="currentTitle" style="margin:5px 0 0"><?= $currentSession ? qa_h($currentSession['title']) : 'Выберите текущий доклад — новые вопросы будут автоматически привязаны к нужному спикеру.' ?></p>
    </div>
    <span class="pill live" id="currentBadge"<?= $currentSession ? '' : ' hidden' ?>>● текущий</span>
</div>
<form method="post" id="clearSessionForm" style="margin-top:14px"<?= $currentSession ? '' : ' hidden' ?>><input type="hidden" name="csrf" value="<?= qa_h($csrf) ?>"><button class="btn ghost" name="action" value="clear_session">Завершить текущий доклад</button></form>
</section>
<section class="card two">
<h3>Кто сейчас выступает</h3>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= qa_h($csrf) ?>">
<div class="field"><label for="programIndex">Доклад программы</label>
<select id="programIndex" name="program_index" class="program-select" required>
<?php foreach ($program as $index => $item): ?>
<option value="<?= $index ?>"<?= $currentProgramIndex === $index ? ' selected' : '' ?>><?= qa_h(sprintf('%02d · %s · %s — %s', $item['number'], $item['time'], $item['speaker'], $item['title'])) ?></option>
<?php endforeach; ?>
</select>
<p class="program-help">Все 20 докладов уже внесены. Вручную ФИО и тему вводить не нужно.</p></div>
<div style="margin-top:12px"><button class="btn" name="action" value="set_session">Сделать текущим</button></div>
</form>
<form method="post" class="program-nav">
<input type="hidden" name="csrf" value="<?= qa_h($csrf) ?>">
<button class="btn ghost" id="prevSession" name="action" value="previous_session"<?= $currentProgramIndex === 0 ? ' disabled' : '' ?>>← Предыдущий</button>
<button class="btn secondary" id="nextSession" name="action" value="next_session"<?= $currentProgramIndex === count($program) - 1 ? ' disabled' : '' ?>>Следующий →</button>
</form>
</section>
<section class="card question-list" id="questionCard">
<div class="session">
    <div><div class="brand">Общая очередь</div><h2 style="margin:6px 0 0">Вопросы из зала и онлайн</h2></div>
    <div class="queue-head-right"><span class="pill poll-pill ok" id="pollStatus">● автообновление 2 сек</span></div>
</div>
<div class="question-tabs" id="questionTabs" role="tablist" aria-label="Фильтр вопросов">
    <button type="button" class="question-tab active" data-filter="all">Все <b id="tabAll"><?= array_sum($counts) ?></b></button>
    <button type="button" class="question-tab" data-filter="new">Новые <b id="tabNew"><?= $counts['new'] ?></b></button>
    <button type="button" class="question-tab" data-filter="on_air">У спикера <b id="tabAir"><?= $counts['on_air'] ?></b></button>
    <button type="button" class="question-tab" data-filter="answered">Отвечены <b id="tabAnswered"><?= $counts['answered'] ?></b></button>
    <button type="button" class="question-tab" data-filter="hidden">Скрыты <b id="tabHidden"><?= $counts['hidden'] ?></b></button>
</div>
<div id="questionList">
<?php if (!$questions): ?><p class="muted" id="emptyQuestions">Пока вопросов нет. Они появятся здесь автоматически, когда участник выберет «Вопрос спикеру».</p><?php endif; ?>
<?php foreach ($questions as $q): ?>
<div class="question <?= $q['status'] === 'on_air' ? 'onair ' : '' ?>status-<?= qa_h($q['status']) ?>" data-status="<?= qa_h($q['status']) ?>" data-question-id="<?= (int)$q['id'] ?>">
<div class="question-head"><div><span class="pill <?= qa_h($q['status']) ?>"><?= qa_h($statusLabels[$q['status']] ?? $q['status']) ?></span><?php if ($q['speaker_name']): ?> <span class="pill"><?= qa_h($q['speaker_name']) ?></span><?php endif; ?><?php if ((int)$q['votes'] > 0): ?> <span class="pill">👍 <?= (int)$q['votes'] ?></span><?php endif; ?></div><strong>#<?= (int)$q['id'] ?></strong></div>
<div class="question-text"><?= qa_h($q['question_text']) ?></div>
<div class="question-meta"><?= qa_h($q['participant_name']) ?> · <?= qa_h($q['organization']) ?> · <?= qa_h($q['created_at']) ?><?php if ($q['session_title']): ?><br><?= qa_h($q['session_title']) ?><?php endif; ?></div>
<form method="post" class="actions" style="margin-top:13px">
<input type="hidden" name="csrf" value="<?= qa_h($csrf) ?>"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
<?php if ($q['status'] === 'new'): ?><button class="btn" name="action" value="air">Показать спикеру</button><?php endif; ?>
<?php if ($q['status'] === 'on_air'): ?><button class="btn" name="action" value="answered">✓ Отвечен</button><?php endif; ?>
<?php if ($q['status'] !== 'hidden' && $q['status'] !== 'answered'): ?><button class="btn ghost" name="action" value="hide">Скрыть</button><?php elseif ($q['status'] === 'hidden'): ?><button class="btn ghost" name="action" value="restore">Вернуть</button><?php endif; ?>
</form>
</div>
<?php endforeach; ?>
</div>
</section>
</div>
</div>
<script>
const csrf=<?= json_encode($csrf, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const programCount=<?= count($program) ?>;
const statusLabels={new:'Новый',on_air:'У спикера',answered:'Отвечен',hidden:'Скрыт'};
const questionList=document.getElementById('questionList');
const questionCard=document.getElementById('questionCard');
const pollStatus=document.getElementById('pollStatus');
const programIndex=document.getElementById('programIndex');
const currentSpeaker=document.getElementById('currentSpeaker');
const currentTitle=document.getElementById('currentTitle');
const currentBadge=document.getElementById('currentBadge');
const clearSessionForm=document.getElementById('clearSessionForm');
const prevSession=document.getElementById('prevSession');
const nextSession=document.getElementById('nextSession');
const soundToggle=document.getElementById('soundToggle');
let qaBusy=false;
let polling=false;
let lastSignature='';
let activeFilter=localStorage.getItem('qaModeratorFilter')||'all';
let soundEnabled=localStorage.getItem('qaModeratorSound')!=='0';
let audioContext=null;
const knownQuestionIds=new Set(Array.from(document.querySelectorAll('[data-question-id]')).map(el=>el.dataset.questionId));

document.addEventListener('submit',()=>{qaBusy=true});

function escapeHtml(value){return String(value??'').replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));}
function intValue(value){const n=Number.parseInt(value,10);return Number.isFinite(n)?n:0;}

function updateSoundButton(){soundToggle.textContent=soundEnabled?'🔔 Звук: вкл':'🔕 Звук: выкл';soundToggle.classList.toggle('off',!soundEnabled);soundToggle.setAttribute('aria-pressed',soundEnabled?'true':'false');}
function unlockAudio(){if(!soundEnabled)return;try{if(!audioContext)audioContext=new (window.AudioContext||window.webkitAudioContext)();if(audioContext.state==='suspended')audioContext.resume();}catch(e){}}
function beep(){if(!soundEnabled)return;unlockAudio();if(!audioContext||audioContext.state!=='running')return;const osc=audioContext.createOscillator();const gain=audioContext.createGain();osc.type='sine';osc.frequency.value=880;gain.gain.setValueAtTime(.0001,audioContext.currentTime);gain.gain.exponentialRampToValueAtTime(.08,audioContext.currentTime+.015);gain.gain.exponentialRampToValueAtTime(.0001,audioContext.currentTime+.19);osc.connect(gain);gain.connect(audioContext.destination);osc.start();osc.stop(audioContext.currentTime+.2);}
updateSoundButton();
document.addEventListener('pointerdown',unlockAudio,{once:true});
soundToggle.addEventListener('click',()=>{soundEnabled=!soundEnabled;localStorage.setItem('qaModeratorSound',soundEnabled?'1':'0');updateSoundButton();if(soundEnabled){unlockAudio();beep();}});

function actionButtons(q){
    const id=intValue(q.id);
    let buttons='';
    if(q.status==='new')buttons+='<button class="btn" name="action" value="air">Показать спикеру</button>';
    if(q.status==='on_air')buttons+='<button class="btn" name="action" value="answered">✓ Отвечен</button>';
    if(q.status!=='hidden'&&q.status!=='answered')buttons+='<button class="btn ghost" name="action" value="hide">Скрыть</button>';
    else if(q.status==='hidden')buttons+='<button class="btn ghost" name="action" value="restore">Вернуть</button>';
    return '<form method="post" class="actions" style="margin-top:13px"><input type="hidden" name="csrf" value="'+escapeHtml(csrf)+'"><input type="hidden" name="question_id" value="'+id+'">'+buttons+'</form>';
}

function questionMarkup(q){
    const id=intValue(q.id),votes=intValue(q.votes),status=String(q.status||'new');
    const speaker=q.speaker_name?' <span class="pill">'+escapeHtml(q.speaker_name)+'</span>':'';
    const votesBadge=votes>0?' <span class="pill">👍 '+votes+'</span>':'';
    const title=q.session_title?'<br>'+escapeHtml(q.session_title):'';
    return '<div class="question '+(status==='on_air'?'onair ':'')+'status-'+escapeHtml(status)+'" data-status="'+escapeHtml(status)+'" data-question-id="'+id+'">'+
        '<div class="question-head"><div><span class="pill '+escapeHtml(status)+'">'+escapeHtml(statusLabels[status]||status)+'</span>'+speaker+votesBadge+'</div><strong>#'+id+'</strong></div>'+
        '<div class="question-text">'+escapeHtml(q.question_text)+'</div>'+
        '<div class="question-meta">'+escapeHtml(q.participant_name)+' · '+escapeHtml(q.organization)+' · '+escapeHtml(q.created_at)+title+'</div>'+
        actionButtons(q)+'</div>';
}

function applyFilter(){
    document.querySelectorAll('.question-tab').forEach(btn=>btn.classList.toggle('active',btn.dataset.filter===activeFilter));
    questionList.querySelectorAll('.question').forEach(el=>{el.hidden=activeFilter!=='all'&&el.dataset.status!==activeFilter;});
    localStorage.setItem('qaModeratorFilter',activeFilter);
}
document.getElementById('questionTabs').addEventListener('click',e=>{const btn=e.target.closest('.question-tab');if(!btn)return;activeFilter=btn.dataset.filter||'all';applyFilter();});
applyFilter();

function updateCounts(counts){
    const n=intValue(counts.new),a=intValue(counts.on_air),d=intValue(counts.answered),h=intValue(counts.hidden);
    document.getElementById('statNew').textContent=n;document.getElementById('statAir').textContent=a;document.getElementById('statAnswered').textContent=d;document.getElementById('statHidden').textContent=h;
    document.getElementById('tabAll').textContent=n+a+d+h;document.getElementById('tabNew').textContent=n;document.getElementById('tabAir').textContent=a;document.getElementById('tabAnswered').textContent=d;document.getElementById('tabHidden').textContent=h;
}

function updateSession(session,index){
    if(session){
        currentSpeaker.textContent=session.speaker_name||'Текущий доклад';
        currentTitle.textContent=session.title||'';
        currentBadge.hidden=false;clearSessionForm.hidden=false;
    }else{
        currentSpeaker.textContent='Не выбран';
        currentTitle.textContent='Выберите текущий доклад — новые вопросы будут автоматически привязаны к нужному спикеру.';
        currentBadge.hidden=true;clearSessionForm.hidden=true;
    }
    const idx=Number.isInteger(index)?index:null;
    if(idx!==null&&document.activeElement!==programIndex)programIndex.value=String(idx);
    prevSession.disabled=idx===0;
    nextSession.disabled=idx===programCount-1;
}

function renderState(data){
    updateCounts(data.counts||{});
    updateSession(data.session,Number.isInteger(data.current_program_index)?data.current_program_index:null);
    const signature=JSON.stringify({session:data.session,current_program_index:data.current_program_index,counts:data.counts,questions:data.questions});
    if(signature===lastSignature)return;
    const questions=Array.isArray(data.questions)?data.questions:[];
    const added=questions.filter(q=>!knownQuestionIds.has(String(q.id))&&q.status==='new');
    questions.forEach(q=>knownQuestionIds.add(String(q.id)));
    questionList.innerHTML=questions.length?questions.map(questionMarkup).join(''):'<p class="muted" id="emptyQuestions">Пока вопросов нет. Они появятся здесь автоматически, когда участник выберет «Вопрос спикеру».</p>';
    applyFilter();
    if(added.length){beep();questionCard.classList.remove('flash');void questionCard.offsetWidth;questionCard.classList.add('flash');}
    lastSignature=signature;
}

async function poll(){
    if(polling||qaBusy||document.hidden)return;
    polling=true;
    try{
        const r=await fetch('/qa/moderator.php?state=1',{cache:'no-store',credentials:'same-origin',headers:{Accept:'application/json'}});
        if(r.status===401){location.reload();return;}
        if(!r.ok)throw new Error('http');
        const data=await r.json();if(!data.ok)throw new Error('api');
        renderState(data);
        pollStatus.textContent='● автообновление 2 сек';pollStatus.classList.add('ok');pollStatus.classList.remove('error');
    }catch(e){pollStatus.textContent='● нет связи — повторяем';pollStatus.classList.remove('ok');pollStatus.classList.add('error');}
    finally{polling=false;}
}

lastSignature=JSON.stringify({session:<?= json_encode($currentSession, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,current_program_index:<?= json_encode($currentProgramIndex) ?>,counts:<?= json_encode($counts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,questions:<?= json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>});
poll();setInterval(poll,2000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll();});
</script>
<?php endif; ?>
</body>
</html>
