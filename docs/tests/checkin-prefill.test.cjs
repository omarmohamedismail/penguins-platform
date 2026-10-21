const test=require('node:test'),assert=require('node:assert/strict');
const {cairoWeek,currentCheckin}=require('../assets/training.js');
test('weekly form uses Cairo week boundaries and never fills another coach or an old week',()=>{
 const now=new Date('2026-10-18T22:30:00Z');assert.equal(cairoWeek(now),'2026-10-19');
 const current={coach_id:'one',week:'2026-10-19',days:3,energy:4,goal:'Original',note:'Private note'};
 const rows=[{coach_id:'one',week:'2026-10-12',days:7},current,{coach_id:'two',week:'2026-10-19',days:1}];
 assert.equal(currentCheckin(rows,'one',now),current);assert.equal(currentCheckin(rows,'three',now),undefined);
 assert.equal(currentCheckin(rows,'one',new Date('2026-10-26T12:00:00Z')),undefined);
});
