const test=require('node:test'),assert=require('node:assert/strict');const {score,pending}=require('../assets/training.js');
test('optional rating rejects empty, noninteger and out-of-range inputs',()=>{for(const v of ['',0,6,'3x','2.5',null,undefined])assert.equal(score(v),null);assert.equal(score('4'),4);});
test('a client revision makes an older coach reply pending again',()=>{assert.equal(pending({version:2,reply_version:1}),true);assert.equal(pending({version:2,reply_version:2}),false);assert.equal(pending({version:1,reply_version:0}),true);});
