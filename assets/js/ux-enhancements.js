/**
 * SoleiPharma UX Enhancements v2.2
 * Módulos: Toast, GlobalSearch, Ripple, SidebarActive, KeyboardShortcuts,
 *          KpiAnimations, DarkMode, TopBar, BackToTop, Clock, KpiCounters, TableEnhancements
 */
(function () {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // 1. TOAST
    // ═══════════════════════════════════════════════════════════════════════
    var TOAST_ICONS  = { success:'fas fa-check-circle', error:'fas fa-times-circle', warning:'fas fa-exclamation-triangle', info:'fas fa-info-circle' };
    var TOAST_TITLES = { success:'Éxito', error:'Error', warning:'Advertencia', info:'Información' };

    window.Toast = {
        show: function (opts) {
            var container = document.getElementById('toast-container');
            if (!container) return;
            var type     = opts.type || 'info';
            var title    = opts.title || TOAST_TITLES[type] || 'Aviso';
            var message  = opts.message || '';
            var duration = opts.duration || 4000;
            var toast = document.createElement('div');
            toast.className = 'solei-toast toast-' + type;
            toast.setAttribute('role', 'alert');
            toast.innerHTML =
                '<span class="toast-icon"><i class="' + (TOAST_ICONS[type] || 'fas fa-bell') + '"></i></span>' +
                '<div class="toast-body">' +
                    '<div class="toast-title">' + _esc(title) + '</div>' +
                    (message ? '<div class="toast-msg">' + _esc(message) + '</div>' : '') +
                '</div>' +
                '<button class="toast-close" aria-label="Cerrar">&times;</button>' +
                '<div class="toast-progress" style="animation-duration:' + duration + 'ms;"></div>';
            toast.querySelector('.toast-close').addEventListener('click', function () { _dismissToast(toast); });
            container.appendChild(toast);
            toast._timer = setTimeout(function () { _dismissToast(toast); }, duration);
        }
    };

    function _dismissToast(t) {
        if (t._timer) clearTimeout(t._timer);
        t.classList.add('toast-out');
        t.addEventListener('animationend', function () { t.remove(); }, { once: true });
    }

    function _esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 2. NAV MAP
    // ═══════════════════════════════════════════════════════════════════════
    var B = window.SOLEI_APP_BASE || '';
    var NAV_MAP = [
        { label:'Dashboard',               icon:'fa-tachometer-alt',    url:B+'/admin/index',           keywords:['inicio','panel','dashboard'],       iconClass:'' },
        { label:'Reporte de Ventas',        icon:'fa-chart-line',        url:B+'/admin/salesReport',     keywords:['ventas','reporte'],                  iconClass:'' },
        { label:'Inventario',               icon:'fa-warehouse',         url:B+'/admin/inventory',       keywords:['inventario','stock','productos'],    iconClass:'' },
        { label:'Agregar Producto',         icon:'fa-plus',              url:B+'/admin/addProduct',      keywords:['agregar','nuevo producto'],          iconClass:'gs-success' },
        { label:'Actualizar Costos',        icon:'fa-dollar-sign',       url:B+'/product/updateCostsForm',keywords:['costos','precios'],                 iconClass:'gs-warning' },
        { label:'Pedidos',                  icon:'fa-shopping-cart',     url:B+'/order/index',           keywords:['pedidos','orders','compras'],        iconClass:'' },
        { label:'Realizar Pedido',          icon:'fa-cart-plus',         url:B+'/order/create',          keywords:['nuevo pedido'],                     iconClass:'gs-success' },
        { label:'Proveedores',              icon:'fa-truck',             url:B+'/supplier/index',        keywords:['proveedores'],                      iconClass:'' },
        { label:'Caja',                     icon:'fa-cash-register',     url:B+'/cash/index',            keywords:['caja','cash'],                      iconClass:'gs-success' },
        { label:'Facturar (POS)',           icon:'fa-receipt',           url:B+'/cash/pos',              keywords:['pos','vender','facturar'],          iconClass:'gs-info' },
        { label:'Historial de Caja',        icon:'fa-history',           url:B+'/cash/history',          keywords:['historial caja'],                   iconClass:'' },
        { label:'Movimientos Inventario',   icon:'fa-exchange-alt',      url:B+'/inventory/movements',   keywords:['movimientos','entradas','salidas'], iconClass:'' },
        { label:'Reporte de Bodega',        icon:'fa-print',             url:B+'/inventory/report',      keywords:['reporte bodega'],                   iconClass:'' },
        { label:'Stock de Bodegas',         icon:'fa-warehouse',         url:B+'/bodega/stock',          keywords:['bodega','stock'],                   iconClass:'' },
        { label:'Registrar Traslado',       icon:'fa-exchange-alt',      url:B+'/bodega/transfer',       keywords:['traslado','transferir'],            iconClass:'' },
        { label:'Historial Traslados',      icon:'fa-history',           url:B+'/bodega/history',        keywords:['historial traslados'],              iconClass:'' },
        { label:'Solicitar Descarte',       icon:'fa-trash-alt',         url:B+'/discard/create',        keywords:['descarte','eliminar','baja'],       iconClass:'gs-danger' },
        { label:'Solicitudes Pendientes',   icon:'fa-hourglass-half',    url:B+'/discard/listPending',   keywords:['pendientes','aprobacion'],          iconClass:'gs-warning' },
        { label:'Gestión de Usuarios',      icon:'fa-user-shield',       url:B+'/admin/manageRoles',     keywords:['usuarios','roles','permisos'],      iconClass:'' },
        { label:'Usuarios Bloqueados',      icon:'fa-user-lock',         url:B+'/admin/lockedUsers',     keywords:['bloqueados','suspendidos'],         iconClass:'gs-danger' },
        { label:'Traslados Sucursales',     icon:'fa-random',            url:B+'/branchTransfer/index',  keywords:['sucursales','traslado entre'],      iconClass:'' },
        { label:'Mi Perfil',               icon:'fa-user-circle',       url:B+'/admin/myProfile',       keywords:['perfil','cuenta','contraseña'],     iconClass:'' },
        { label:'Bajo Stock',              icon:'fa-exclamation-triangle',url:B+'/admin/lowStock',      keywords:['bajo stock','agotado'],             iconClass:'gs-danger' },
    ];

    function _fuzzy(q) {
        q = q.toLowerCase().trim();
        if (!q) return [];
        return NAV_MAP.filter(function (i) {
            return (i.label + ' ' + (i.keywords||[]).join(' ')).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 10);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 3. GLOBAL SEARCH MODAL
    // ═══════════════════════════════════════════════════════════════════════
    function _initGlobalSearch() {
        var input  = document.getElementById('globalSearchInput');
        var list   = document.getElementById('gs-result-list');
        var idle   = document.getElementById('gs-state-idle');
        var empty  = document.getElementById('gs-state-empty');
        var emptyQ = document.getElementById('gs-empty-query');
        if (!input || !list) return;
        var activeIdx = -1;

        function _hl(text, q) {
            var re = q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
            return text.replace(new RegExp('('+re+')','gi'),'<mark style="background:rgba(111,66,193,.15);color:#5a32a3;padding:0 2px;border-radius:3px;">$1</mark>');
        }

        function render(results, q) {
            list.innerHTML = ''; activeIdx = -1;
            if (!q.trim()) { idle.style.display='block'; empty.style.display='none'; list.style.display='none'; return; }
            if (!results.length) { idle.style.display='none'; empty.style.display='block'; list.style.display='none'; if(emptyQ) emptyQ.textContent='"'+q+'"'; return; }
            idle.style.display='none'; empty.style.display='none'; list.style.display='block';
            results.forEach(function (item) {
                var li = document.createElement('li');
                li.setAttribute('role','option'); li.setAttribute('aria-selected','false'); li.dataset.url=item.url;
                li.innerHTML='<a href="'+item.url+'" class="gs-modal-item" tabindex="-1"><span class="gs-item-icon '+(item.iconClass||'')+'"><i class="fas '+item.icon+'"></i></span><span class="gs-modal-label">'+_hl(_esc(item.label),q)+'</span><i class="fas fa-arrow-right gs-modal-arrow"></i></a>';
                li.querySelector('a').addEventListener('click', function () { _close(); });
                list.appendChild(li);
            });
        }

        function hl(idx) {
            list.querySelectorAll('li').forEach(function (el,i) {
                var a=el.querySelector('a');
                if(i===idx){el.setAttribute('aria-selected','true');a.classList.add('gs-active');el.scrollIntoView({block:'nearest'});}
                else{el.setAttribute('aria-selected','false');a.classList.remove('gs-active');}
            });
            activeIdx=idx;
        }

        input.addEventListener('input', function () { render(_fuzzy(this.value), this.value); });
        input.addEventListener('keydown', function (e) {
            var items=list.querySelectorAll('li');
            if(e.key==='ArrowDown'){e.preventDefault();hl(Math.min(activeIdx+1,items.length-1));}
            else if(e.key==='ArrowUp'){e.preventDefault();hl(Math.max(activeIdx-1,0));}
            else if(e.key==='Enter' && activeIdx>=0 && items[activeIdx]){var a=items[activeIdx].querySelector('a');if(a){_close();window.location.href=a.href;}}
        });

        function _open() {
            if(typeof MicroModal==='undefined') return;
            input.value=''; render([],'');
            MicroModal.show('modal-global-search',{onShow:function(){setTimeout(function(){input.focus();},80);},disableScroll:true,awaitOpenAnimation:true,awaitCloseAnimation:true});
        }
        function _close() { if(typeof MicroModal!=='undefined'){try{MicroModal.close('modal-global-search');}catch(_){}} }
        window.GlobalSearch = { open:_open, close:_close };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 4. RIPPLE
    // ═══════════════════════════════════════════════════════════════════════
    function _initRipple() {
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn');
            if (!btn) return;
            var r = document.createElement('span');
            r.className = 'btn-ripple';
            var rect = btn.getBoundingClientRect();
            var sz   = Math.max(rect.width, rect.height);
            r.style.width = r.style.height = sz+'px';
            r.style.left = (e.clientX-rect.left-sz/2)+'px';
            r.style.top  = (e.clientY-rect.top-sz/2)+'px';
            btn.appendChild(r);
            r.addEventListener('animationend', function(){r.remove();},{once:true});
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 5. SIDEBAR ACTIVE
    // ═══════════════════════════════════════════════════════════════════════
    function _initSidebarActive() {
        var cur = window.location.pathname.replace(/\/$/,'');
        var best=null, bestLen=0;
        document.querySelectorAll('.nav-sidebar .nav-link').forEach(function(link){
            var href=link.getAttribute('href');
            if(!href||href==='#') return;
            try{
                var lp=new URL(href,window.location.origin).pathname.replace(/\/$/,'');
                if(cur.indexOf(lp)===0&&lp.length>bestLen){bestLen=lp.length;best=link;}
            }catch(_){}
        });
        if(best){
            best.classList.add('ux-active');
            var pt=best.closest('.has-treeview');
            if(pt){pt.classList.add('menu-open');var pl=pt.querySelector(':scope > .nav-link');if(pl)pl.classList.add('ux-active');}
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 6. KEYBOARD SHORTCUTS
    // ═══════════════════════════════════════════════════════════════════════
    function _initKeyboardShortcuts() {
        document.addEventListener('keydown', function (e) {
            var tag = (document.activeElement||{}).tagName||'';
            var ed  = ['INPUT','TEXTAREA','SELECT'].indexOf(tag)!==-1;

            if((e.ctrlKey||e.metaKey)&&e.key==='k'){e.preventDefault();if(window.GlobalSearch)window.GlobalSearch.open();return;}
            if(e.key==='?'&&!ed){e.preventDefault();try{MicroModal.show('modal-shortcuts',{awaitOpenAnimation:true,awaitCloseAnimation:true});}catch(_){} return;}
            if(e.altKey&&e.key==='d'){e.preventDefault();window.location.href=B+'/admin/index';return;}
            if(e.altKey&&e.key==='p'){e.preventDefault();window.location.href=B+'/cash/pos';return;}
            if(e.altKey&&e.key==='t'){e.preventDefault();if(window.DarkMode)window.DarkMode.toggle();return;}
            if(e.altKey&&e.key==='ArrowUp'){e.preventDefault();window.scrollTo({top:0,behavior:'smooth'});return;}
            if(e.key==='Escape'&&!ed){var om=document.querySelector('.micromodal-slide.is-open');if(om&&typeof MicroModal!=='undefined'){try{MicroModal.close(om.id);}catch(_){}}}
            if(e.key==='/'&&!ed){var om2=document.querySelector('.micromodal-slide.is-open');if(om2)return;var ps=document.getElementById('posSearch');if(ps){e.preventDefault();ps.focus();ps.select();}}
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 7. KPI ANIMATIONS (fade-in)
    // ═══════════════════════════════════════════════════════════════════════
    function _initKpiAnimations() {
        document.querySelectorAll('.small-box').forEach(function(box){
            var col=box.closest('[class*="col-"]');
            if(col) col.classList.add('kpi-animate');
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 8. DARK MODE
    // ═══════════════════════════════════════════════════════════════════════
    var DM_KEY='solei_theme', DM_DARK='dark', DM_LIGHT='light';

    function _isDark(){ return document.documentElement.getAttribute('data-theme')===DM_DARK; }

    function _applyDark(dark){
        if(dark){document.documentElement.setAttribute('data-theme',DM_DARK);localStorage.setItem(DM_KEY,DM_DARK);}
        else{document.documentElement.removeAttribute('data-theme');localStorage.setItem(DM_KEY,DM_LIGHT);}
        _updateDMUI(dark);
    }

    function _updateDMUI(dark){
        var sw=document.getElementById('darkModeSwitch');
        var ic=document.getElementById('darkModeNavIcon');
        var lb=document.getElementById('darkModeNavLabel');
        if(sw) sw.classList.toggle('active',dark);
        if(ic){ic.className=dark?'fas fa-sun mr-2':'fas fa-moon mr-2';ic.style.color=dark?'#ffc107':'#6f42c1';}
        if(lb) lb.textContent=dark?'Modo Claro':'Modo Oscuro';
        var tr=document.getElementById('globalSearchTrigger');
        if(tr){if(dark){tr.style.background='#1e2130';tr.style.borderColor='rgba(255,255,255,.10)';tr.style.color='#94a3b8';}else{tr.style.background='#f8f9fa';tr.style.borderColor='#dee2e6';tr.style.color='#6c757d';}}
    }

    function _initDarkMode(){
        _updateDMUI(_isDark());
        window.DarkMode={
            toggle:  function(){_applyDark(!_isDark());},
            enable:  function(){_applyDark(true);},
            disable: function(){_applyDark(false);},
            isDark:  function(){return _isDark();}
        };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 9. TOP LOADING BAR
    // ═══════════════════════════════════════════════════════════════════════
    function _initTopBar(){
        var bar=document.getElementById('solei-topbar');
        var fill=document.getElementById('solei-topbar-fill');
        if(!bar||!fill) return;
        var prog=0, timer=null;

        function start(){
            bar.classList.add('active'); prog=0; fill.style.width='0%';
            timer=setInterval(function(){
                prog += prog<70?8:prog<90?1:0.2;
                fill.style.width=Math.min(prog,92)+'%';
            },80);
        }
        function finish(){
            clearInterval(timer); fill.style.width='100%';
            setTimeout(function(){bar.classList.remove('active');fill.style.width='0%';},400);
        }

        document.addEventListener('click',function(e){
            var a=e.target.closest('a[href]');
            if(!a) return;
            var h=a.getAttribute('href');
            if(!h||h==='#'||h.startsWith('javascript:')||h.startsWith('mailto:')||a.target==='_blank'||a.hasAttribute('download')||a.hasAttribute('data-micromodal-close')||a.hasAttribute('data-toggle')||e.ctrlKey||e.metaKey) return;
            start();
        });
        window.addEventListener('load',finish);
        if(document.readyState==='complete') finish();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 10. BACK TO TOP
    // ═══════════════════════════════════════════════════════════════════════
    function _initBackToTop(){
        var btn=document.getElementById('backToTop');
        if(!btn) return;
        var se=document.querySelector('.content-wrapper')||window;
        function check(){ var y=se===window?window.scrollY:se.scrollTop; btn.classList.toggle('visible',y>320); }
        se.addEventListener('scroll',check,{passive:true});
        window.addEventListener('scroll',check,{passive:true});
        btn.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});if(se!==window)se.scrollTo({top:0,behavior:'smooth'});});
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 11. REAL-TIME CLOCK + SHORTCUTS TRIGGER
    // ═══════════════════════════════════════════════════════════════════════
    function _initClock(){
        var nav=document.querySelector('.navbar-nav.ml-auto');
        if(!nav) return;
        var par=nav.parentNode;

        // Botón "?"
        var hint=document.createElement('button');
        hint.id='shortcutsTrigger';
        hint.setAttribute('aria-label','Atajos de teclado');
        hint.setAttribute('title','Atajos de teclado (?)');
        hint.textContent='?';
        hint.addEventListener('click',function(){try{MicroModal.show('modal-shortcuts',{awaitOpenAnimation:true,awaitCloseAnimation:true});}catch(_){}});

        // Reloj
        var clk=document.createElement('div');
        clk.id='navClock';
        clk.setAttribute('aria-label','Hora actual');
        clk.innerHTML='<i class="fas fa-clock"></i><span id="navClockTime">--:--:--</span>';

        par.insertBefore(clk, nav);
        par.insertBefore(hint, clk);

        var el=document.getElementById('navClockTime');
        function tick(){
            var n=new Date();
            if(el) el.textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
        }
        tick(); setInterval(tick,1000);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 12. ANIMATED KPI COUNTERS
    // ═══════════════════════════════════════════════════════════════════════
    function _initKpiCounters(){
        document.querySelectorAll('.small-box .inner h3, .small-box .inner p').forEach(function(el){
            var m=el.textContent.trim().match(/^([^\d]*)(\d[\d,.]*)([^\d]*)$/);
            if(!m) return;
            var pre=m[1], num=parseFloat(m[2].replace(/,/g,'')), suf=m[3];
            if(isNaN(num)||num===0) return;
            var dur=900, ts=null;
            el.classList.add('kpi-counter-animate');
            (function step(t){
                if(!ts) ts=t;
                var p=Math.min((t-ts)/dur,1);
                var ease=1-Math.pow(1-p,3);
                el.textContent=pre+Math.floor(ease*num).toLocaleString('es-NI')+suf;
                if(p<1) requestAnimationFrame(step);
                else el.textContent=pre+num.toLocaleString('es-NI')+suf;
            })(performance.now());
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 13. TABLE ENHANCEMENTS — row selection
    // ═══════════════════════════════════════════════════════════════════════
    function _initTableEnhancements(){
        document.addEventListener('click',function(e){
            var row=e.target.closest('table tbody tr');
            if(!row||e.target.closest('a,button,input,select')) return;
            var was=row.classList.contains('row-selected');
            var tbl=row.closest('table');
            if(tbl) tbl.querySelectorAll('tbody tr.row-selected').forEach(function(r){r.classList.remove('row-selected');});
            if(!was) row.classList.add('row-selected');
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS GLOBALES
    // ═══════════════════════════════════════════════════════════════════════
    window.SoleiNotify = function(type,title,msg,dur){ window.Toast.show({type:type,title:title,message:msg,duration:dur}); };

    // ═══════════════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════════════
    function _init(){
        _initRipple();
        _initSidebarActive();
        _initKeyboardShortcuts();
        _initKpiAnimations();
        _initGlobalSearch();
        _initDarkMode();
        _initTopBar();
        _initBackToTop();
        _initClock();
        _initKpiCounters();
        _initTableEnhancements();

        if(!localStorage.getItem('solei_gs_hint_shown')){
            setTimeout(function(){
                if(window.Toast) window.Toast.show({type:'info',title:'Tip',message:'Usa Ctrl+K para buscar módulos. Presiona ? para ver todos los atajos.',duration:6000});
                localStorage.setItem('solei_gs_hint_shown','1');
            },2500);
        }
    }

    if(document.readyState==='loading'){
        document.addEventListener('DOMContentLoaded',_init);
    } else {
        _init();
    }

})();
