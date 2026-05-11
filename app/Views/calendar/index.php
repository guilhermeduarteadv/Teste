<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Agenda & Calendário</h4>
        <p class="text-muted small mb-0">Audiências, prazos e compromissos</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/cases/create" class="btn btn-sm btn-outline-primary"><i class="fas fa-gavel me-1"></i>Novo Processo</a>
        <a href="/tasks/create" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Nova Tarefa</a>
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
            <span><span style="display:inline-block;width:12px;height:12px;background:#3498db;border-radius:2px;" class="me-1"></span>Prazo Interno</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/pt-br.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'pt-br',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        height: 'auto',
        events: function (info, successCb, failureCb) {
            fetch(`/calendar/events?start=${info.startStr}&end=${info.endStr}`)
                .then(r => r.json())
                .then(successCb)
                .catch(failureCb);
        },
        eventClick: function (info) {
            if (info.event.url) {
                info.jsEvent.preventDefault();
                window.location.href = info.event.url;
            }
        },
        eventDidMount: function (info) {
            const props = info.event.extendedProps;
            const tip = [props.tipo, props.local, props.cnj].filter(Boolean).join(' | ');
            if (tip) info.el.title = tip;
        }
    });
    calendar.render();
});
</script>
