const test=require('node:test'),assert=require('node:assert/strict');
const core=require('../assets/core.js');
test('normalization starts with empty collections and clears unsafe theme values',()=>{
 const value=core.normalize({theme:{accent:'url(javascript:1)',gold:'#abcdef'}});
 assert.deepEqual(value.clients,[]);assert.equal(value.theme.accent,'#6C5CE7');assert.equal(value.theme.gold,'#abcdef');
});
test('reject corrupted imports and prototype pollution',()=>{
 for(const value of [null,[],{clients:'not an array'},{clients:[{}]},JSON.parse('{"__proto__":{}}')])assert.throws(()=>core.normalize(value));
 assert.throws(()=>core.normalize({shop:[{price:-1}]}));
});
test('CSV escapes quotes, commas, newlines and spreadsheet formulas',()=>{
 const value=core.csv([{name:'=HYPERLINK("bad")',notes:'line1\nline2'}],['name','notes']);
 assert.ok(value.includes('"\'=HYPERLINK(""bad"")"'));assert.ok(value.includes('"line1\nline2"'));
});
test('dynamic actions accept only known calls with literal arguments',()=>{
 assert.deepEqual(core.parseActions("openClientModal('O\\'Neil');closeClientModal()"),[{name:'openClientModal',args:["O'Neil"]},{name:'closeClientModal',args:[]}]);
 assert.deepEqual(core.parseActions("del('tasks',123)"),[{name:'del',args:['tasks',123]}]);
 for(const value of ["fetch('https://bad')","constructor('alert(1)')()","del('tasks',alert(1))","window.location='https://bad'","openChat(document.cookie)","openChat('x');fetch('bad')"])assert.equal(core.parseActions(value),null);
});
