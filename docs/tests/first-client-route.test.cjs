const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
for(const destination of ['client-dashboard.html','coach.html?id=11111111-2222-4333-8444-555555555555&connect=1'])test('new client finishes onboarding at '+destination,async()=>{
 const source=fs.readFileSync(require.resolve('../assets/portal.js'),'utf8');
 const body=source.slice(source.indexOf('async function render(){'),source.indexOf("document.getElementById('logout').addEventListener"));
 const routes=[],calls=[];let profileCreated=false;
 const user={id:'fixture-client',user_metadata:{requested_role:'client',name:'Fixture'}};
 const query={select(){return this;},eq(){return this;},async maybeSingle(){return{data:null};},async single(){return{data:{id:user.id,role:'client',status:'active'}};}};
 const root={setAttribute(){},childElementCount:0};
 const context={window:{PenguinsConnections:{landing:()=>destination}},rendering:false,queued:false,refresh:{disabled:false},root,view:root,profile:null,admin:false,user:null,URLSearchParams,location:{search:'',replace:x=>routes.push(x)},C:{metadata:()=>({role:'client',name:'Fixture'})},document:{body:{classList:{contains:()=>false}},createDocumentFragment:()=>({}),getElementById:()=>({textContent:'',hidden:true})},api:{auth:{getUser:async()=>({data:{user}})},from:()=>query,rpc:async(name)=>{calls.push(name);if(name==='join_platform')profileCreated=true;return{data:name==='platform_state'?{enabled:true,announcement:''}:name==='am_platform_admin'?false:null};}},checked:async promise=>(await promise).data,clientPortal:async()=>assert.fail('must not render the legacy client portal'),coachPortal:async()=>assert.fail('must not render coach UI'),onboarding:async()=>assert.fail('valid client metadata already provided'),message:()=>assert.fail('unexpected error'),empty(){}};
 vm.createContext(context);await vm.runInContext(body+';render()',context);
 assert.equal(profileCreated,true);assert.deepEqual(routes,[destination]);assert.ok(calls.includes('join_platform'));assert.equal(context.refresh.disabled,false);
});
