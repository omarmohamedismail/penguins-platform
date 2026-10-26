const test=require('node:test'),assert=require('node:assert/strict');
const C=require('../assets/connections.js');
const coach='11111111-2222-4333-8444-555555555555',client='22222222-2222-4333-8444-555555555555',base='https://example.test/penguins/portal.html';
test('coach links are confined to this site and cannot redirect to arbitrary URLs',()=>{
 const link=C.coachURL(base,coach);assert.equal(C.parseLink(link,base),coach);assert.equal(C.parseLink(coach,base),coach);
 for(const bad of ['https://evil.test/penguins/coach.html?id='+coach,'javascript:alert(1)','https://example.test/other/coach.html?id='+coach,'https://user:pass@example.test/penguins/coach.html?id='+coach,'https://example.test/penguins/coach.html?id=admin'])assert.equal(C.parseLink(bad,base),null);
 assert.throws(()=>C.coachURL(base,'../evil'));
});
test('pending coach handoffs expire and only contain validated identifiers',()=>{
 const now=1000000000;assert.equal(C.pendingValue(JSON.stringify({id:coach,at:now-1000}),now),coach);
 for(const v of [null,'bad',JSON.stringify({id:coach,at:now+1}),JSON.stringify({id:coach,at:now-86400000}),JSON.stringify({id:'https://evil.test',at:now})])assert.equal(C.pendingValue(v,now),null);
});
function apiFixture(status,insertError=null){const calls=[],query={select(x){calls.push(['select',x]);return this;},eq(k,v){calls.push(['eq',k,v]);return this;},async maybeSingle(){return{data:status?{status}:null};},async single(){return{data:{status:'requested'}};},async insert(row){calls.push(['insert',row]);return{error:insertError};},update(row){calls.push(['update',row]);return this;}};return {api:{from:t=>{assert.equal(t,'coaching_links');return query;}},calls};}
test('request is idempotent and never accepts a client on their own authority',async()=>{
 const existing=apiFixture('accepted');assert.equal(await C.request(existing.api,client,coach),'accepted');assert.equal(existing.calls.filter(x=>x[0]==='insert').length,0);
 const fresh=apiFixture(null);assert.equal(await C.request(fresh.api,client,coach),'requested');assert.deepEqual(fresh.calls.find(x=>x[0]==='insert')[1],{client_id:client,coach_id:coach});
 const concurrent=apiFixture(null,{code:'23505'});assert.equal(await C.request(concurrent.api,client,coach),'requested');
 await assert.rejects(C.request(fresh.api,coach,coach));
});
test('coach decision checks the pair and pending status and does not report false success',async()=>{
 const zero=apiFixture(null);await assert.rejects(C.decide(zero.api,coach,client,'accepted'),/اتغيّر/);
 for(const expected of [['eq','coach_id',coach],['eq','client_id',client],['eq','status','requested']])assert.ok(zero.calls.some(x=>JSON.stringify(x)===JSON.stringify(expected)));
 await assert.rejects(C.decide(zero.api,coach,client,'admin'));
});
test('coach handoff survives an email callback opened in another tab and clears after use',()=>{
 const fs=require('node:fs'),vm=require('node:vm'),values=new Map();const storage={getItem:k=>values.get(k)||null,setItem:(k,v)=>values.set(k,v),removeItem:k=>values.delete(k)};
 const source=fs.readFileSync(require.resolve('../assets/connections.js'),'utf8');
 function open(search){const window={addEventListener(){}};vm.runInNewContext(source,{window,document:{},localStorage:storage,location:{search,pathname:'/penguins/auth.html'},URL,URLSearchParams,Date});return window.PenguinsConnections;}
 open('?coach='+coach);const callback=open('');assert.equal(callback.pending(),coach);assert.equal(callback.landing(),'coach.html?id='+coach+'&connect=1');callback.clear();assert.equal(open('').landing(),'client-dashboard.html');
});
