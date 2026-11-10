(()=>{'use strict';
const page=document.getElementById('page-dashboard');if(!page)return;
const task=document.getElementById('form-task')?.closest('.card');
const activity=document.getElementById('dash-activity')?.closest('.card');
const finance=document.getElementById('earningsChart')?.closest('.card');
const review=page.querySelector('.coach-review-grid');
const header=page.querySelector('header');
const top=document.createElement('div');top.className='review-topline';
const label=document.createElement('span');const back=document.createElement('a');back.href='dashboard.html?demo=1';top.append(label,back);header.prepend(top);
const grid=document.createElement('div');grid.className='review-main-grid';
const next=document.createElement('section');next.className='review-next';const eyebrow=document.createElement('span'),title=document.createElement('h3'),description=document.createElement('p'),actions=document.createElement('div');actions.className='review-next-actions';
next.append(eyebrow,title,description,actions);
const links=[['connected','العملاء والمتابعات','Clients & follow-up'],['planner','باني الخطط','Plan builder'],['clients','سجلات العملاء','Client records']];
for(const [key,ar,en]of links){const b=document.createElement('button');b.type='button';b.dataset.ar=ar;b.dataset.en=en;b.addEventListener('click',()=>navigateTo(key));actions.append(b)}
grid.append(next);if(task){task.classList.add('review-tasks');grid.append(task)}if(activity){activity.classList.add('review-activity');grid.append(activity)}
const financeRow=document.createElement('div');financeRow.className='review-lower-grid';if(finance)financeRow.append(finance);if(review)financeRow.append(review);
for(const child of [...page.children])if(child.classList.contains('grid')&&child.id!=='dash-stats'&&!child.children.length)child.remove();
page.append(grid,financeRow);
function localize(){const en=window.PenguinsLanguage?.language==='en';label.textContent=en?'YOUR WORKSPACE / OVERVIEW':'مساحتك / نظرة عامة';back.textContent=en?'Compare current layout ↗':'قارن بالترتيب الحالي ↗';eyebrow.textContent=en?'YOUR NEXT STEP':'خطوتك التالية';title.textContent=en?'Good coaching starts with a clear day.':'يوم واضح. متابعة أفضل.';description.textContent=en?'Start with your client follow-ups, prepare a plan, then organise the rest of your day.':'ابدأ بمتابعات عملائك، جهّز خططهم، ورتّب باقي يومك.';for(const b of actions.children)b.textContent=en?b.dataset.en:b.dataset.ar;}
for(const n of [label,back,eyebrow,title,description,...actions.children])n.dataset.languageControl='true';
window.addEventListener('penguins-language-change',localize);localize();
})();
