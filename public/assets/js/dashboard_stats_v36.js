
(function(){
    function getBase(){
        var path = window.location.pathname;
        if (path.indexOf('/public/') === 0 || path === '/public') return '/public';
        return '';
    }

    function renderSimpleBars(containerId, rows, labelField, valueField, title) {
        var el = document.getElementById(containerId);
        if (!el) return;
        rows = rows || [];
        var max = 1;
        rows.forEach(function(r){ max = Math.max(max, parseFloat(r[valueField] || 0)); });

        var html = '<div class="card mb-3"><div class="card-header"><strong>'+title+'</strong></div><div class="card-body">';
        if (!rows.length) {
            html += '<p class="text-muted mb-0">Sem dados para exibir.</p>';
        } else {
            rows.forEach(function(r){
                var val = parseFloat(r[valueField] || 0);
                var pct = Math.round((val / max) * 100);
                html += '<div class="mb-2">';
                html += '<div class="d-flex justify-content-between small"><span>'+ (r[labelField] || '-') +'</span><strong>'+ val.toLocaleString('pt-BR') +'</strong></div>';
                html += '<div class="progress" style="height:10px"><div class="progress-bar" style="width:'+pct+'%"></div></div>';
                html += '</div>';
            });
        }
        html += '</div></div>';
        el.innerHTML = html;
    }

    document.addEventListener('DOMContentLoaded', function(){
        var dashboardArea = document.getElementById('dashboard-stats-v36');
        if (!dashboardArea) return;

        fetch(getBase() + '/api/stats/dashboard')
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data.success) return;
                renderSimpleBars('chart-processes-month', data.processes_by_month, 'month_key', 'total', 'Novos processos por mês');
                renderSimpleBars('chart-received-month', data.received_by_month, 'month_key', 'total', 'Recebido por mês');
                renderSimpleBars('chart-cases-area', data.cases_by_area, 'label', 'total', 'Processos por área');
                renderSimpleBars('chart-cases-comarca', data.cases_by_comarca, 'label', 'total', 'Processos por comarca');
            })
            .catch(function(err){
                dashboardArea.innerHTML = '<div class="alert alert-warning">Não foi possível carregar estatísticas: '+err.message+'</div>';
            });
    });
})();
