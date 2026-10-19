(function(global){
  'use strict';
  const lists=['clients','leads','invoices','plans','planTemplates','sessions','slots','feedback','submissions','pointsClients','readiness','cohorts','automations','contracts','community','shop','library','tasks'];
  function normalize(input, account={}) {
    if(!input || typeof input!=='object' || Array.isArray(input)) throw new Error('The backup must contain a workspace object.');
    const result=JSON.parse(JSON.stringify(input));
    const walk=(value,depth=0)=>{
      if(depth>30) throw new Error('Data is nested too deeply.');
      if(value && typeof value==='object') for(const key of Object.keys(value)) {
        if(['__proto__','prototype','constructor'].includes(key)) throw new Error('Unsupported field in workspace.');
        walk(value[key],depth+1);
      }
    };walk(result);
    for(const key of lists) {
      if(result[key]===undefined) result[key]=[];
      if(!Array.isArray(result[key]) || result[key].length>10000 || result[key].some(item=>!item||typeof item!=='object'||Array.isArray(item))) throw new Error('Invalid collection: '+key);
    }
    result.account={name:'Coach',email:'',niche:'Fitness / Strength',bio:'',rate:0,...account,...result.account};
    if(typeof result.account.name!=='string') throw new Error('Invalid account name.');
    for(const key of ['chats','metrics','nutritionTargets','nutritionLogs','sessionNotes','clientPRs','clientGoals']) {
      result[key] ??= {};
      if(!result[key] || Array.isArray(result[key]) || typeof result[key]!=='object') throw new Error('Invalid workspace field: '+key);
    }
    for(const client of result.clients) if(typeof client.name!=='string'||!client.name.trim())throw new Error('Every client needs a name.');
    for(const key of ['invoices','shop']) for(const item of result[key]) for(const field of ['amount','price','salePrice','stock']) {
      if(item[field]!==undefined && item[field]!==null && (!Number.isFinite(item[field])||item[field]<0))throw new Error('Invalid '+field+' in '+key);
    }
    result.lang=['en','ar','de','fr','es'].includes(result.lang)?result.lang:'en';
    result.colorMode=result.colorMode==='light'?'light':'dark';
    const color=v=>typeof v==='string'&&/^#[0-9a-f]{6}$/i.test(v);
    result.theme={accent:color(result.theme?.accent)?result.theme.accent:'#6C5CE7',gold:color(result.theme?.gold)?result.theme.gold:'#F5B942'};
    result.referrals ??= {clicks:0,signups:0};result.businessGoals ??= {revenueTarget:0,clientsTarget:0};
    result.activity=Array.isArray(result.activity)?result.activity.slice(0,100):[];
    delete result._csrf;delete result._version;
    if(new TextEncoder().encode(JSON.stringify(result)).length>1900000)throw new Error('Workspace exceeds the backup size limit.');
    return result;
  }
  function csv(rows,fields){
    const cell=value=>{
      let text=String(value??'');
      if(/^[\s]*[=+\-@\t\r]/.test(text))text="'"+text;
      return '"'+text.replaceAll('"','""')+'"';
    };
    return '\ufeff'+[fields.map(cell).join(','),...rows.map(row=>fields.map(key=>cell(row[key])).join(','))].join('\r\n');
  }
  const allowed=new Set('del likePost loadSubmission markPaid markSigned navigateTo openSidebar openChat openClientModal closeClientModal openClientPRModal openClientGoalModal delClientGoal delClientPR openEditProduct openGoalsModal openNutritionModal toggleNutritionSub paletteSelect quickAction toggleQuickActions toggleAutomation toggleTask useTemplate removeBuiltExercise addExerciseToWorkout sendQuickReply setShopFilter setTheme approveAccountChange cancelAccountRequest closeClientPreview'.split(' '));
  function parseActions(text){
    let i=0;const actions=[];
    const space=()=>{while(/\s/.test(text[i]||'')&&i<text.length)i++;};
    while(i<text.length){
      space();if(i===text.length)break;
      const match=/^(?:event\.(?:preventDefault|stopPropagation)|[A-Za-z_$][\w$]*)/.exec(text.slice(i));if(!match)return null;
      const name=match[0];i+=name.length;space();if(text[i++]!=='(')return null;
      const args=[];space();
      while(text[i]!==')'){
        space();let value;const quote=text[i];
        if(quote==="'"||quote==='"'){
          i++;let out='',closed=false;
          while(i<text.length){let c=text[i++];if(c===quote){closed=true;break;}if(c==='\\'){if(i>=text.length)return null;c=text[i++];out+=({n:'\n',r:'\r',t:'\t'})[c]??c;}else out+=c;}
          if(!closed)return null;value=out;
        } else {
          const token=/^(?:-?\d+(?:\.\d+)?|true|false|null)(?=\s*[,\)])/.exec(text.slice(i));if(!token)return null;
          value=JSON.parse(token[0]);i+=token[0].length;
        }
        args.push(value);if(args.length>5)return null;space();if(text[i]===','){i++;continue;}if(text[i]!==')')return null;
      }
      i++;space();if(text[i]===';')i++;else if(i<text.length)return null;
      if(!allowed.has(name)&&!['event.preventDefault','event.stopPropagation'].includes(name))return null;
      actions.push({name,args});if(actions.length>5)return null;
    }
    return actions.length?actions:null;
  }
  const api={normalize,csv,parseActions,lists};
  if(typeof module!=='undefined'&&module.exports)module.exports=api;else global.PenguinsCore=api;
})(globalThis);
