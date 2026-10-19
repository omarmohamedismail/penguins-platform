(()=>{'use strict';
let mounted='',timer=null,deadline=0,remaining=90;
const host=document.getElementById('workspace');
function node(tag,text,parent,cls){const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;parent.append(n);return n;}
function action(text,parent,fn,cls='secondary'){const b=node('button',text,parent,cls);b.type='button';b.addEventListener('click',fn);return b;}
function dayKey(d=new Date()){return [d.getFullYear(),String(d.getMonth()+1).padStart(2,'0'),String(d.getDate()).padStart(2,'0')].join('-');}
window.PenguinsWorkspace={mount({user,profile,admin}){
 const identity=user.id+':'+(admin?'admin':profile?.role||'new');if(mounted===identity)return;mounted=identity;clearInterval(timer);timer=null;remaining=90;host.replaceChildren();
 const shell=node('section',undefined,host,'workspace');const top=node('div',undefined,shell,'workspace-top');
 node('div',new Date().toLocaleDateString('ar-EG',{weekday:'long',day:'numeric',month:'long'}),top,'eyebrow');node('h2',admin?'كل حاجة تحت السيطرة.':profile?.role==='coach'?'مساحة لأفكارك. وقت أكتر لعملائك.':'خطوة صغيرة، تقدّم كل يوم.',shell);
 node('p','نظّم أسبوعك وخد وقتك. التفاصيل الصغيرة بتفرق.',shell,'muted');
 const tabs=node('div',undefined,shell,'workspace-tabs');const links=admin?['طلبات انضمام الكوتش']:profile?.role==='coach'?['حالة حسابك','الشهادات والمؤهلات','طلبات العملاء','متابعات عملائك','ملاحظاتك الخاصة عن العميل','إرسال خطة تمرين','الخطط المرسلة والمسودات']:['مدرّبين معتمدين','متابعتك الأسبوعية','سجل المتابعة وردود الكوتش','خططي وملفات التمرين'];
 for(const title of links)action(title,tabs,()=>{const target=[...document.querySelectorAll('#content h2')].find(h=>h.textContent===title);if(target){const group=target.parentElement.dataset.group;if(group)document.querySelector('[data-section-tab="'+group+'"]')?.click();target.parentElement.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});target.tabIndex=-1;target.focus({preventScroll:true});}},'quiet');
 const details=node('details',undefined,shell,'organizer');node('summary','منظّمي الشخصي ومؤقّت الراحة',details);node('p','المنظّم محفوظ على المتصفح ده لحسابك فقط، ومش بيتبعت للكوتش أو العميل. حذف بيانات المتصفح بيمسحه.',details,'helper');
 const grid=node('div',undefined,details,'organizer-grid'),planner=node('div',undefined,grid);node('h3','أسبوعك على مهلك',planner);
 const key='penguins:organizer:v1:'+user.id;let data={};let available=true;try{data=JSON.parse(localStorage.getItem(key)||'{}');if(!data||Array.isArray(data)||typeof data!=='object')data={};}catch{data={};available=false;}
 const status=node('p','',planner,'helper');status.setAttribute('aria-live','polite');
 function save(){try{localStorage.setItem(key,JSON.stringify(data));status.textContent='اتحفظ على المتصفح ده.';}catch{status.textContent='المتصفح مش سامح بالحفظ. التغييرات متاحة لحد ما تقفل الصفحة.';}}
 if(!available)status.textContent='تعذر قراءة الحفظ المحلي. تقدر تستخدم المنظّم خلال الجلسة دي.';
 const week=node('div',undefined,planner,'week-grid');const progress=node('p','',planner,'week-progress');let completed=0;
 const start=new Date();start.setHours(12,0,0,0);start.setDate(start.getDate()-((start.getDay()+1)%7));
 const days=[];for(let i=0;i<7;i++){const d=new Date(start);d.setDate(d.getDate()+i);const id=dayKey(d),label=node('label',undefined,week,'day-choice');const input=node('input',undefined,label);input.type='checkbox';input.checked=data[id]===true;node('span',d.toLocaleDateString('ar-EG',{weekday:'short'}),label);node('small',d.getDate().toString(),label);if(id===dayKey())label.classList.add('today');days.push(input);input.addEventListener('change',()=>{data[id]=input.checked;save();count();});}
 function count(){completed=days.filter(d=>d.checked).length;progress.textContent=completed+' من ٧ أيام علّمت عليهم الأسبوع ده';}count();
 const noteLabel=node('label','هدف أو ملاحظة لنفسك',planner);const note=node('textarea',undefined,noteLabel);note.maxLength=1200;note.placeholder='إيه الخطوة اللي هتركّز عليها الأسبوع ده؟';note.value=typeof data.note==='string'?data.note.slice(0,1200):'';
 action('حفظ ملاحظتي',planner,()=>{data.note=note.value;const keep=new Set(Array.from({length:42},(_,i)=>{const d=new Date();d.setDate(d.getDate()+7-i);return dayKey(d);}));for(const k of Object.keys(data))if(k!=='note'&&!keep.has(k))delete data[k];save();});
 const clock=node('div',undefined,grid,'rest-clock');node('h3','خد نَفَس',clock);node('p','مؤقّت راحة بين المجموعات. اختار الوقت المناسب لخطة كوتشك.',clock,'helper');const display=node('output','01:30',clock,'clock-display');display.setAttribute('aria-label','الوقت المتبقي');const presets=node('div',undefined,clock,'actions');
 function paint(){display.textContent=String(Math.floor(remaining/60)).padStart(2,'0')+':'+String(remaining%60).padStart(2,'0');}
 function stop(){clearInterval(timer);timer=null;}
 const feedback=node('p','جاهز لما تكون جاهز.',clock,'helper');feedback.setAttribute('role','status');
 for(const seconds of [30,60,90,120])action(seconds+' ث',presets,()=>{stop();remaining=seconds;paint();feedback.textContent='تم اختيار '+seconds+' ثانية.';});
 const controls=node('div',undefined,clock,'actions');action('ابدأ / استكمل',controls,()=>{if(timer)return;if(remaining<=0)remaining=90;deadline=Date.now()+remaining*1000;feedback.textContent='المؤقّت شغّال. خليك فاتح الصفحة.';timer=setInterval(()=>{remaining=Math.max(0,Math.ceil((deadline-Date.now())/1000));paint();if(!remaining){stop();feedback.textContent='انتهت الراحة. جاهز للمجموعة الجاية؟';}},250);},'');action('إيقاف مؤقت',controls,()=>{if(timer)remaining=Math.max(0,Math.ceil((deadline-Date.now())/1000));stop();paint();feedback.textContent='متوقف مؤقتًا.';});action('إعادة',controls,()=>{stop();remaining=90;paint();feedback.textContent='رجعنا لدقيقة ونص.';},'quiet');
 }};
})();
