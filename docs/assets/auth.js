const api=window.penguinsClient;
const $=id=>document.getElementById(id);
function toast(message){const box=$('auth-status');box.textContent=message;box.hidden=false;}
function setMode(mode){const login=mode==='login';$('form-login').classList.toggle('hidden',!login);$('form-signup').classList.toggle('hidden',login);$('tab-login').classList.toggle('active',login);$('tab-signup').classList.toggle('active',!login);}
function toggleVisibility(id,button){const input=$(id);input.type=input.type==='password'?'text':'password';button.textContent=input.type==='password'?'Show':'Hide';}
function showError(id,message){const field=$(id);field.textContent=message;field.classList.remove('hidden');field.setAttribute('role','alert');}
function authUrl(name){return new URL(name,location.href).href;}
if(new URLSearchParams(location.search).get('mode')==='signup')setMode('signup');
api.auth.getSession().then(({data})=>{if(data.session)location.replace('dashboard.html');});
$('form-login').addEventListener('submit',async event=>{
 event.preventDefault();const button=$('login-submit');button.disabled=true;$('login-form-error').classList.add('hidden');
 try{const {error}=await api.auth.signInWithPassword({email:$('login-email').value.trim(),password:$('login-password').value});if(error)throw error;location.assign('dashboard.html');}
 catch(error){showError('login-form-error',error.message||'Could not log in. Check your connection.');}
 finally{button.disabled=false;}
});
$('form-signup').addEventListener('submit',async event=>{
 event.preventDefault();const button=$('signup-submit');$('signup-form-error').classList.add('hidden');
 const name=$('signup-name').value.trim(),password=$('signup-password').value;
 if(!name||name.length>80)return showError('signup-form-error','Enter your name, up to 80 characters.');
 if(password.length<10)return showError('signup-form-error','Use a password with at least 10 characters.');
 if(password!==$('signup-confirm').value)return showError('signup-form-error','Passwords do not match.');
 if(!$('signup-terms').checked)return showError('signup-form-error','Please read and accept the development-use notice.');
 button.disabled=true;
 try{
  const {data,error}=await api.auth.signUp({email:$('signup-email').value.trim(),password,options:{emailRedirectTo:authUrl('auth.html'),data:{name,niche:$('signup-niche').value}}});
  if(error)throw error;
  if(data.session)location.assign('dashboard.html');else{toast('Check your email to confirm your account, then log in here.');setMode('login');$('login-email').value=$('signup-email').value.trim();}
 }catch(error){showError('signup-form-error',error.message||'Could not create your account.');}finally{button.disabled=false;}
});
$('signup-password').addEventListener('input',event=>{
 const password=event.target.value,score=[password.length>=10,password.length>=14,/[A-Z]/.test(password)&&/[a-z]/.test(password),/[0-9]/.test(password)&&/[^a-z0-9]/i.test(password)].filter(Boolean).length;
 $('strength-fill').style.width=(score*25)+'%';$('strength-fill').style.background=['#333','#ef4444','#f59e0b','#8b5cf6','#22c55e'][score];$('strength-label').textContent=['Password strength','Weak','Fair','Good','Strong'][score];
});
$('reset-password').addEventListener('click',async()=>{
 const email=$('login-email').value.trim();if(!email)return showError('login-form-error','Enter your email above, then select Forgot password.');
 const button=$('reset-password');button.disabled=true;
 try{const {error}=await api.auth.resetPasswordForEmail(email,{redirectTo:authUrl('recovery.html')});if(error)throw error;toast('If this email has an account, a recovery link will arrive shortly.');}
 catch(error){showError('login-form-error',error.message);}finally{button.disabled=false;}
});
