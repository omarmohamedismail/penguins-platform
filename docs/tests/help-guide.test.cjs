const test=require('node:test'),assert=require('node:assert/strict');
const {sections,explain}=require('../assets/help-guide.js');
test('guide separates real client sharing from private business records in both languages',()=>{
 assert.ok(sections.length>=19);for(const row of sections)assert.ok(row.length===4&&row.every(x=>typeof x==='string'&&x.length>2));
 assert.match(sections.find(x=>x[1]==='Clients & messages')[3],/private records/);
 assert.match(sections.find(x=>x[1].includes('Contracts'))[3],/does not process a payment/);
 assert.match(explain('حفظ تقدّمي')[1],/this plan/);assert.match(explain('حفظ الملاحظة الخاصة')[1],/coach-only/);
 assert.match(explain('إرسال / تحديث متابعة الأسبوع')[1],/Sends to the selected coach/);
 assert.match(explain('تحديث')[1],/Reloads data/);
});
