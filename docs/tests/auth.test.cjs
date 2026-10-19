const test=require('node:test'),assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs');
function harness(){
 const nodes=new Map(),calls=[];
 const get=id=>{if(!nodes.has(id)){const classes=new Set();nodes.set(id,{value:'',textContent:'',hidden:false,disabled:false,checked:true,type:'password',style:{},parentElement:{hidden:false},listeners:{},classList:{add:x=>classes.add(x),remove:x=>classes.delete(x),toggle:(x,on)=>on?classes.add(x):classes.delete(x),contains:x=>classes.has(x)},setAttribute(){},checkValidity(){return true;},addEventListener(type,fn){this.listeners[type]=fn;}});}return nodes.get(id);};
 get('signup-role').value='client';get('signup-name').value='Test';get('signup-password').value=get('signup-confirm').value='Fixture_only_123';
 const api={auth:{getSession:async()=>({data:{session:null}}),signInWithPassword:async()=>({error:{code:'invalid_credentials'}}),signInWithOtp:async args=>{calls.push(args);return{error:null};},signUp:async()=>({data:{session:null},error:null}),resend:async args=>{calls.push(args);return{error:null};}}};
 vm.runInNewContext(fs.readFileSync(require.resolve('../assets/auth.js'),'utf8'),{window:{penguinsClient:api},document:{getElementById:get},location:{search:'',href:'https://example.test/auth.html',replace(){},assign(){}},URL,URLSearchParams,setTimeout});
 return {get,calls,submit:id=>get(id).listeners.submit({preventDefault(){}})};
}
test('failed login clears stale confirmation success instead of showing both',async()=>{
 const h=harness();h.get('auth-status').hidden=false;h.get('auth-status').textContent='Old signup confirmation';await h.submit('form-login');
 assert.equal(h.get('auth-status').hidden,true);assert.equal(h.get('login-form-error').classList.contains('hidden'),false);assert.match(h.get('login-form-error').textContent,/تعذر الدخول/);
});
test('signup confirmation remains visible after switching to login',async()=>{
 const h=harness();await h.submit('form-signup');assert.equal(h.get('auth-status').hidden,false);assert.match(h.get('auth-status').textContent,/رسالة التفعيل/);assert.equal(h.get('form-signup').classList.contains('hidden'),true);
});
test('resend uses signup confirmation callback and releases button',async()=>{
 const h=harness();h.get('login-email').value='fixture@example.invalid';await h.get('resend-confirmation').listeners.click();assert.equal(h.calls.length,1);assert.equal(h.calls[0].type,'signup');assert.equal(h.calls[0].options.emailRedirectTo,'https://example.test/auth.html');assert.equal(h.get('resend-confirmation').disabled,false);
});

test('email-link sign-in uses an allowlisted callback and releases the button',async()=>{
 const h=harness();h.get('login-email').value='fixture@example.invalid';await h.get('email-link-login').listeners.click();assert.equal(h.calls.length,1);assert.equal(h.calls[0].email,'fixture@example.invalid');assert.equal(h.calls[0].options.emailRedirectTo,'https://example.test/auth.html');assert.equal(h.calls[0].options.shouldCreateUser,true);assert.equal(h.get('email-link-login').disabled,false);
});
