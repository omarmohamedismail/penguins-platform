const test=require('node:test'),assert=require('node:assert/strict'),C=require('../assets/portal-core.js');
function file(body,name,type){const b=new Blob([body],{type});b.name=name;return b;}
test('reject renamed HTML, wrong MIME and empty files',async()=>{
 await assert.rejects(C.validateFile(file('<script>alert(1)</script>','renamed.pdf','application/pdf')),/محتوى/);
 await assert.rejects(C.validateFile(file('%PDF-1.7\n','file.pdf','text/html')),/PDF/);
 await assert.rejects(C.validateFile(file('','empty.pdf','application/pdf')),/غير فارغ/);
});
test('validate real file signatures and optional browser MIME',async()=>{
 assert.equal(await C.validateFile(file('%PDF-1.7\n','Workout.PDF',''),true),'application/pdf');
 assert.equal(await C.validateFile(file(new Uint8Array([137,80,78,71,13,10,26,10]),'cert.png','image/png')),'image/png');
 await assert.rejects(C.validateFile(file(new Uint8Array([137,80,78,71,13,10,26,10]),'cert.png','image/png'),true));
});
test('enforce file size before reading and reject invalid page offsets',async()=>{
 await assert.rejects(C.validateFile({size:10485761,name:'big.pdf',type:'application/pdf'}),/ميجابايت/);
 for(const page of [-1,NaN,1.5,100001])assert.throws(()=>C.pageRange(page));assert.deepEqual(C.pageRange(1),{from:12,to:24});
});
test('untrusted metadata cannot crash onboarding or request administrator role',()=>{
 assert.deepEqual(C.metadata({name:{trim(){throw Error('unexpected call');}},requested_role:'admin'}),{name:'',role:null});
 assert.equal(C.metadata({name:'a'.repeat(81),requested_role:'coach'}).name,'');
 assert.deepEqual(C.metadata({name:' Coach ',requested_role:'coach'}),{name:'Coach',role:'coach'});
});
test('hide internal SQL errors while preserving actionable stale-review errors',()=>{
 assert.doesNotMatch(C.errorMessage({message:'relation private.secret_table does not exist'}),/secret_table/);
 assert.match(C.errorMessage({code:'40001'}),/اتغيّر/);
});
