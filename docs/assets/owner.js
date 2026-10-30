(()=>{'use strict';
window.PenguinsOwner={async mount(ctx){
const {target,api,checked,el,button,field,option,message,confirm,reviewQueue}=ctx;
const titles={overview:'نظرة عامة',reviews:'مراجعة الكوتشات',members:'كل الأعضاء',links:'طلبات التدريب',plans:'إدارة الخطط',activity:'سجل القرارات',settings:'إعدادات المنصة'};
const icons={overview:'◈',reviews:'✓',members:'◎',links:'⇄',plans:'▤',activity:'◷',settings:'⚙'};
const state=window.PenguinsOwner.state||(window.PenguinsOwner.state={tab:'overview',page:0,search:''});
const shell=el('div',undefined,target,'owner-shell'),side=el('aside',undefined,shell,'owner-sidebar');el('p','P / CONTROL ROOM',side,'eyebrow');el('h2','مساحتك أنت.',side);el('p','إدارة المنصة',side,'helper');const nav=el('nav',undefined,side);nav.setAttribute('aria-label','أقسام إدارة المنصة');
const pane=el('div',undefined,shell,'owner-pane');pane.setAttribute('aria-live','polite');let version=0;
for(const [key,title]of Object.entries(titles)){const b=button(icons[key]+'  '+title,nav,async()=>{state.tab=key;state.page=0;state.search='';await draw();},'owner-tab');b.dataset.tab=key;}
el('p','الصلاحيات متاحة لحساب المدير المعتمد فقط.',side,'helper');
function label(v){return {requested:'بانتظار الرد',accepted:'مقبول',declined:'مرفوض',approved:'اعتماد كوتش',rejected:'رفض كوتش',pause:'تغيير إيقاف الحساب',rename:'تعديل اسم',visibility:'تغيير ظهور خطة',link:'تغيير طلب تدريب',announcement:'رسالة المنصة'}[v]||v;}
function date(v){return new Date(v).toLocaleString('ar-EG',{dateStyle:'medium',timeStyle:'short'});}
async function action(kind,payload){await checked(api.rpc('owner_action',{p_action:kind,p_payload:payload}));message('تم حفظ التغيير في سجل الإدارة.','success');await draw();}
function modal(title){const d=el('dialog',undefined,document.body);const h=el('h2',title,d);h.id='owner-modal-title';d.setAttribute('aria-labelledby',h.id);d.addEventListener('close',()=>d.remove(),{once:true});d.addEventListener('cancel',()=>d.close());return d;}
async function reason(title,description,run){const d=modal(title);el('p',description,d);const note=field(d,'سبب القرار');note.maxLength=500;const actions=el('div',undefined,d,'actions');button('رجوع',actions,()=>d.close(),'secondary');button('تأكيد القرار',actions,async()=>{if(!note.value.trim()){note.setCustomValidity('اكتب سبب القرار');note.reportValidity();return;}note.setCustomValidity('');await run(note.value.trim());d.close();});note.addEventListener('input',()=>note.setCustomValidity(''));d.showModal();note.focus();}
async function editName(row){const d=modal('تعديل اسم العضو');const name=field(d,'الاسم الظاهر');name.value=row.name;name.maxLength=80;const actions=el('div',undefined,d,'actions');button('رجوع',actions,()=>d.close(),'secondary');button('حفظ الاسم',actions,async()=>{if(!name.value.trim()){name.setCustomValidity('اكتب الاسم');name.reportValidity();return;}await action('rename',{id:row.id,name:name.value.trim(),expected_name:row.name});d.close();});d.showModal();name.focus();}
function stats(data,parent){const grid=el('div',undefined,parent,'owner-stats');for(const [key,title]of [['members','كل الأعضاء'],['coaches','حسابات الكوتش'],['clients','العملاء'],['pending','مستنيين المراجعة'],['links','روابط تدريب مقبولة'],['plans','الخطط المحفوظة']]){const card=el('article',undefined,grid,'owner-stat');el('span',title,card);el('strong',data[key]??0,card);}}
async function recentActivity(parent){
const box=el('section',undefined,parent,'owner-live');el('h3','آخر نشاط على المنصة',box);
el('p','أحدث التسجيلات وطلبات الربط والخطط وقرارات الإدارة. اضغط تحديث البيانات لجلب الجديد.',box,'helper');
const sources=['members','links','plans','activity'];
const results=await Promise.all(sources.map(p_view=>checked(api.rpc('owner_data',{p_view,p_page:0,p_search:''}))));
const events=[];
results.forEach((result,i)=>{for(const row of result.rows||[]){const source=sources[i];events.push({source,row,time:new Date(row.created_at).getTime()});}});
events.sort((a,b)=>b.time-a.time);
if(!events.length)el('p','لسه مفيش نشاط مسجل.',box,'helper');
for(const {source,row}of events.slice(0,12)){
const card=el('article',undefined,box,'owner-event');
const title=source==='members'?'انضمام عضو':source==='links'?'طلب تدريب':source==='plans'?'خطة مشتركة':label(row.action);
el('strong',title,card);
const detail=source==='members'?row.name:source==='links'?row.client_name+' ← '+row.coach_name:source==='plans'?row.title+' · '+row.coach_name+' ← '+row.client_name:row.target;
el('p',detail,card).dataset.userContent='true';
if(source==='links')el('span',label(row.status),card,'badge');
el('small',date(row.created_at),card);
button('عرض التفاصيل',card,async()=>{state.tab=source;state.page=0;state.search='';await draw();},'secondary');
}
el('p','آخر تحديث: '+date(new Date()),box,'helper');
}
async function draw(){const token=++version;pane.setAttribute('aria-busy','true');try{
for(const b of nav.querySelectorAll('[data-tab]')){b.setAttribute('aria-current',b.dataset.tab===state.tab?'page':'false');}
const f=document.createDocumentFragment();const header=el('div',undefined,f,'owner-title');el('h2',titles[state.tab],header);button('تحديث البيانات',header,()=>draw(),'secondary');
if(state.tab==='reviews'){const guide=el('section',undefined,f,'owner-review-guide');el('h3','إزاي تعتمد كوتش؟',guide);const steps=el('ol',undefined,guide);for(const text of ['اختار قيد المراجعة وابحث باسم الكوتش.','راجع بياناته وافتح الشهادات المرفوعة إن وجدت.','اضغط اعتماد الكوتش ثم أكّد القرار. للرفض، اكتب سببًا واضحًا.'])el('li',text,steps);el('p','العميل يدخل بعد تأكيد البريد بدون موافقتك. اعتماد الكوتش يفتح له استقبال العملاء وإرسال الخطط.',guide,'helper');await reviewQueue(f);if(token===version)pane.replaceChildren(f);return;}
const result=await checked(api.rpc('owner_data',{p_view:state.tab==='settings'?'overview':state.tab,p_page:state.page,p_search:state.search}));
if(state.tab==='overview'){
stats(result,f);await recentActivity(f);const strip=el('div',undefined,f,'owner-summary');el('p','انضم خلال آخر ٧ أيام: '+result.new_week,strip);el('p','حسابات موقوفة: '+result.paused,strip);el('p','خطط مخفية: '+result.hidden,strip);
const chart=el('section',undefined,f);el('h3','حركة الانضمام · آخر ٧ أيام',chart);const max=Math.max(1,...(result.days||[]).map(d=>Number(d.members)));for(const d of result.days||[]){const row=el('div',undefined,chart,'chart-row');el('span',new Date(d.day).toLocaleDateString('ar-EG',{weekday:'short',day:'numeric'}),row);const bar=el('progress',undefined,row);bar.max=max;bar.value=Number(d.members);bar.setAttribute('aria-label',d.day+': '+d.members+' أعضاء');el('strong',d.members,row);}
const quick=el('section',undefined,f);el('h3','الخطوة الجاية',quick);el('p',result.pending?'فيه طلبات كوتش مستنية قرارك.':'مفيش طلبات كوتش معلّقة دلوقتي. تابع الأعضاء والطلبات من القائمة.',quick,'helper');button('افتح مراجعة الكوتشات',quick,async()=>{state.tab='reviews';state.page=0;await draw();});
}else if(state.tab==='settings'){
const box=el('section',undefined,f);el('h3','رسالة عامة داخل حسابات الأعضاء',box);el('p','تظهر كنص لكل الأعضاء بعد تحديث حسابهم. امسح النص لحذف الرسالة. لا يتم إرسال بريد أو إشعارات خارجية.',box,'helper');const text=field(box,'نص الرسالة','textarea');text.maxLength=500;text.value=result.announcement||'';button('حفظ رسالة المنصة',box,async()=>{if(!await confirm('نشر رسالة المنصة؟','الرسالة هتظهر لكل الأعضاء داخل بوابة الحساب.'))return;await action('announcement',{note:text.value.trim(),expected:result.announcement||''});});const info=el('section',undefined,f);el('h3','حدود الإدارة',info);el('p','البيانات والصلاحيات على السيرفر. إيقاف الحساب يمنع الوصول لبيانات المنصة دون حذفها، ولا يحذف حساب تسجيل الدخول. روابط الملفات التي سبق إنشاؤها قد تبقى صالحة حتى تنتهي مدتها القصيرة.',info,'helper');el('p','تغيير البريد وكلمات المرور وإضافة مدير آخر ليست أزرارًا داخل هذه اللوحة. مساحة العمل الخاصة بالكوتش لا تُعرض للإدارة هنا.',info,'helper');
}else{
if(['members','links','plans'].includes(state.tab)){const form=el('form',undefined,f,'owner-search');const search=field(form,state.tab==='members'?'ابحث بالاسم أو البريد':state.tab==='links'?'ابحث باسم الكوتش أو العميل':'ابحث بعنوان الخطة');search.value=state.search;search.maxLength=80;const submit=el('button','بحث',form);submit.type='submit';form.addEventListener('submit',e=>{e.preventDefault();state.search=search.value.trim();state.page=0;draw().catch(e=>message(window.PenguinsPortalCore.errorMessage(e),'error'));});}
el('p','إجمالي النتائج: '+result.total,f,'helper');if(!result.rows?.length){const empty=el('div',undefined,f,'empty');el('h3','مفيش بيانات في القسم ده حاليًا',empty);el('p','البيانات هتظهر لما الأعضاء يبدأوا استخدام المنصة.',empty);}
for(const row of result.rows||[]){const card=el('article',undefined,f,'owner-record');
if(state.tab==='members'){
const head=el('div',undefined,card,'card-head');el('h3',row.name,head);el('span',row.paused?'موقوف':row.role==='coach'?'كوتش':'عميل',head,'badge');const email=el('p',row.email||'بريد غير متاح',card,'helper');email.dir='ltr';el('p',(window.PenguinsPortalCore.labels[row.status]||row.status)+' · '+date(row.created_at),card,'helper');if(row.control_note)el('p','آخر سبب إداري: '+row.control_note,card,'inline-note');const actions=el('div',undefined,card,'actions');button('تعديل الاسم',actions,()=>editName(row),'secondary');button(row.paused?'إعادة تفعيل الحساب':'إيقاف الحساب',actions,()=>reason(row.paused?'إعادة تفعيل الحساب؟':'إيقاف الحساب؟','القرار يطبّق على الوصول لبيانات المنصة. مفيش بيانات هتتحذف.',note=>action('pause',{id:row.id,paused:!row.paused,expected_paused:row.paused,note})),row.paused?'secondary':'danger');
}else if(state.tab==='links'){
el('h3',row.client_name+' ← '+row.coach_name,card);el('span',label(row.status),card,'badge');el('p',date(row.created_at),card,'helper');const actions=el('div',undefined,card,'actions');for(const [value,title] of [['accepted','قبول الربط'],['declined','رفض / إيقاف الربط']]){const b=button(title,actions,()=>reason(title,'قبول الربط يسمح للكوتش المعتمد بإرسال خطط. إيقاف الربط يمنع خططًا جديدة، والخطط السابقة تظل متاحة ما لم تخفها من قسم الخطط.',note=>action('link',{coach_id:row.coach_id,client_id:row.client_id,status:value,expected_status:row.status,note})),'secondary');b.disabled=value===row.status;}
}else if(state.tab==='plans'){
el('h3',row.title,card);el('p','الكوتش: '+row.coach_name+' · العميل: '+row.client_name,card,'helper');el('span',row.moderation_hidden?'مخفية عن العميل':row.pdf_name&&!row.attachment_ready?'مسودة PDF':'متاحة',card,'badge');el('p',date(row.created_at),card,'helper');const details=el('details',undefined,card);el('summary','عرض نص الخطة',details);el('p',row.details||'لا توجد تفاصيل.',details,'plan-details');if(row.pdf_name)el('p','مرفق: '+row.pdf_name,details,'file-name');button(row.moderation_hidden?'إظهار للعميل':'إخفاء عن العميل',card,()=>reason('تغيير ظهور الخطة','إخفاء الخطة يمنع ظهورها وإنشاء روابط جديدة لملفها من حساب العميل، مع الاحتفاظ بها للكوتش.',note=>action('visibility',{id:row.id,hidden:!row.moderation_hidden,expected_hidden:row.moderation_hidden,note})),'secondary');
}else{el('h3',label(row.action),card);el('p',row.target,card,'helper');el('p',row.note||'بدون ملاحظة',card);el('small',date(row.created_at),card);}
}
const pager=el('div',undefined,f,'pagination');const prev=button('السابق',pager,async()=>{state.page--;await draw();},'secondary');prev.disabled=!state.page;el('span','صفحة '+(state.page+1),pager,'helper');const next=button('التالي',pager,async()=>{state.page++;await draw();},'secondary');next.disabled=(state.page+1)*20>=result.total;
}
if(token===version)pane.replaceChildren(f);
}finally{if(token===version)pane.setAttribute('aria-busy','false');}}
await draw();
}};
})();
