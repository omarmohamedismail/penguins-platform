let recoveryReady=false;
penguinsClient.auth.onAuthStateChange(event=>{if(event==='PASSWORD_RECOVERY')recoveryReady=true;});
penguinsClient.auth.getSession().then(({data})=>{if(data.session)recoveryReady=true;});
document.getElementById('recovery-form').addEventListener('submit',async event=>{
 event.preventDefault();const message=document.getElementById('recovery-message'),button=event.target.querySelector('button');
 if(!recoveryReady){message.textContent='رابط الاستعادة غير صالح أو انتهت مدته. اطلب رابطًا جديدًا من صفحة تسجيل الدخول.';return;}
 const password=document.getElementById('password').value;
 if(password.length<10||password!==document.getElementById('confirm').value){message.textContent='استخدم ١٠ أحرف على الأقل، وتأكد من تطابق كلمتي المرور.';return;}
 button.disabled=true;try{const {error}=await penguinsClient.auth.updateUser({password});if(error)throw error;message.textContent='تم تغيير كلمة المرور. جارٍ فتح حسابك…';location.replace('portal.html');}catch(error){message.textContent=error.message;}finally{button.disabled=false;}
});
