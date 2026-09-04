<?php

defined('ABSPATH') || exit;

final class K8_Canvas_Admin
{
    public static function register_menu(): void
    {
        add_menu_page('K8 Canvas', 'K8 Canvas', 'manage_options', 'k8-canvas', [self::class, 'render'], 'dashicons-layout', 58);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage K8 Canvas.', 'k8-canvas'));
        }
        global $wpdb;
        $t = K8_Canvas_Schema::tables();
        $orgs = $wpdb->get_results("SELECT * FROM {$t['organisations']} WHERE status='active' ORDER BY organisation_type,name", ARRAY_A);
        $sites = $wpdb->get_results("SELECT s.*,o.name owner_name FROM {$t['sites']} s JOIN {$t['organisations']} o ON o.id=s.owning_organisation_id WHERE s.status='active' ORDER BY o.name,s.name", ARRAY_A);
        $rels = $wpdb->get_results("SELECT r.*,a.name manager_name,c.name managed_name FROM {$t['relationships']} r JOIN {$t['organisations']} a ON a.id=r.managing_organisation_id JOIN {$t['organisations']} c ON c.id=r.managed_organisation_id WHERE r.status='active' ORDER BY a.name,c.name", ARRAY_A);
        $features = $wpdb->get_results("SELECT * FROM {$t['features']} WHERE lifecycle_status='active' ORDER BY name", ARRAY_A);
        $profiles = $wpdb->get_results("SELECT * FROM {$t['permission_profiles']} WHERE status='active' ORDER BY name", ARRAY_A);
        $memberships = $wpdb->get_results("SELECT m.id,u.user_login,o.name organisation_name,p.name profile_name FROM {$t['memberships']} m JOIN {$wpdb->users} u ON u.ID=m.user_id JOIN {$t['organisations']} o ON o.id=m.organisation_id LEFT JOIN {$t['permission_grants']} g ON g.membership_id=m.id AND g.revoked_at IS NULL LEFT JOIN {$t['permission_profiles']} p ON p.id=g.permission_profile_id WHERE m.status='active' ORDER BY o.name,u.user_login", ARRAY_A);
        $audit = $wpdb->get_results("SELECT a.*,u.user_login FROM {$t['audit_events']} a LEFT JOIN {$wpdb->users} u ON u.ID=a.actor_user_id ORDER BY a.id DESC LIMIT 20", ARRAY_A);
        $boot = ['api' => esc_url_raw(rest_url('k8-canvas/v1')), 'nonce' => wp_create_nonce('wp_rest'), 'sites' => $sites];
        ?>
        <div class="wrap k8" id="k8-app">
            <header><div><small>KOLLABOR8 OPERATING LAYER</small><h1>K8 Canvas Control</h1><p>Agencies, clients, sites and capabilities—without tangled ownership.</p></div><b>MVP <?php echo esc_html(K8_CANVAS_VERSION); ?></b></header>
            <div id="k8-note" class="notice" hidden><p></p></div>
            <section class="context">
                <label>Active organisation<select id="k8-org"><option value="">Choose an organisation</option><?php foreach ($orgs as $o) : ?><option value="<?php echo esc_attr($o['id']); ?>"><?php echo esc_html($o['name'] . ' — ' . ucfirst($o['organisation_type'])); ?></option><?php endforeach; ?></select></label>
                <label>Site<select id="k8-site" disabled><option value="">Organisation level</option></select></label>
                <span id="k8-context">Select an organisation to begin.</span>
            </section>
            <nav><button class="active" data-tab="estate">Estate</button><button data-tab="relationships">Relationships</button><button data-tab="features">Features</button><button data-tab="access">Access &amp; audit</button></nav>

            <main class="panel active" data-panel="estate"><div class="grid">
                <article><h2>Organisations <em><?php echo esc_html((string) count($orgs)); ?></em></h2>
                    <form id="add-org"><label>Name<input name="name" required></label><label>Type<select name="organisation_type"><option value="agency">Agency</option><option value="client">Client</option><option value="platform">Platform</option></select></label><button class="button button-primary">Add organisation</button></form>
                    <div class="list"><?php foreach ($orgs as $o) : ?><div><span><strong><?php echo esc_html($o['name']); ?></strong><small><?php echo esc_html(ucfirst($o['organisation_type'])); ?></small></span><i><?php echo esc_html($o['status']); ?></i></div><?php endforeach; ?><?php if (!$orgs) : ?><p>Create Kollabor8 as the first platform organisation.</p><?php endif; ?></div>
                </article>
                <article><h2>Connected sites <em><?php echo esc_html((string) count($sites)); ?></em></h2>
                    <form id="add-site"><label>Owner<select name="owning_organisation_id" required><option value="">Choose owner</option><?php foreach ($orgs as $o) : ?><option value="<?php echo esc_attr($o['id']); ?>"><?php echo esc_html($o['name']); ?></option><?php endforeach; ?></select></label><label>Site name<input name="name" required></label><label>Canonical URL<input name="canonical_url" type="url" placeholder="https://example.com.au" required></label><button class="button button-primary">Register site</button></form>
                    <div class="list"><?php foreach ($sites as $s) : ?><div><span><strong><?php echo esc_html($s['name']); ?></strong><small><?php echo esc_html($s['owner_name']); ?></small></span><a href="<?php echo esc_url($s['canonical_url']); ?>" target="_blank" rel="noopener">Visit</a></div><?php endforeach; ?><?php if (!$sites) : ?><p>No sites registered yet.</p><?php endif; ?></div>
                </article>
            </div></main>

            <main class="panel" data-panel="relationships"><article><h2>Agency → client relationships <em><?php echo esc_html((string) count($rels)); ?></em></h2>
                <form id="add-rel" class="row"><label>Managing agency<select name="managing_organisation_id" required><option value="">Choose agency</option><?php foreach ($orgs as $o) : ?><option value="<?php echo esc_attr($o['id']); ?>"><?php echo esc_html($o['name']); ?></option><?php endforeach; ?></select></label><b>→</b><label>Managed client<select name="managed_organisation_id" required><option value="">Choose client</option><?php foreach ($orgs as $o) : ?><option value="<?php echo esc_attr($o['id']); ?>"><?php echo esc_html($o['name']); ?></option><?php endforeach; ?></select></label><button class="button button-primary">Connect</button></form>
                <div class="list"><?php foreach ($rels as $r) : ?><div><strong><?php echo esc_html($r['manager_name']); ?></strong><span>manages →</span><strong><?php echo esc_html($r['managed_name']); ?></strong></div><?php endforeach; ?><?php if (!$rels) : ?><p>No relationships yet.</p><?php endif; ?></div>
            </article></main>

            <main class="panel" data-panel="features"><article><h2>Feature switches</h2><p>Choose an organisation or site. Switches are stored against that exact boundary.</p>
                <div class="features"><?php foreach ($features as $f) : ?><label><span><strong><?php echo esc_html($f['name']); ?></strong><small><?php echo esc_html($f['feature_key']); ?></small></span><input type="checkbox" data-feature="<?php echo esc_attr($f['id']); ?>" disabled><i></i></label><?php endforeach; ?></div>
            </article></main>

            <main class="panel" data-panel="access"><div class="grid">
                <article><h2>Memberships <em><?php echo esc_html((string) count($memberships)); ?></em></h2><p>Grant an existing WordPress user access to one organisation.</p>
                    <form id="add-member"><label>User login or email<input name="user" required></label><label>Organisation<select name="organisation_id" required><option value="">Choose organisation</option><?php foreach ($orgs as $o) : ?><option value="<?php echo esc_attr($o['id']); ?>"><?php echo esc_html($o['name']); ?></option><?php endforeach; ?></select></label><label>Profile<select name="profile_key" required><?php foreach ($profiles as $p) : ?><option value="<?php echo esc_attr($p['profile_key']); ?>"><?php echo esc_html($p['name']); ?></option><?php endforeach; ?></select></label><button class="button button-primary">Grant membership</button></form>
                    <div class="list"><?php foreach ($memberships as $m) : ?><div><span><strong><?php echo esc_html($m['user_login']); ?></strong><small><?php echo esc_html($m['organisation_name']); ?></small></span><i><?php echo esc_html($m['profile_name'] ?: 'Unassigned'); ?></i></div><?php endforeach; ?><?php if (!$memberships) : ?><p>No delegated memberships yet.</p><?php endif; ?></div>
                </article>
                <article><h2>Recent audit events</h2><p>Append-only evidence of consequential changes.</p>
                    <div class="list"><?php foreach ($audit as $event) : ?><div><span><strong><?php echo esc_html($event['action_key']); ?></strong><small><?php echo esc_html(($event['user_login'] ?: 'system') . ' · ' . $event['occurred_at']); ?></small></span><code><?php echo esc_html($event['resource_type'] . ':' . $event['resource_id']); ?></code></div><?php endforeach; ?><?php if (!$audit) : ?><p>The first managed change will appear here.</p><?php endif; ?></div>
                </article>
            </div></main>
        </div>
        <style>
        .k8{--o:#f26a21;--ink:#263238;--muted:#66747b;--line:#dbe0e3;max-width:1180px}.k8 *{box-sizing:border-box}.k8>header{display:flex;justify-content:space-between;margin:20px 0;padding:28px 32px;border-radius:14px;background:linear-gradient(135deg,#202a2f,#43535a);color:#fff}.k8 header h1{margin:3px 0;color:#fff;font-size:30px}.k8 header p{margin:0;color:#dfe7ea}.k8 header small{color:var(--o);font-weight:700;letter-spacing:.12em}.k8 header>b,.k8 .list i{align-self:flex-start;padding:6px 10px;border-radius:99px;background:#fff2eb;color:#9c3b08;font-size:11px;font-style:normal}.k8 .context{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;align-items:end;padding:20px 24px;background:#fff;border:1px solid var(--line);border-radius:12px}.k8 label{display:grid;gap:5px;color:var(--muted);font-size:12px;font-weight:600}.k8 input,.k8 select{width:100%;min-height:38px}.k8 nav{display:flex;gap:6px;margin:22px 0 12px;border-bottom:1px solid var(--line)}.k8 nav button{padding:11px 17px;border:0;border-bottom:3px solid transparent;background:transparent;font-weight:600;cursor:pointer}.k8 nav button.active{border-color:var(--o)}.k8 .panel{display:none}.k8 .panel.active{display:block}.k8 .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.k8 article{padding:23px;background:#fff;border:1px solid var(--line);border-radius:12px}.k8 article h2{display:flex;justify-content:space-between;margin-top:0}.k8 h2 em{font-size:27px;font-style:normal}.k8 form{display:grid;gap:10px;padding:15px;margin-bottom:16px;border-radius:9px;background:#f6f8f9}.k8 form.row{grid-template-columns:1fr auto 1fr auto;align-items:end}.k8 .list>div{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 7px;border-bottom:1px solid #edf0f1}.k8 .list span,.k8 .features span{display:grid}.k8 small{color:var(--muted)}.k8 .features{display:grid;grid-template-columns:1fr 1fr;gap:10px}.k8 .features label{display:flex;align-items:center;justify-content:space-between;padding:15px;border:1px solid var(--line);border-radius:9px}.k8 .features input{position:absolute;opacity:0}.k8 .features i{position:relative;width:40px;height:22px;border-radius:20px;background:#b7c0c4}.k8 .features i:after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:.2s}.k8 .features input:checked+i{background:var(--o)}.k8 .features input:checked+i:after{transform:translateX(18px)}.k8 .features input:disabled+i{opacity:.4}@media(max-width:900px){.k8 .context,.k8 .grid,.k8 .features{grid-template-columns:1fr}.k8 form.row{grid-template-columns:1fr}}
        </style>
        <script>
        (()=>{const b=<?php echo wp_json_encode($boot); ?>,$=s=>document.querySelector(s),$$=s=>document.querySelectorAll(s),call=async(p,o={})=>{const r=await fetch(b.api+p,{...o,headers:{'Content-Type':'application/json','X-WP-Nonce':b.nonce}}),j=await r.json();if(!r.ok)throw Error(j.message||'Request failed');return j},note=(m,bad=false)=>{const n=$('#k8-note');n.className='notice '+(bad?'notice-error':'notice-success');n.querySelector('p').textContent=m;n.hidden=false},bind=(id,p,m)=>$(id).onsubmit=async e=>{e.preventDefault();try{await call(p,{method:'POST',body:JSON.stringify(Object.fromEntries(new FormData(e.currentTarget)))});note(m);location.reload()}catch(x){note(x.message,true)}};$$('.k8 nav button').forEach(x=>x.onclick=()=>{$$('.k8 nav button,.k8 .panel').forEach(y=>y.classList.remove('active'));x.classList.add('active');$(`[data-panel="${x.dataset.tab}"]`).classList.add('active')});const org=$('#k8-org'),site=$('#k8-site');async function load(){const oid=org.value,sid=site.value;$$('[data-feature]').forEach(x=>{x.disabled=!oid;x.checked=false});if(!oid)return;try{const j=await call('/features?'+(sid?'site_id='+sid:'organisation_id='+oid));j.data.forEach(f=>{const x=$(`[data-feature="${f.id}"]`);if(x)x.checked=String(f.enabled)==='1'})}catch(x){note(x.message,true)}}org.onchange=()=>{const a=b.sites.filter(s=>String(s.owning_organisation_id)===org.value);site.innerHTML='<option value="">Organisation level</option>'+a.map(s=>`<option value="${s.id}">${s.name}</option>`).join('');site.disabled=!org.value;$('#k8-context').textContent=org.value?`${org.options[org.selectedIndex].text} · ${a.length} site(s)`:'Select an organisation to begin.';load()};site.onchange=load;$$('[data-feature]').forEach(x=>x.onchange=async()=>{const body={feature_id:x.dataset.feature,enabled:x.checked};body[site.value?'site_id':'organisation_id']=site.value||org.value;try{await call('/feature-assignments',{method:'POST',body:JSON.stringify(body)});note('Feature assignment updated.')}catch(e){x.checked=!x.checked;note(e.message,true)}});bind('#add-org','/organisations','Organisation created.');bind('#add-site','/sites','Site registered.');bind('#add-rel','/relationships','Relationship created.');})();
        document.querySelector('#add-member').addEventListener('submit',async event=>{event.preventDefault();const boot=<?php echo wp_json_encode($boot); ?>;const response=await fetch(boot.api+'/memberships',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':boot.nonce},body:JSON.stringify(Object.fromEntries(new FormData(event.currentTarget)))});const result=await response.json();if(response.ok){location.reload()}else{const note=document.querySelector('#k8-note');note.className='notice notice-error';note.querySelector('p').textContent=result.message||'Unable to grant membership.';note.hidden=false}});
        </script>
        <?php
    }
}
