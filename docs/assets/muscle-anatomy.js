(function(){'use strict';
// Illustrative surface anatomy: highlights describe common targets, not activation measurements.
const muscles={
 upperChest:['الصدر العلوي','Upper chest'],chest:['الصدر','Chest'],frontDelts:['الكتف الأمامي','Front delts'],sideDelts:['الكتف الجانبي','Side delts'],rearDelts:['الكتف الخلفي','Rear delts'],biceps:['البايسبس','Biceps'],triceps:['الترايسبس','Triceps'],forearms:['الساعد','Forearms'],abs:['البطن','Abs'],obliques:['البطن الجانبي','Obliques'],serratus:['المنشارية الأمامية','Serratus'],quads:['الفخذ الأمامي','Quads'],adductors:['الفخذ الداخلي','Inner thighs'],shins:['أمام الساق','Shins'],traps:['الترابيس','Traps'],midBack:['منتصف الظهر','Mid-back'],lats:['الظهر العريض','Lats'],lowerBack:['أسفل الظهر','Lower back'],glutes:['المؤخرة','Glutes'],hamstrings:['الفخذ الخلفي','Hamstrings'],calves:['السمانة','Calves']};
const profiles=new Map();
function register(names,primary,secondary=[],view='front'){
 for(const name of names.split('|'))profiles.set(name.toLocaleLowerCase(),{primary,secondary,view});
}
register('ضغط أرضي|Push up|ضغط صدر بالبار|Barbell bench press|ضغط صدر بالدمبل|Dumbbell bench press|ضغط صدر جهاز|Chest press machine',['chest','upperChest'],['triceps','frontDelts']);
register('ضغط صدر مائل|Incline press',['upperChest'],['chest','frontDelts','triceps']);
register('تفتيح دمبل|Dumbbell fly|تفتيح كابل|Cable fly',['chest','upperChest'],['frontDelts']);
register('متوازي|Dips',['triceps','chest'],['frontDelts']);
register('عقلة|Pull up|سحب علوي|Lat pulldown',['lats'],['midBack','biceps'],'back');
register('تجديف بالكابل|Seated cable row|تجديف بالبار|Barbell row|تجديف دمبل|Dumbbell row',['midBack','lats'],['rearDelts','biceps'],'back');
register('سحب مستقيم للكابل|Straight arm pulldown',['lats'],['triceps'],'back');
register('سحب للوجه|Face pull',['rearDelts','midBack'],['traps'],'back');
register('تمديد الظهر|Back extension',['lowerBack'],['glutes','hamstrings'],'back');
register('قرفصاء بالبار|Barbell squat|قرفصاء بالكأس|Goblet squat|قرفصاء أمامية|Front squat|ضغط الأرجل|Leg press|اندفاع أمامي|Forward lunge|اندفاع خلفي|Reverse lunge|قرفصاء بلغارية|Bulgarian split squat|صعود منصة|Step up|قرفصاء بوزن الجسم|Bodyweight squat',['quads','glutes'],['adductors','abs']);
register('رفعة ميتة|Deadlift',['glutes','hamstrings'],['quads','lowerBack','forearms'],'back');
register('رفعة رومانية|Romanian deadlift',['hamstrings','glutes'],['lowerBack'],'back');
register('تمديد الركبة جهاز|Leg extension',['quads']);
register('ثني الركبة جهاز|Leg curl',['hamstrings'],[],'back');
register('رفع السمانة|Calf raise',['calves'],[],'back');
register('دفع الحوض|Hip thrust|جسر الحوض|Glute bridge',['glutes'],['hamstrings'],'back');
register('ضغط كتف دمبل|Dumbbell shoulder press|ضغط كتف بار|Overhead press',['frontDelts','sideDelts'],['triceps']);
register('رفرفة جانبية|Lateral raise',['sideDelts'],['traps']);
register('رفرفة خلفية|Rear delt fly',['rearDelts'],['midBack'],'back');
register('رفرفة أمامية|Front raise',['frontDelts']);
register('رفع الكتفين|Shrug',['traps'],[],'back');
register('ثني بايسبس دمبل|Dumbbell curl|ثني بايسبس بار|Barbell curl|ثني بايسبس كابل|Cable curl',['biceps'],['forearms']);
register('ثني مطرقة|Hammer curl',['biceps','forearms']);
register('دفع ترايسبس كابل|Triceps pushdown|تمديد ترايسبس فوق الرأس|Overhead triceps extension|تمديد ترايسبس مستلقٍ|Skull crusher',['triceps'],[],'back');
register('بلانك|Plank',['abs','obliques'],['frontDelts','glutes']);
register('بلانك جانبي|Side plank|بالوف برس|Pallof press',['obliques'],['abs']);
register('كرنش|Crunch|رفع الركبتين|Knee raise|ديد باغ|Dead bug',['abs'],['obliques']);
register('بيرد دوغ|Bird dog',['abs','lowerBack','glutes'],['rearDelts'],'back');
register('مشي|Walking|جري|Running',['quads','glutes','calves'],['hamstrings','shins']);
register('دراجة ثابتة|Stationary bike',['quads','glutes'],['hamstrings','calves']);
register('جهاز تجديف|Rowing machine',['quads','lats','midBack'],['glutes','biceps'],'back');
register('نط حبل|Jump rope',['calves'],['quads','forearms'],'back');
register('متسلق الجبل|Mountain climber',['abs'],['frontDelts','quads']);
register('حمل المزارع|Farmer carry',['forearms','traps'],['abs','obliques']);
register('تحريك الكتف|Shoulder mobility',['frontDelts','sideDelts','rearDelts']);
const categories={'صدر':['chest','upperChest'],'ظهر':['lats','midBack','traps'],'أرجل':['quads','hamstrings','glutes','calves'],'أكتاف':['frontDelts','sideDelts','rearDelts'],'ذراع':['biceps','triceps'],'وسط الجسم':['abs','obliques'],'لياقة وحركة':['quads','glutes','calves']};
function profile(ex){const found=profiles.get(String(ex?.name||'').trim().toLocaleLowerCase());if(found)return {...found,primary:[...found.primary],secondary:[...found.secondary],specific:true};return {primary:[...(categories[ex?.category]||[])],secondary:[],view:ex?.category==='ظهر'?'back':'front',specific:false};}
// Left-side paths are mirrored around the body's centerline. Separate shapes preserve muscle borders.
const front={
 upperChest:['M87 55 Q76 50 64 57 L65 65 Q75 62 87 63 Z'],
 chest:['M64 68 Q76 64 87 66 L87 82 Q75 87 65 79 Z'],
 frontDelts:['M62 53 Q54 54 51 63 L54 74 Q61 70 64 64 Z'],
 sideDelts:['M50 59 Q45 63 44 74 L48 80 L52 74 L49 65 Z'],
 biceps:['M53 77 Q59 83 55 94 L49 106 Q43 101 46 92 Z'],
 triceps:['M44 78 L47 85 L43 101 L39 102 Q39 89 44 78 Z'],
 forearms:['M43 106 Q49 110 45 119 L38 142 L32 144 Q32 129 36 116 Z','M47 111 L48 121 L40 143 L39 138 Z'],
 serratus:['M64 82 L70 85 L65 90 L61 87 Z','M62 91 L69 92 L64 98 L60 94 Z','M61 98 L67 99 L63 104 L59 101 Z'],
 abs:['M79 87 Q84 86 88 87 L88 98 L78 98 Z','M78 101 L88 101 L88 112 L77 111 Z','M77 114 L88 115 L88 126 L78 125 Z','M79 129 L88 129 L88 143 Q81 140 79 135 Z'],
 obliques:['M69 90 L75 88 L73 113 L77 136 L65 126 L62 112 Z'],
 quads:['M68 153 Q72 147 77 153 L76 180 L72 207 Q64 201 64 186 Z','M78 154 L85 156 L84 180 L79 202 L76 189 Z','M79 202 L84 190 Q88 204 82 211 L76 211 Z'],
 adductors:['M84 151 L89 155 L86 181 L82 196 L85 169 Z'],
 shins:['M73 226 L77 224 L76 251 L74 271 L70 271 Z'],
 calves:['M66 227 L70 226 L68 254 L64 246 Q62 237 66 227 Z']};
const back={
 traps:['M85 39 L88 41 L88 65 L68 56 Q77 51 81 46 Z','M71 59 L87 70 L87 91 L78 81 Z'],
 rearDelts:['M63 54 Q53 53 48 65 L50 76 Q58 74 65 65 Z'],
 sideDelts:['M47 65 Q43 71 44 80 L48 80 L49 74 Z'],
 midBack:['M67 60 L78 68 L75 81 L66 85 L61 73 Z'],
 lats:['M62 86 L74 84 L84 97 L79 126 L67 119 L61 103 Z'],
 lowerBack:['M85 97 L88 97 L88 141 L80 137 L82 118 Z'],
 triceps:['M48 78 Q57 79 55 91 L49 107 L43 103 Q43 91 48 78 Z','M42 82 L45 85 L40 103 L37 105 Z'],
 forearms:['M40 108 Q47 109 45 119 L38 142 L32 144 Q31 129 36 117 Z','M46 115 L47 122 L40 144 L39 138 Z'],
 glutes:['M68 130 Q75 126 88 144 L88 164 Q74 169 65 159 Q62 143 68 130 Z'],
 hamstrings:['M66 165 Q72 169 77 167 L75 195 L71 215 L66 208 Q62 190 66 165 Z','M80 167 L87 166 L84 191 L78 213 L76 202 Z'],
 calves:['M67 226 Q72 220 75 226 L73 244 L68 252 Q62 245 64 235 Z','M77 225 Q83 225 83 235 L78 252 L75 244 Z'],
 adductors:['M88 169 L88 186 L83 203 L85 181 Z']};
const api={profile,muscles,views:{front:Object.keys(front),back:Object.keys(back)}};
if(typeof module!=='undefined'&&module.exports){module.exports=api;return;}
let serial=0;
const instances=new WeakMap();
const language=()=>window.PenguinsLanguage?.language==='en'?'en':'ar';
function labels(root){const state=instances.get(root);if(!state)return;const en=language()==='en',p=state.profile;root.dir=en?'ltr':'rtl';for(const b of root.querySelectorAll('[data-view]')){const rear=b.dataset.view==='back';b.textContent=en?(rear?'Back':'Front'):(rear?'خلف':'أمام');b.setAttribute('aria-label',en?(rear?'View muscles from the back':'View muscles from the front'):(rear?'عرض العضلات من الخلف':'عرض العضلات من الأمام'));}state.caption.textContent=p.primary.length?(en?'Focus: ':'التركيز: ')+p.primary.map(x=>muscles[x][en?1:0]).join(' · '):(en?'Custom exercise':'تمرين مخصّص');state.primaryLabel.textContent=en?'Main':'أساسية';state.secondaryLabel.textContent=en?'Supporting':'مساعدة';state.legend.hidden=!p.primary.length;state.caption.title=p.secondary.length?(en?'Supporting: ':'عضلات مساعدة: ')+p.secondary.map(x=>muscles[x][en?1:0]).join(' · '):'';}
window.addEventListener('penguins-language-change',()=>{document.querySelectorAll('.muscle-figure').forEach(labels);});
api.render=function(parent,ex){
 const p=profile(ex),root=document.createElement('div');root.className='muscle-figure';root.dataset.languageControl='true';parent.append(root);
 const views=document.createElement('div');views.className='muscle-view-switch';root.append(views);
 const art=document.createElement('div');art.className='exercise-art pose-art muscle-map';art.dataset.specific=String(p.specific);art.setAttribute('aria-hidden','true');root.append(art);
 const caption=document.createElement('p');caption.className='muscle-caption';root.append(caption);
 const legend=document.createElement('div');legend.className='muscle-legend';root.append(legend);
 const primaryLabel=document.createElement('span'),secondaryLabel=document.createElement('span');primaryLabel.className='primary-key';secondaryLabel.className='secondary-key';legend.append(primaryLabel,secondaryLabel);secondaryLabel.hidden=!p.secondary.length;
 instances.set(root,{profile:p,caption,legend,primaryLabel,secondaryLabel});
 function draw(view){art.replaceChildren();art.dataset.view=view;const id='pg-muscle-'+(++serial),ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg');svg.setAttribute('viewBox','0 0 180 300');art.append(svg);
  const add=(tag,attrs,into=svg)=>{const n=document.createElementNS(ns,tag);for(const[k,v]of Object.entries(attrs))n.setAttribute(k,String(v));into.append(n);return n;};
  const defs=add('defs',{});for(const [key,stops]of Object.entries({body:['#343946','#212530','#424956'],active:['#ddd1ff','#9b80dc','#66519e'],support:['#9f99b6','#67617b','#4a455e'],rest:['#606778','#414754','#303642']})){const g=add('linearGradient',{id:id+'-'+key,x1:'0%',y1:'0%',x2:'100%',y2:'80%'},defs);stops.forEach((color,i)=>add('stop',{offset:(i/(stops.length-1)*100)+'%','stop-color':color},g));}
  add('ellipse',{cx:90,cy:290,rx:42,ry:5,fill:'#090c14',opacity:.45});
  const body={fill:'url(#'+id+'-body)',stroke:'#798296','stroke-opacity':.55,'stroke-width':.8,'stroke-linejoin':'round'};
  add('path',{...body,d:'M79 12 Q90 5 101 12 Q106 20 101 30 L96 37 L84 37 L79 30 Q74 20 79 12 Z'});
  add('path',{...body,d:'M84 36 L83 44 L63 51 Q51 53 48 63 L55 83 L62 94 L64 117 L62 139 Q59 152 64 168 L68 187 L88 187 L90 165 L92 187 L112 187 L116 168 Q121 152 118 139 L116 117 L118 94 L125 83 L132 63 Q129 53 117 51 L97 44 L96 36 Z'});
  for(const mirror of [false,true]){
   const layer=add('g',mirror?{transform:'translate(180 0) scale(-1 1)'}:{});
   for(const d of ['M51 57 Q44 58 41 73 L37 96 L34 110 Q29 125 29 144 L25 153 L26 164 Q29 168 32 163 L34 155 L39 150 L44 131 L50 113 L56 96 L61 78 Z','M65 151 Q60 169 63 190 L67 214 Q61 227 62 242 L67 269 L67 278 L62 284 Q63 288 70 287 L78 284 L78 271 L81 248 L85 227 L85 213 L89 186 L89 160 Z'])add('path',{...body,d},layer);
   const zones=view==='back'?back:front;
   for(const [key,paths]of Object.entries(zones))for(const d of paths){const level=p.primary.includes(key)?'active':p.secondary.includes(key)?'support':'rest';add('path',{d,fill:'url(#'+id+'-'+level+')',stroke:level==='active'?'#e3d5ff':'#828a9b','stroke-opacity':level==='active'?.65:.28,'stroke-width':.65,'stroke-linejoin':'round','data-muscle':key,'data-level':level,class:'muscle-zone muscle-'+level},layer);}
   // Tendons and bony landmarks help separate adjacent muscle groups.
   add('path',{d:view==='back'?'M71 255 L72 277 M71 218 Q76 220 81 217':'M67 215 Q75 211 82 215 L80 222 Q74 225 68 220 Z M76 229 L74 270',fill:'none',stroke:'#8991a2','stroke-opacity':.38,'stroke-width':.8},layer);
  }
  add('path',{d:view==='back'?'M90 43 L90 142 M90 149 L90 166':'M90 55 L90 143 M84 47 L90 51 L96 47',fill:'none',stroke:'#8991a2','stroke-opacity':.45,'stroke-width':.7});
  for(const b of views.children)b.setAttribute('aria-pressed',String(b.dataset.view===view));
 }
 for(const view of ['front','back']){const b=document.createElement('button');b.type='button';b.dataset.view=view;b.addEventListener('click',()=>draw(view));views.append(b);}
 draw(p.view);labels(root);
};
window.PenguinsMuscleAnatomy=api;
})();
