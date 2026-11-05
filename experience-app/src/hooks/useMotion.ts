import {useEffect,useState} from 'react';
export function useMotion(){
 const [time,setTime]=useState(0),[playing,setPlaying]=useState(false),[reduced,setReduced]=useState(false);
 useEffect(()=>{const mq=matchMedia('(prefers-reduced-motion: reduce)');const update=()=>{setReduced(mq.matches);setPlaying(!mq.matches);if(mq.matches)setTime(15)};update();mq.addEventListener('change',update);return()=>mq.removeEventListener('change',update)},[]);
 useEffect(()=>{if(!playing)return;let id=0,previous=0;const tick=(now:number)=>{if(previous&&!document.hidden)setTime(t=>{const next=Math.min(15,t+Math.min((now-previous)/1000,.08));if(next===15)setPlaying(false);return next});previous=now;id=requestAnimationFrame(tick)};id=requestAnimationFrame(tick);return()=>cancelAnimationFrame(id)},[playing]);
 return {time,playing,reduced,toggle:()=>{if(time>=15)setTime(0);setPlaying(p=>!p)},seek:(n:number)=>{setTime(n);setPlaying(false)}};
}