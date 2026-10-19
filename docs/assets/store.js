class PenguinsStore {
  constructor(client,onStatus){
    this.client=client;this.onStatus=onStatus;this.version=0;this.revision=0;this.savedRevision=0;this.pending=null;this.busy=false;this.conflict=false;this.loaded=false;this.lastSaved='';
    window.addEventListener('online',()=>this.flush());
    window.addEventListener('offline',()=>this.status('offline','Offline — changes stay on this device'));
    window.addEventListener('beforeunload',event=>{if(this.dirty){event.preventDefault();event.returnValue='';}});
  }
  get dirty(){return this.revision!==this.savedRevision;}
  status(state,message){this.onStatus?.(state,message);}
  get draftKey(){return 'penguins-draft-v1:'+this.user.id;}
  async load(makeBlank){
    const {data:{user},error:userError}=await this.client.auth.getUser();
    if(!user){
      const {data:{session}}=await this.client.auth.getSession();
      if(userError&&session)throw new Error('Cannot verify your session. Check your connection and retry.');
      location.replace('auth.html');throw new Error('Sign in required.');
    }
    this.user=user;
    const {data:row,error}=await this.client.from('workspaces').select('data,version').eq('user_id',user.id).maybeSingle();
    if(error)throw new Error('Could not load your workspace. '+error.message);
    const account={name:user.user_metadata?.name||user.email.split('@')[0],email:user.email,niche:user.user_metadata?.niche||'Fitness / Strength'};
    const data=PenguinsCore.normalize(row?.data||makeBlank(account.name,account.email,account.niche),account);
    this.version=row?.version||0;this.lastSaved=JSON.stringify(data);this.loaded=true;
    try {const draft=JSON.parse(localStorage.getItem(this.draftKey)||'null');if(draft?.data && JSON.stringify(draft.data)!==this.lastSaved)this.draft=draft;}catch{}
    this.status('saved',row?'All changes saved':'Workspace ready');
    return data;
  }
  queue(data){
    if(!this.loaded)throw new Error('Load the workspace before saving.');
    this.pending=PenguinsCore.normalize(data);this.revision++;
    try{localStorage.setItem(this.draftKey,JSON.stringify({data:this.pending,baseVersion:this.version,updatedAt:new Date().toISOString()}));}catch{this.status('error','Device draft unavailable — keep this tab open until saved');}
    this.status(this.conflict?'conflict':'pending',this.conflict?'Newer data exists — export changes before reloading':'Changes waiting to save');
    clearTimeout(this.timer);if(!this.conflict)this.timer=setTimeout(()=>this.flush(),550);
  }
  async flush(){
    clearTimeout(this.timer);
    if(!this.pending||this.busy||this.conflict)return;
    if(!navigator.onLine){this.status('offline','Offline — draft kept on this device');return;}
    this.busy=true;const revision=this.revision;const snapshot=this.pending;this.pending=null;this.status('saving','Saving…');
    try{
      const {data:version,error}=await this.client.rpc('save_workspace',{p_data:snapshot,p_expected:this.version});
      if(error){if(error.code==='40001')this.conflict=true;throw error;}
      this.version=Number(version);this.savedRevision=revision;this.lastSaved=JSON.stringify(snapshot);
      if(this.revision===revision){try{localStorage.removeItem(this.draftKey);}catch{}this.status('saved','All changes saved · '+new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'}));}
    }catch(error){
      if(!this.pending)this.pending=snapshot;
      this.status(this.conflict?'conflict':'error',this.conflict?'Another tab saved newer data. Export your changes, then reload.':'Not saved yet. Your draft is kept on this device. Retry when connected.');
    }finally{this.busy=false;if(this.pending&&!this.conflict&&this.revision!==revision)this.timer=setTimeout(()=>this.flush(),550);}
  }
  async history(){
    const {data,error}=await this.client.from('workspace_history').select('version,created_at').eq('user_id',this.user.id).order('version',{ascending:false}).limit(10);
    if(error)throw error;return data;
  }
  async snapshot(version){
    const {data,error}=await this.client.from('workspace_history').select('data').eq('user_id',this.user.id).eq('version',version).single();
    if(error)throw error;return PenguinsCore.normalize(data.data);
  }
}
window.PenguinsStore=PenguinsStore;
