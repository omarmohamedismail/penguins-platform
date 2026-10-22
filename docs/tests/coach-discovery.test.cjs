const test=require('node:test'),assert=require('node:assert/strict'),{coachId,profileData}=require('../assets/coach-discovery.js');
test('coach links accept UUIDs only and profile writes cannot include client statistics or owner ids',()=>{
 assert.equal(coachId('javascript:alert(1)'),null);assert.equal(coachId('../admin'),null);assert.equal(coachId('11111111-1111-4111-8111-111111111111'),'11111111-1111-4111-8111-111111111111');
 const value=profileData({headline:' Coach ',specialty:' Strength ',bio:' Bio ',qualifications:' Qualified ',experience_years:'5',published:true,certificate_slots:['1'],client_count:55,coach_id:'someone-else'});
 assert.equal(value.headline,'Coach');assert.equal(value.experience_years,5);assert.equal('client_count' in value,false);assert.equal('coach_id' in value,false);
 assert.throws(()=>profileData({...value,experience_years:'-1'}));assert.throws(()=>profileData({...value,experience_years:'2.5'}));assert.throws(()=>profileData({...value,certificate_slots:[4]}));
});
