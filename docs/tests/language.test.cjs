const test=require('node:test'),assert=require('node:assert/strict'),{translate}=require('../assets/language.js');
test('language switching is reversible and supports decorated controls and counts',()=>{assert.equal(translate('خطتي الحالية','en'),'My current plan');assert.equal(translate('My current plan','ar'),'خطتي الحالية');assert.equal(translate('◆ نظرة عامة','en'),'◆ Overview');assert.equal(translate('1 / 2 خطط مكتملة','en'),'1 / 2 plans complete');});
test('unknown personal text is preserved and unsupported locales fall back safely',()=>{assert.equal(translate('اسم عميل غير موجود في القاموس','en'),'اسم عميل غير موجود في القاموس');assert.equal(translate('خطتي الحالية','unsupported'),'خطتي الحالية');assert.equal(translate('قرفصاء بوزن الجسم','en'),'Bodyweight squat');});
test('translation lookup ignores inherited object properties',()=>{assert.equal(translate('constructor','en'),'constructor');assert.equal(translate('__proto__','en'),'__proto__');});
test('system dates and readiness explanations translate without changing personal notes',()=>{assert.equal(translate('الثلاثاء، ٢٠ أكتوبر','en'),'Tuesday, 20 October');assert.equal(translate('بيانات متابعة ذاتية للأسبوع 2026-10-19؛ لا تمثل تقييمًا طبيًا.','en'),'Self-reported check-in for week 2026-10-19. This is not a medical assessment.');});

test('weekly check-in counts and Arabic numeric dates use the selected English interface',()=>{assert.equal(translate('أيام تدريب: 3 / ٧ · الطاقة: 4 / ٥','en'),'Training days: 3 / 7 · Energy: 4 / 5');assert.equal(translate('أسبوع ١٩\u200f/١٠\u200f/٢٠٢٦','en'),'Week of 19/10/2026');});

test('last-updated clock is translated with readable digits and meridiem',()=>{assert.equal(translate('آخر تحديث ٠٨:٤٢ ص','en'),'Last updated 08:42 AM');assert.equal(translate('آخر تحديث ٠٨:٤٢ م','en'),'Last updated 08:42 PM');});

test('legacy coach metric widgets follow the Arabic interface',()=>{assert.equal(translate('Total Revenue 💰','ar'),'إجمالي الإيرادات');assert.equal(translate('Plans Sent ▤','ar'),'الخطط المرسلة');assert.equal(translate('2 being coached','ar'),'2 يتلقون المتابعة');});

test('plan completion and favorite summaries switch to English',()=>{assert.equal(translate('متابعة إنجاز الخطة · ★ في المفضلة','en'),'Plan progress · ★ Favorite');assert.equal(translate('✓ مكتملة · ★ في المفضلة','en'),'✓ Completed · ★ Favorite');});

test('account errors translate and personal review text is preserved',()=>{
 const source='تعذر الدخول. تأكد من البريد وكلمة المرور، وأكّد بريدك أولًا إذا كان الحساب جديدًا.';
 const english=translate(source,'en');assert.notEqual(english,source);assert.equal(translate(english,'ar'),source);
 assert.equal(translate('الشهادة 2','en'),'Certificate 2');
 assert.equal(translate('ملاحظة الإدارة: مراجعة','en'),'Owner feedback: مراجعة');
});


test('plan ratings and legacy plan types use coherent English labels',()=>{
 assert.equal(translate('3 / ٥','en'),'3 / 5');
 assert.equal(translate('✓ العميل علّم الخطة كمكتملة · تقييم: 4 / ٥','en'),'✓ Client marked the plan complete · Rating: 4 / 5');
 assert.equal(translate('Life &amp; Mindset','en'),'Life & Mindset');
 assert.equal(translate('BUSINESS GOALS','ar'),'أهداف العمل');
});
