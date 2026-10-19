(function(scope){
 'use strict';
 const labels={pending:'قيد المراجعة',approved:'كوتش معتمد',rejected:'لم تتم الموافقة',active:'حساب نشط',requested:'بانتظار رد الكوتش',accepted:'تم الاتصال',declined:'لم يُقبل الطلب'};
 function metadata(value){const name=typeof value?.name==='string'?value.name.trim():'';return {name:name.length<=80?name:'',role:['coach','client'].includes(value?.requested_role)?value.requested_role:null};}
 function pageRange(page,size=12){if(!Number.isInteger(page)||page<0||page>100000)throw Error('Invalid page');return {from:page*size,to:page*size+size};}
 function errorMessage(e){if(e?.code==='40001')return 'الطلب اتغيّر أثناء المراجعة. حدّث الصفحة وراجع حالته الجديدة.';if(e?.code==='42501')return 'حسابك غير مسموح له بالعملية دي. حدّث الصفحة للتأكد من صلاحياتك.';if(e?.code==='23505')return 'الطلب موجود بالفعل. حدّث الصفحة لمتابعته.';if(/[\u0600-\u06ff]/.test(e?.message||''))return e.message;return 'تعذر إتمام العملية. تحقق من الاتصال وحاول مجددًا.';}
 async function validateFile(file,pdfOnly=false){
  if(!file)throw Error('اختر ملفًا أولًا.');
  if(!file.size||file.size>10485760)throw Error('اختار ملفًا غير فارغ، بحد أقصى ١٠ ميجابايت.');
  if(typeof file.name!=='string'||!file.name.trim()||file.name.length>180)throw Error('اسم الملف غير صالح أو أطول من ١٨٠ حرفًا.');
  const ext=file.name.split('.').pop().toLowerCase(),types={pdf:'application/pdf',png:'image/png',jpg:'image/jpeg',jpeg:'image/jpeg'},mime=types[ext];
  if(!mime||(pdfOnly&&mime!=='application/pdf')||(file.type&&file.type!==mime))throw Error(pdfOnly?'ملف التمرين لازم يكون PDF.':'اختر ملف PDF أو صورة PNG أو JPEG.');
  const h=new Uint8Array(await file.slice(0,8).arrayBuffer());
  const valid=mime==='application/pdf'?[37,80,68,70,45].every((v,i)=>h[i]===v):mime==='image/png'?[137,80,78,71,13,10,26,10].every((v,i)=>h[i]===v):h[0]===255&&h[1]===216&&h[2]===255;
  if(!valid)throw Error('محتوى الملف مش مطابق لنوعه. اختار ملفًا أصليًا بالصيغة المطلوبة.');
  return mime;
 }
 const core={labels,metadata,pageRange,errorMessage,validateFile};
 if(typeof module!=='undefined'&&module.exports)module.exports=core;else scope.PenguinsPortalCore=core;
})(typeof window!=='undefined'?window:globalThis);
