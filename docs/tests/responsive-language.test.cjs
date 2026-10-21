const test=require('node:test'),assert=require('node:assert/strict'),{translate}=require('../assets/language.js');
test('coach stat text nodes translate when the icon is rendered in a separate span',()=>{
 assert.equal(translate('Total Revenue','ar'),'إجمالي الإيرادات');
 assert.equal(translate('Plans Sent','ar'),'الخطط المرسلة');
 assert.equal(translate('Library Resources','ar'),'موارد المكتبة');
 assert.equal(translate('الخطط المرسلة','en'),'Plans sent');
});
