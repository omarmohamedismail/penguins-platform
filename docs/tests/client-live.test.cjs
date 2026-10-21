const test=require('node:test'),assert=require('node:assert/strict');
const {chartWeeks}=require('../assets/client-live.js');
test('live history deduplicates coaches without inventing missing weeks or adding days together',()=>{
 assert.deepEqual(chartWeeks([{week:'2026-10-19',days:3},{week:'2026-10-19',days:5},{week:'2026-10-05',days:2}]),[['2026-10-05',2],['2026-10-19',5]]);
 assert.deepEqual(chartWeeks([]),[]);
 assert.equal(chartWeeks(Array.from({length:10},(_,i)=>({week:'2026-01-'+String(i+1).padStart(2,'0'),days:1}))).length,8);
});
