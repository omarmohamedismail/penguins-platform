const test=require('node:test'),assert=require('node:assert/strict'),vm=require('node:vm'),fs=require('node:fs');
function harness(invitationCode=null, loginError={code:"invalid_credentials"}, session=null, search=""){
 const nodes=new Map(),calls=[],redirects=[];
 const get=id=>{if(!nodes.has(id)){const classes=new Set();nodes.set(id,{value:'',textContent:'',hidden:false,disabled:false,checked:true,type:'password',style:{},parentElement:{hidden:false},listeners:{},classList:{add:x=>classes.add(x),remove:x=>classes.delete(x),toggle:(x,on)=>on?classes.add(x):classes.delete(x),contains:x=>classes.has(x)},setAttribute(){},checkValidity(){return true;},addEventListener(type,fn){this.listeners[type]=fn;}});}return nodes.get(id);};
 get('signup-email').value=get('login-email').value='fixture@example.invalid';get('login-password').value='Fixture_only_123';get('signup-role').value='client';get('signup-name').value='Test';get('signup-password').value=get('signup-confirm').value='Fixture_only_123';
 const api={auth:{getSession:async()=>({data:{session}}),signInWithPassword:async()=>({error:loginError}),signInWithOtp:async args=>{calls.push(args);return{error:null};},signUp:async args=>{calls.push(args);return{data:{session:null},error:null};},resend:async args=>{calls.push(args);return{error:null};}}};
 vm.runInNewContext(fs.readFileSync(require.resolve('../assets/auth.js'),'utf8'),{window:{penguinsClient:api,PenguinsInvites:{code:invitationCode}},document:{getElementById:get},location:{search,href:'https://example.test/auth.html',replace(url){redirects.push(url);},assign(url){redirects.push(url);}},URL,URLSearchParams,setTimeout});
 return {get,calls,redirects,submit:id=>get(id).listeners.submit({preventDefault(){}})};
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
 const h=harness();h.get('login-email').value='fixture@example.invalid';await h.get('email-link-login').listeners.click();assert.equal(h.calls.length,1);assert.equal(h.calls[0].email,'fixture@example.invalid');assert.equal(h.calls[0].options.emailRedirectTo,'https://example.test/auth.html');assert.equal(h.calls[0].options.shouldCreateUser,false);assert.equal(h.get('email-link-login').disabled,false);
});

test('coach signup carries invitation attribution without setting approval or admin metadata',async()=>{const code='77777777-3333-4333-8333-333333333310',h=harness(code);h.get('signup-role').value='coach';await h.submit('form-signup');assert.equal(h.calls[0].options.data.invitation_code,code);assert.equal(h.calls[0].options.data.requested_role,'coach');assert.equal(Object.hasOwn(h.calls[0].options.data,'status'),false);assert.equal(Object.hasOwn(h.calls[0].options.data,'admin'),false);});

test('empty login and invalid account roles do not issue auth requests',async()=>{const h=harness();h.get('login-password').value='';await h.submit('form-login');assert.match(h.get('login-form-error').textContent,/أولًا/);h.get('signup-role').value='admin';await h.submit('form-signup');assert.equal(h.calls.length,0);assert.match(h.get('signup-form-error').textContent,/نوع حسابك/);});

test('invalid reset email is blocked before requesting a reset',async()=>{const h=harness();h.get('login-email').checkValidity=()=>false;await h.get('reset-password').listeners.click();assert.match(h.get('login-form-error').textContent,/الصحيح/);assert.equal(h.get('reset-password').disabled,false);});
test('network failure shows actionable login feedback',async()=>{const h=harness(null,{name:'AuthRetryableFetchError',message:'Failed to fetch'});await h.submit('form-login');assert.match(h.get('login-form-error').textContent,/الإنترنت/);assert.equal(h.get('login-submit').disabled,false);});

test('opening sign-in with an existing session waits for explicit choice',async()=>{const h=harness(null,{}, {user:{email:'existing@example.invalid'}});await Promise.resolve();assert.deepEqual(h.redirects,[]);assert.equal(h.get('existing-session').hidden,false);assert.equal(h.get('existing-session-email').textContent,'existing@example.invalid');await h.get('continue-session').listeners.click();assert.deepEqual(h.redirects,['portal.html']);});
test('email callback continues to portal but callback errors do not',async()=>{const session={user:{email:'existing@example.invalid'}};const h=harness(null,{},session,'?code=fixture');await Promise.resolve();assert.deepEqual(h.redirects,['portal.html']);const e=harness(null,{},session,'?code=fixture&error=expired');await Promise.resolve();assert.deepEqual(e.redirects,[]);});
