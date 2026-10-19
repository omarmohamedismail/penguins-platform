let recoveryReady=false;
penguinsClient.auth.onAuthStateChange(event=>{if(event==='PASSWORD_RECOVERY')recoveryReady=true;});
penguinsClient.auth.getSession().then(({data})=>{if(data.session)recoveryReady=true;});
document.getElementById('recovery-form').addEventListener('submit',async event=>{
 event.preventDefault();const message=document.getElementById('recovery-message'),button=event.target.querySelector('button');
 if(!recoveryReady){message.textContent='Your recovery link is missing or expired. Request a new link from the login page.';return;}
 const password=document.getElementById('password').value;
 if(password.length<10||password!==document.getElementById('confirm').value){message.textContent='Use at least 10 characters and make both passwords match.';return;}
 button.disabled=true;try{const {error}=await penguinsClient.auth.updateUser({password});if(error)throw error;message.textContent='Password updated. Opening your workspace…';location.replace('dashboard.html');}catch(error){message.textContent=error.message;}finally{button.disabled=false;}
});
