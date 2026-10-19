// Appended to the dashboard script so the existing views share one workspace.
function downloadFile(name,contents,type='application/json'){
 const url=URL.createObjectURL(new Blob([contents],{type})),link=document.createElement('a');link.href=url;link.download=name;link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
}
function exportWorkspace(){if(!db)return;downloadFile('penguins-backup-'+new Date().toISOString().slice(0,10)+'.json',JSON.stringify({format:'penguins-workspace',schemaVersion:1,exportedAt:new Date().toISOString(),data:db},null,2));}
function logActivity(message){if(!db)return;db.activity??=[];db.activity.unshift({id:Date.now(),message,at:new Date().toISOString()});db.activity=db.activity.slice(0,100);}
const dialog=document.getElementById('pg-dialog'),dialogBody=document.getElementById('pg-dialog-body');
function showDialog(title){document.getElementById('pg-dialog-title').textContent=title;dialogBody.replaceChildren();dialog.showModal();return dialogBody;}
function addText(parent,tag,text){const element=document.createElement(tag);element.textContent=text;parent.append(element);return element;}
function addButton(parent,label,action){const button=addText(parent,'button',label);button.type='button';button.onclick=action;return button;}
document.getElementById('pg-dialog-close').onclick=()=>dialog.close();
document.getElementById('pg-export').onclick=exportWorkspace;
document.getElementById('pg-export-alert').onclick=exportWorkspace;
document.getElementById('pg-retry').onclick=()=>{if(sync.conflict){toast('Export your changes, then reload to resolve the conflict.');return;}sync.flush();};
document.getElementById('pg-print').onclick=()=>window.print();
document.getElementById('pg-import').onclick=()=>document.getElementById('pg-import-file').click();
document.getElementById('pg-import-file').onchange=async event=>{
 const file=event.target.files[0];event.target.value='';if(!file)return;
 try{
  if(file.size>2000000)throw new Error('Choose a JSON backup smaller than 2 MB.');
  const parsed=JSON.parse(await file.text());const data=PenguinsCore.normalize(parsed.format==='penguins-workspace'?parsed.data:parsed);
  const body=showDialog('Restore a backup');
  addText(body,'p',`${data.clients.length} clients · ${data.plans.length} plans · ${data.invoices.length} invoices · ${data.tasks.length} tasks`);
  addText(body,'p','This replaces your workspace. Your current data is downloaded first. Login email and account access will stay with the signed-in user.');
  addButton(body,'Export current data and restore',()=>{
    exportWorkspace();data.account.email=sync.user.email;db=data;logActivity('Restored a JSON backup');saveData();applyTheme();applyColorMode();applyTranslations();renderAll();dialog.close();toast('Backup restored. Check the save status.');
  });
 }catch(error){toast(error.message||'Could not read this backup.');}
};
document.getElementById('pg-history').onclick=async()=>{
 const body=showDialog('Recent versions & activity');const loading=addText(body,'p','Loading cloud versions…');
 try{
  const history=await sync.history();loading.remove();
  if(!history.length)addText(body,'p','Versions appear after your first save. Up to ten are kept.');
  for(const entry of history){
   const row=document.createElement('div');row.className='history-entry';body.append(row);
   addText(row,'span','Version '+entry.version+' · '+new Date(entry.created_at).toLocaleString());
   addButton(row,'Restore',async()=>{
    if(!confirm('Restore this cloud version? Your current workspace is downloaded first.'))return;
    try{const snapshot=await sync.snapshot(entry.version);exportWorkspace();db=snapshot;logActivity('Restored cloud version '+entry.version);saveData();applyTheme();applyColorMode();applyTranslations();renderAll();dialog.close();}catch(error){toast(error.message);}
   });
  }
 }catch(error){loading.textContent='Could not load history: '+error.message;}
 addText(body,'h3','Workspace activity');
 for(const entry of (db.activity||[]).slice(0,15))addText(body,'p',new Date(entry.at).toLocaleString()+' — '+entry.message);
};
document.getElementById('pg-csv').onclick=()=>{
 const body=showDialog('Export a spreadsheet');addText(body,'p','Download your records as CSV. Formula-like text is neutralized for safer spreadsheet import.');
 for(const [collection,fields] of [['clients',['name','email','status','tag','joined','rate','notes']],['invoices',['client','amount','status','due','desc']],['leads',['name','email','stage','value','source']],['tasks',['text','done','due','priority']]]){
  addButton(body,'Export '+collection,()=>downloadFile('penguins-'+collection+'.csv',PenguinsCore.csv(db[collection],fields),'text/csv;charset=utf-8'));
 }
};
function clientEditor(name){
 const client=name?db.clients.find(item=>item.name===name):null;
 const body=showDialog(client?'Edit client':'Add a client');const form=document.createElement('form');body.append(form);
 const fields={};
 for(const [key,label,type] of [['name','Full name','text'],['email','Email','email'],['tag','Coaching focus','text'],['rate','Monthly rate','number'],['notes','Notes','textarea']]){
  const labelElement=addText(form,'label',label);labelElement.htmlFor='edit-client-'+key;
  const input=document.createElement(type==='textarea'?'textarea':'input');if(type!=='textarea')input.type=type;
  input.id='edit-client-'+key;input.name=key;input.value=client?.[key]??(key==='rate'?0:'');input.style.cssText='display:block;width:100%;background:#090910;color:white;border:1px solid #444;padding:10px;border-radius:8px;margin:6px 0 14px';
  if(key==='name'){input.required=true;input.maxLength=80;}if(key==='rate'){input.min='0';input.step='0.01';}
  form.append(input);fields[key]=input;
 }
 const status=document.createElement('select');status.setAttribute('aria-label','Client status');for(const value of ['Active','Paused']){const option=new Option(value,value);status.add(option);}status.value=client?.status||'Active';form.append(status);
 const nutrition=document.createElement('input');nutrition.type='checkbox';nutrition.checked=Boolean(client?.nutrition);nutrition.id='edit-client-nutrition';form.append(nutrition);const nutritionLabel=addText(form,'label',' Includes nutrition coaching');nutritionLabel.htmlFor=nutrition.id;
 const submit=addText(form,'button',client?'Save client':'Add client');submit.type='submit';
 const message=addText(form,'p','');message.setAttribute('role','alert');
 form.onsubmit=event=>{
  event.preventDefault();const newName=fields.name.value.trim();
  if(!newName)return;
  if(db.clients.some(item=>item.name.toLowerCase()===newName.toLowerCase()&&item!==client)){message.textContent='A client with this name already exists.';return;}
  const values={id:client?.id||Date.now(),name:newName,email:fields.email.value.trim(),tag:fields.tag.value.trim()||'Coaching',rate:Number(fields.rate.value)||0,notes:fields.notes.value.trim(),status:status.value,nutrition:nutrition.checked,joined:client?.joined||new Date().toLocaleDateString('en-US',{month:'short',year:'numeric'})};
  if(client){
   if(client.name!==newName){
    for(const key of ['chats','metrics','nutritionTargets','nutritionLogs','sessionNotes','clientPRs','clientGoals'])if(Object.hasOwn(db[key],client.name)){db[key][newName]=db[key][client.name];delete db[key][client.name];}
    for(const key of ['plans','invoices','sessions','feedback','submissions','contracts','slots'])for(const item of db[key])if(item.client===client.name)item.client=newName;
    for(const key of ['pointsClients','readiness'])for(const item of db[key])if(item.name===client.name)item.name=newName;
    for(const item of db.cohorts)if(Array.isArray(item.members))item.members=item.members.map(value=>value===client.name?newName:value);
    if(db.activeChat===client.name)db.activeChat=newName;
   }
   Object.assign(client,values);
  }else{db.clients.push(values);db.chats[newName]=[];}
  logActivity((client?'Updated client: ':'Added client: ')+newName);saveData();renderAll();dialog.close();toast('Client saved.');
 };
 setTimeout(()=>fields.name.focus(),0);
}
document.getElementById('pg-add-client').onclick=()=>clientEditor();
const originalOpenClientModal=window.openClientModal;
window.openClientModal=name=>{
 originalOpenClientModal(name);const body=document.getElementById('clientModalBody');
 const button=document.createElement('button');button.className='pg-button';button.textContent='Edit client';button.onclick=()=>{closeClientModal();clientEditor(name);};body.prepend(button);
};
let undoState=null,undoTimer;
const originalDelete=window.del;
window.del=(type,id)=>{
 if(!Array.isArray(db[type]))return;
 const index=db[type].findIndex(item=>item.id===id);if(index<0)return;
 undoState={type,index,item:structuredClone(db[type][index])};originalDelete(type,id);logActivity('Removed an item from '+type);saveData();
 document.getElementById('pg-undo').hidden=false;clearTimeout(undoTimer);undoTimer=setTimeout(()=>{document.getElementById('pg-undo').hidden=true;undoState=null;},12000);
};
document.getElementById('pg-undo-button').onclick=()=>{
 if(!undoState)return;db[undoState.type].splice(undoState.index,0,undoState.item);logActivity('Undid removal from '+undoState.type);saveData();renderAll();undoState=null;document.getElementById('pg-undo').hidden=true;clearTimeout(undoTimer);
};
const taskForm=document.getElementById('form-task');
const due=document.createElement('input');due.type='date';due.name='due';due.setAttribute('aria-label','Task due date');due.className='input-dark rounded-lg px-2 text-xs';
const priority=document.createElement('select');priority.name='priority';priority.setAttribute('aria-label','Task priority');priority.className='input-dark rounded-lg px-2 text-xs';for(const value of ['Normal','High','Low'])priority.add(new Option(value,value));
taskForm.style.flexWrap='wrap';taskForm.insertBefore(due,taskForm.lastElementChild);taskForm.insertBefore(priority,taskForm.lastElementChild);
taskForm.onsubmit=event=>{event.preventDefault();const data=new FormData(taskForm);const text=String(data.get('text')||'').trim();if(!text)return;db.tasks.unshift({id:Date.now(),text,done:false,due:data.get('due'),priority:data.get('priority')});logActivity('Added a task');saveData();taskForm.reset();renderTasks();};
renderTasks=function(){
 const list=document.getElementById('list-tasks');if(!list)return;list.replaceChildren();
 if(!db.tasks.length){addText(list,'p','No tasks yet — add your first task above.');return;}
 const today=new Date().toLocaleDateString('en-CA');
 const ordered=[...db.tasks].sort((a,b)=>Number(a.done)-Number(b.done)||Number(b.priority==='High')-Number(a.priority==='High')||String(a.due||'9999').localeCompare(b.due||'9999'));
 for(const task of ordered){
  const row=document.createElement('div');row.className='flex items-center gap-3 bg-black border border-border rounded-lg px-3 py-2.5';list.append(row);
  const checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.checked=task.done;checkbox.setAttribute('aria-label','Complete '+task.text);checkbox.onchange=()=>toggleTask(task.id);row.append(checkbox);
  const label=addText(row,'span',task.text);label.className='text-sm flex-1'+(task.done?' line-through text-muted':'');
  const detail=addText(row,'span',[task.priority||'Normal',task.due||'',!task.done&&task.due&&task.due<today?'Overdue':''].filter(Boolean).join(' · '));detail.className='text-xs text-muted';if(!task.done&&task.due&&task.due<today)detail.style.color='#ffb15a';
  addButton(row,'Remove',()=>window.del('tasks',task.id));
 }
};
function refreshClientChoices(){
 document.querySelectorAll('select[name="client"],#metrics-client-select').forEach(select=>{
  const previous=select.value;select.replaceChildren();select.add(new Option(db.clients.length?'Select client':'Add a client first',''));
  for(const client of db.clients)select.add(new Option(client.name,client.name));if(db.clients.some(client=>client.name===previous))select.value=previous;
 });
}
const originalRenderAll=renderAll;
renderAll=function(){refreshClientChoices();originalRenderAll();};
const originalPipeline=renderPipeline;
renderPipeline=function(){
 originalPipeline();
 document.querySelectorAll('#pipeline-board .kanban-col').forEach(column=>{
  column.ondragover=event=>onDragOver(event);column.ondragleave=event=>onDragLeave(event);column.ondrop=event=>onDrop(event);
 });
 document.querySelectorAll('#pipeline-board .kanban-card').forEach(card=>{card.ondragstart=event=>onDragStart(event);card.ondragend=event=>onDragEnd(event);});
};
const originalNavigate=navigateTo;
navigateTo=function(page){originalNavigate(page);history.replaceState(null,'','#'+page);};
document.addEventListener('keydown',event=>{
 if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='s'){event.preventDefault();sync.flush();}
});
window.addEventListener('online',()=>toast('Back online — retrying unsaved changes.'));
