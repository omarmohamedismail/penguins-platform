(function(){
  'use strict';
  const handlers=new WeakMap();
  DOMPurify.addHook('uponSanitizeAttribute',(node,data)=>{
    if(data.attrName==='onclick') {
      const parsed=PenguinsCore.parseActions(data.attrValue);
      if(parsed)handlers.set(node,parsed);
    }
  });
  const descriptor=Object.getOwnPropertyDescriptor(Element.prototype,'innerHTML');
  Object.defineProperty(Element.prototype,'innerHTML',{
    configurable:descriptor.configurable,enumerable:descriptor.enumerable,get:descriptor.get,
    set(value){
      const fragment=DOMPurify.sanitize(String(value),{RETURN_DOM_FRAGMENT:true,FORBID_TAGS:['iframe','object','embed','script','style','form'],FORBID_ATTR:['srcdoc'],ALLOW_DATA_ATTR:false,ADD_ATTR:['data-stage','data-id']});
      this.replaceChildren(fragment);
    }
  });
  document.addEventListener('click',event=>{
    for(let element=event.target instanceof Element?event.target:null;element;element=element.parentElement){
      const actions=handlers.get(element);if(!actions)continue;
      for(const action of actions){
        if(action.name==='event.preventDefault')event.preventDefault();
        else if(action.name==='event.stopPropagation')event.stopPropagation();
        else if(typeof window[action.name]==='function')window[action.name](...action.args);
      }
      break;
    }
  });
})();
