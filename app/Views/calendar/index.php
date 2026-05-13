<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Agenda & Calendário</h4>
        <p class="text-muted small mb-0">Tarefas, audiências, prazos processuais e vencimentos financeiros</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= ($basePath ?? '') ?>/calendar/debug" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-bug me-1"></i>Diagnóstico</a>
        <a href="<?= ($basePath ?? '') ?>/cases/create" class="btn btn-sm btn-outline-primary"><i class="fas fa-gavel me-1"></i>Novo Processo</a>
        <a href="<?= ($basePath ?? '') ?>/tasks/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Tarefa</a>
    </div>
</div>

<!-- Legend -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 small">
            <span><span style="display:inline-block;width:12px;height:12px;background:#3788d8;border-radius:2px;" class="me-1"></span>Conciliação</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#e74c3c;border-radius:2px;" class="me-1"></span>Instrução / Prazo Fatal</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#8e44ad;border-radius:2px;" class="me-1"></span>Julgamento</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#27ae60;border-radius:2px;" class="me-1"></span>Reunião</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#f39c12;border-radius:2px;" class="me-1"></span>Prazo Processual</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#1a56db;border-radius:2px;" class="me-1"></span>Tarefa</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#059669;border-radius:2px;" class="me-1"></span>Financeiro</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar-alert" class="alert alert-warning d-none"></div>
        <div id="calendar"></div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/pt-br.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const BASE_PATH = <?= json_encode($basePath ?? '') ?>;
    const CURRENT_BASE = window.location.pathname.replace(/\/calendar\/?$/, '');
    const ROOT = BASE_PATH || CURRENT_BASE || '';
    const calendarEl = document.getElementById('calendar');
    const alertBox = document.getElementById('calendar-alert');

    function showCalendarAlert(message, type = 'warning') {
        if (!alertBox) return;
        alertBox.className = 'alert alert-' + type;
        alertBox.textContent = message;
    }

    function hideCalendarAlert() {
        if (!alertBox) return;
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    function nearestInitialDate(events) {
        if (!Array.isArray(events) || events.length === 0) {
            return new Date().toISOString().slice(0, 10);
        }
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const parsed = events
            .map(e => String(e.start || '').slice(0, 10))
            .filter(Boolean)
            .sort();
        const upcoming = parsed.find(d => new Date(d + 'T00:00:00') >= today);
        return upcoming || parsed[parsed.length - 1] || new Date().toISOString().slice(0, 10);
    }

    async function loadEvents() {
        const url = `${ROOT}/calendar/events?all=1&_=${Date.now()}`;
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text || '[]');
        } catch (e) {
            console.error('Resposta inválida do calendário:', text);
            throw new Error('O servidor retornou resposta inválida para o calendário. Abra /calendar/debug para ver o diagnóstico.');
        }
        if (!response.ok) {
            throw new Error(data.message || ('HTTP ' + response.status));
        }
        if (!Array.isArray(data)) {
            throw new Error('Formato inválido: o calendário esperava uma lista de eventos.');
        }
        return data;
    }

    loadEvents()
        .then(events => {
            console.log('Eventos carregados no calendário:', events);
            const calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                initialDate: nearestInitialDate(events),
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                height: 'auto',
                events: events,
                noEventsContent: 'Nenhum compromisso para exibir nesta visão.',
                eventClick: function (info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                    }
                },
                eventDidMount: function (info) {
                    const props = info.event.extendedProps || {};
                    const tip = [props.categoria, props.tipo, props.local, props.cnj].filter(Boolean).join(' | ');
                    if (tip) info.el.title = tip;
                }
            });
            calendar.render();
            if (events.length === 0) {
                showCalendarAlert('Nenhum evento foi encontrado pelo endpoint do calendário. Abra o Diagnóstico para conferir se as tarefas possuem prazo gravado no banco: ' + (ROOT + '/calendar/debug'), 'info');
            } else {
                hideCalendarAlert();
            }
        })
        .catch(err => {
            console.error('Erro ao carregar eventos do calendário:', err);
            showCalendarAlert(err.message, 'danger');
        });
});
</script>
