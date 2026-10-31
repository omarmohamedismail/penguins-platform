const api=window.penguinsClient;
const $=id=>document.getElementById(id);
function toast(message){const box=$('auth-status');box.textContent=message;box.hidden=false;}
function clearAuthFeedback(){ $('auth-status').hidden=true;for(const id of ['login-form-error','signup-form-error'])$(id).classList.add('hidden');}
function setMode(mode){clearAuthFeedback();const login=mode==='login';$('form-login').classList.toggle('hidden',!login);$('form-signup').classList.toggle('hidden',login);$('tab-login').classList.toggle('active',login);$('tab-signup').classList.toggle('active',!login);window.PenguinsAccess?.mode(mode);}
function toggleVisibility(id,button){const input=$(id);input.type=input.type==='password'?'text':'password';button.textContent=input.type==='password'?'إظهار':'إخفاء';}
function showError(id,message){const field=$(id);field.textContent=message;field.classList.remove('hidden');field.setAttribute('role','alert');}
function authError(error){if(error.code==='email_not_confirmed')window.PenguinsAccess?.confirmed();if(error.name==='AuthRetryableFetchError'||error.message==='Failed to fetch')return 'تعذر الاتصال. تأكد من الإنترنت وحاول مرة أخرى.';return ({invalid_credentials:'تعذر الدخول. تأكد من البريد وكلمة المرور، وأكّد بريدك أولًا إذا كان الحساب جديدًا.',email_not_confirmed:'أكّد بريدك الإلكتروني من رسالة التفعيل أولًا.',over_email_send_rate_limit:'تم بلوغ حد إرسال البريد. انتظر قليلًا ثم حاول مجددًا.',email_address_not_authorized:'خدمة البريد تحتاج إعدادًا من إدارة المنصة قبل إرسال رسالة لهذا العنوان.',user_already_exists:'هذا البريد مسجل بالفعل. استخدم تسجيل الدخول.',signup_disabled:'التسجيل متوقف مؤقتًا. تواصل مع إدارة المنصة.',otp_disabled:'تعذر إرسال رابط الدخول. لو دي أول مرة، اختار حساب جديد وحدد نوع حسابك.'})[error.code]||error.message||'تعذر إتمام العملية. حاول مجددًا.';}
function authUrl(name){return new URL(name,location.href).href;}
if(new URLSearchParams(location.search).get('mode')==='signup')setMode('signup');
const authParams=new URLSearchParams(location.search),authHash=new URLSearchParams((location.hash||'').slice(1));
const returningFromEmail=(authParams.has('code')||authHash.has('access_token'))&&!authParams.has('error')&&!authHash.has('error');
api.auth.getSession().then(({data,error})=>{if(error)throw error;if(!data?.session)return;
if(returningFromEmail&&authParams.get('switch')!=='1'){location.replace('portal.html');return;}
const existing=$('existing-session');existing.hidden=false;$('existing-session-email').textContent=data.session.user?.email||'';
}).catch(()=>showError('login-form-error','تعذر التحقق من جلسة الدخول. حدّث الصفحة وحاول مجددًا.'));
$('continue-session').addEventListener('click',()=>location.assign('portal.html'));

$('form-login').addEventListener('submit',async event=>{
 event.preventDefault();clearAuthFeedback();if(!$('login-email').value.trim()||!$('login-email').checkValidity()||!$('login-password').value)return showError('login-form-error','اكتب البريد الإلكتروني وكلمة المرور أولًا.');const button=$('login-submit');if(button.disabled)return;button.disabled=true;$('login-form-error').classList.add('hidden');
 try{const {error}=await api.auth.signInWithPassword({email:$('login-email').value.trim(),password:$('login-password').value});if(error)throw error;location.assign('portal.html');}
 catch(error){showError('login-form-error',authError(error));}
 finally{button.disabled=false;}
});
$('form-signup').addEventListener('submit',async event=>{
 event.preventDefault();clearAuthFeedback();const button=$('signup-submit');$('signup-form-error').classList.add('hidden');
 if(button.disabled)return;const name=$('signup-name').value.trim(),password=$('signup-password').value;
 if(!$('signup-email').value.trim()||!$('signup-email').checkValidity())return showError('signup-form-error','اكتب بريدك الإلكتروني الصحيح أولًا.');
 if(!['coach','client'].includes($('signup-role').value))return showError('signup-form-error','اختار نوع حسابك أولًا.');
 if(!name||name.length>80)return showError('signup-form-error','اكتب اسمك، بحد أقصى ٨٠ حرفًا.');
 if(password.length<10)return showError('signup-form-error','استخدم كلمة مرور من ١٠ أحرف على الأقل.');
 if(password!==$('signup-confirm').value)return showError('signup-form-error','كلمتا المرور غير متطابقتين.');
 if(!$('signup-terms').checked)return showError('signup-form-error','اطّلع على بيان البيانات وأكّد موافقتك قبل المتابعة.');
 button.disabled=true;
 try{
  const {data,error}=await api.auth.signUp({email:$('signup-email').value.trim(),password,options:{emailRedirectTo:authUrl('auth.html'),data:{name,requested_role:$('signup-role').value,niche:$('signup-niche').value,invitation_code:window.PenguinsInvites?.code||null}}});
  if(error)throw error;
  if(data.session)location.assign('portal.html');else{setMode('login');toast('لو البريد جديد، راجع رسالة التفعيل لتأكيد حسابك قبل الدخول. لو الحساب موجود بالفعل وكلمة المرور مش معاك، استخدم «نسيت كلمة المرور؟».');$('login-email').value=$('signup-email').value.trim();window.PenguinsAccess?.confirmed();}
 }catch(error){showError('signup-form-error',authError(error));}finally{button.disabled=false;}
});
$('signup-password').addEventListener('input',event=>{
 const password=event.target.value,score=[password.length>=10,password.length>=14,/[A-Z]/.test(password)&&/[a-z]/.test(password),/[0-9]/.test(password)&&/[^a-z0-9]/i.test(password)].filter(Boolean).length;
 $('strength-fill').style.width=(score*25)+'%';$('strength-fill').style.background=['#333','#ef4444','#f59e0b','#8b5cf6','#22c55e'][score];$('strength-label').textContent=['قوة كلمة المرور','ضعيفة','مقبولة','جيدة','قوية'][score];
});
$('reset-password').addEventListener('click',async()=>{
 clearAuthFeedback();const email=$('login-email').value.trim();if(!email||!$('login-email').checkValidity())return showError('login-form-error','اكتب بريدك الإلكتروني الصحيح أولًا.');
 const button=$('reset-password');if(button.disabled)return;button.disabled=true;
 try{const {error}=await api.auth.resetPasswordForEmail(email,{redirectTo:authUrl('recovery.html')});if(error)throw error;toast('إذا كان البريد مسجلًا لدينا، فستصلك رسالة لاستعادة كلمة المرور.');}
 catch(error){showError('login-form-error',authError(error));}finally{button.disabled=false;}
});

function updateSignupRole(){const coach=$('signup-role').value==='coach';$('signup-niche').parentElement.hidden=!coach;window.PenguinsAccess?.updateRole();}
$('signup-role').addEventListener('change',updateSignupRole);updateSignupRole();

$('resend-confirmation').addEventListener('click',async()=>{
 clearAuthFeedback();const email=$('login-email').value.trim();
 if(!email||!$('login-email').checkValidity())return showError('login-form-error','اكتب بريدك الإلكتروني الصحيح أولًا.');
 const button=$('resend-confirmation');if(button.disabled)return;button.disabled=true;
 try{const {error}=await api.auth.resend({type:'signup',email,options:{emailRedirectTo:authUrl('auth.html')}});if(error)throw error;toast('لو الحساب محتاج تفعيل، هتوصلك رسالة على نفس البريد. راجع الرسائل غير المرغوب فيها كمان.');}
 catch(error){showError('login-form-error',authError(error));}finally{button.disabled=false;}
});

$('email-link-login').addEventListener('click',async()=>{
 clearAuthFeedback();const email=$('login-email').value.trim();
 if(!email||!$('login-email').checkValidity())return showError('login-form-error','اكتب بريدك الإلكتروني الصحيح أولًا.');
 const button=$('email-link-login');if(button.disabled)return;button.disabled=true;
 try{const {error}=await api.auth.signInWithOtp({email,options:{emailRedirectTo:authUrl('auth.html'),shouldCreateUser:false}});if(error)throw error;toast('لو عندك حساب، راجع بريدك واضغط رابط الدخول. افتحه من نفس المتصفح اللي طلبت منه الرابط. لو مش لاقي الرسالة، راجع الرسائل غير المرغوب فيها.');}
 catch(error){showError('login-form-error',authError(error));}finally{button.disabled=false;}
});

const callbackError=new URLSearchParams(location.search).get('error')||new URLSearchParams((location.hash||'').slice(1)).get('error');if(callbackError)showError('login-form-error','رابط الدخول منتهي أو غير صالح. اطلب رسالة جديدة وافتحها من نفس المتصفح.');
