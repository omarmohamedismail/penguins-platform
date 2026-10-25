const test=require('node:test'),assert=require('node:assert/strict');
const anatomy=require('../assets/muscle-anatomy.js'),exercise=require('../assets/exercise-studio.js');
test('every catalogue exercise resolves in Arabic and English with visible named targets',()=>{
 for(const ex of exercise.catalog){
  const ar=anatomy.profile(ex),en=anatomy.profile({...ex,name:ex.alias});
  assert.equal(ar.specific,true,ex.name);assert.deepEqual(ar,en,ex.alias);
  assert.ok(ar.primary.some(k=>anatomy.views[ar.view].includes(k)),ex.name);
  for(const k of [...ar.primary,...ar.secondary])assert.ok(anatomy.muscles[k],k);
  assert.equal(new Set([...ar.primary,...ar.secondary]).size,ar.primary.length+ar.secondary.length);
 }
});
test('isolation exercises do not highlight the entire arm or leg',()=>{
 assert.deepEqual(anatomy.profile({name:'رفع السمانة'}).primary,['calves']);
 assert.deepEqual(anatomy.profile({name:'ثني الركبة جهاز'}).primary,['hamstrings']);
 assert.deepEqual(anatomy.profile({name:'تمديد الركبة جهاز'}).primary,['quads']);
 assert.deepEqual(anatomy.profile({name:'دفع ترايسبس كابل'}).primary,['triceps']);
 assert.deepEqual(anatomy.profile({name:'ثني بايسبس دمبل'}).primary,['biceps']);
 assert.equal(anatomy.profile({name:'دفع ترايسبس كابل'}).view,'back');
});
test('stored plans retain targets without new fields and unknown custom names stay neutral',()=>{
 const ex={name:'رفرفة خلفية',category:'أكتاف',sets:3,reps:'10',rest:60};
 const stored=exercise.decode(exercise.encode('',[ex])).exercises[0];
 assert.deepEqual(anatomy.profile(stored),anatomy.profile(ex));
 const unknown=anatomy.profile({name:'<script>arbitrary custom name</script>',category:'مخصّص'});
 assert.deepEqual(unknown.primary,[]);assert.equal(unknown.specific,false);
 const copy=anatomy.profile(ex);copy.primary.push('calves');
 assert.deepEqual(anatomy.profile(ex).primary,['rearDelts']);
});
