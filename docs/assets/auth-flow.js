(()=>{'use strict';
const $=id=>document.getElementById(id),en=()=>window.PenguinsLanguage?.language==='en';
function label(n,ar,english){n.dataset.languageControl='true';n.dataset.accessAr=ar;n.dataset.accessEn=english;n.textContent=en()?english:ar;return n;}
function make(tag,parent,cls){const n=document.createElement(tag);if(cls)n.className=cls;parent.append(n);return n;}
function localize(){document.querySelectorAll('[data-access-ar]').forEach(n=>n.textContent=en()?n.dataset.accessEn:n.dataset.accessAr);}
window.addEventListener('penguins-language-change',localize);
const role=$('signup-role'),params=new URLSearchParams(location.search);if(!window.PenguinsInvites?.code){if(window.PenguinsConnections?.pending())role.value='client';else if(['client','coach'].includes(params.get('role')))role.value=params.get('role');}
const choices=document.createElement('div');choices.className='auth-role-choices';choices.setAttribute('role','group');choices.setAttribute('aria-label','نوع الحساب / Account type');role.before(choices);role.hidden=true;document.querySelector('label[for="signup-role"]')?.setAttribute('hidden','');
for(const [value,ar,english,desc,descEn]of [['client','أنا عميل','I’m a client','تماريني ومتابعتي مع الكوتش','My training and coach follow-up'],['coach','أنا كوتش','I’m a coach','إدارة العملاء والخطط','Manage clients and training plans']]){const b=make('button',choices);b.type='button';b.dataset.role=value;label(make('strong',b),ar,english);label(make('span',b),desc,descEn);b.addEventListener('click',()=>{role.value=value;role.dispatchEvent(new Event('change',{bubbles:true}));});}
const note=make('p',choices,'auth-role-note');
function updateRole(){for(const b of choices.querySelectorAll('button'))b.setAttribute('aria-pressed',String(b.dataset.role===role.value));if(role.value==='coach')label(note,'أكّد بريدك، ثم كمّل ملفك وانتظر اعتماد الإدارة لاستقبال العملاء.','Confirm your email, complete your profile and wait for approval before receiving clients.');else label(note,'أكّد بريدك مرة واحدة؛ حسابك يفتح مباشرة بدون انتظار موافقة الإدارة.','Confirm your email once; your client account opens without owner approval.');}
const context=document.createElement('div');context.className='auth-connection-context';context.hidden=!window.PenguinsConnections?.pending();$('form-login').before(context);label(make('strong',context),'خطوة واحدة وتوصل لكوتشك','You’re on your way to your coach');label(make('p',context),'ادخل بحساب العميل أو اعمل حساب جديد. بعد الدخول هنرجعك لصفحة الكوتش عشان تراجع اسمه وتبعت طلب الربط.','Sign in with a client account or create one. We’ll return you to the coach profile so you can review their name and request coaching.');
const confirmation=document.createElement('section');confirmation.className='auth-confirmation';confirmation.hidden=true;confirmation.setAttribute('role','status');$('auth-status').after(confirmation);
label(make('h3',confirmation),'افتح رسالة التفعيل في بريدك','Open your confirmation email');
const steps=make('ol',confirmation);for(const [ar,english]of [['افتح صندوق الوارد أو الرسائل غير المرغوب فيها.','Check your inbox or spam folder.'],['اضغط رابط التفعيل وافتحه في نفس المتصفح.','Open the confirmation link in this same browser.'],['هنفتح مساحة حسابك تلقائيًا؛ ولو رجعت هنا ادخل بالبريد وكلمة المرور.','Your workspace opens automatically; if you return here, sign in with your email and password.']])label(make('li',steps),ar,english);
function confirmed(){confirmation.hidden=false;const options=$('auth-help-options');if(options)options.open=true;}
function mode(value){$('tab-login').setAttribute('aria-pressed',String(value==='login'));$('tab-signup').setAttribute('aria-pressed',String(value==='signup'));if(value==='signup')confirmation.hidden=true;}
role.addEventListener('change',updateRole);updateRole();mode(params.get('mode')==='signup'?'signup':'login');
window.PenguinsAccess={updateRole,confirmed,mode};
})();
