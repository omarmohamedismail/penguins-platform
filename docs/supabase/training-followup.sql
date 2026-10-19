create table public.weekly_checkins(
 id uuid primary key default gen_random_uuid(),coach_id uuid not null,client_id uuid not null,week date not null,
 goal text not null check(length(goal) between 1 and 300),note text not null default '' check(length(note)<=1500),days smallint not null check(days between 0 and 7),energy smallint not null check(energy between 1 and 5),
 reply text not null default '' check(length(reply)<=1500),version integer not null default 1,reply_version integer not null default 0,updated_at timestamptz not null default now(),
 foreign key(coach_id,client_id) references public.coaching_links on delete cascade,unique(coach_id,client_id,week));
create index weekly_checkins_client on public.weekly_checkins(client_id,week desc);
create index weekly_checkins_coach on public.weekly_checkins(coach_id,week desc);
create table public.plan_progress(plan_id uuid primary key references public.shared_plans on delete cascade,client_id uuid not null references auth.users on delete cascade,done boolean not null default false,favorite boolean not null default false,rating smallint check(rating between 1 and 5),comment text not null default '' check(length(comment)<=1000),updated_at timestamptz not null default now());
create index plan_progress_client on public.plan_progress(client_id);
create table public.coach_client_notes(coach_id uuid not null,client_id uuid not null,note text not null check(length(note)<=2000),updated_at timestamptz not null default now(),primary key(coach_id,client_id),foreign key(coach_id,client_id) references public.coaching_links on delete cascade);
alter table public.weekly_checkins enable row level security;
alter table public.plan_progress enable row level security;
alter table public.coach_client_notes enable row level security;
revoke all on public.weekly_checkins,public.plan_progress,public.coach_client_notes from public,anon,authenticated;
grant select on public.weekly_checkins,public.plan_progress,public.coach_client_notes to authenticated;
create function private.training_link(p_coach uuid,p_client uuid) returns boolean language sql stable security definer set search_path='' as $$select private.approved_coach(p_coach) and private.is_client(p_client) and exists(select 1 from public.coaching_links where coach_id=p_coach and client_id=p_client and status='accepted')$$;
revoke all on function private.training_link(uuid,uuid) from public,anon;
grant execute on function private.training_link(uuid,uuid) to authenticated;
create policy checkin_read on public.weekly_checkins for select to authenticated using((select private.member_enabled((select auth.uid()))) and (client_id=(select auth.uid()) or (coach_id=(select auth.uid()) and private.training_link(coach_id,client_id))));
create policy progress_read on public.plan_progress for select to authenticated using((select private.member_enabled((select auth.uid()))) and exists(select 1 from public.shared_plans p where p.id=plan_id and (p.client_id=(select auth.uid()) or (p.coach_id=(select auth.uid()) and private.training_link(p.coach_id,p.client_id)))));
create policy notes_read on public.coach_client_notes for select to authenticated using(coach_id=(select auth.uid()) and private.training_link(coach_id,client_id));
create function private.submit_checkin(p_coach uuid,p_goal text,p_note text,p_days integer,p_energy integer) returns void language plpgsql security definer set search_path='' as $$declare w date;begin
 if not private.training_link(p_coach,auth.uid()) then raise exception 'Active relationship required' using errcode='42501';end if;
 if p_goal is null or length(trim(p_goal)) not between 1 and 300 or p_note is null or length(p_note)>1500 or p_days is null or p_days not between 0 and 7 or p_energy is null or p_energy not between 1 and 5 then raise exception 'Invalid checkin';end if;
 w:=date_trunc('week',now() at time zone 'Africa/Cairo')::date;
 insert into public.weekly_checkins(coach_id,client_id,week,goal,note,days,energy) values(p_coach,auth.uid(),w,trim(p_goal),p_note,p_days,p_energy) on conflict(coach_id,client_id,week) do update set goal=excluded.goal,note=excluded.note,days=excluded.days,energy=excluded.energy,version=public.weekly_checkins.version+1,updated_at=now();end $$;
create function private.reply_checkin(p_id uuid,p_reply text,p_version integer) returns void language plpgsql security definer set search_path='' as $$declare r public.weekly_checkins;begin
 select * into r from public.weekly_checkins where id=p_id and coach_id=auth.uid() for update;
 if not found or not private.training_link(auth.uid(),r.client_id) then raise exception 'Coach relationship required' using errcode='42501';end if;
 if p_version is distinct from r.version then raise exception 'Checkin changed' using errcode='40001';end if;
 if p_reply is null or length(trim(p_reply)) not between 1 and 1500 then raise exception 'Invalid reply';end if;
 update public.weekly_checkins set reply=trim(p_reply),reply_version=version where id=p_id;end $$;
create function private.save_plan_progress(p_id uuid,p_done boolean,p_favorite boolean,p_rating integer,p_comment text) returns void language plpgsql security definer set search_path='' as $$begin
 if not private.member_enabled(auth.uid()) or not exists(select 1 from public.shared_plans where id=p_id and client_id=auth.uid() and not moderation_hidden and (pdf_name is null or attachment_ready)) then raise exception 'Assigned published plan required' using errcode='42501';end if;
 if p_done is null or p_favorite is null or (p_rating is not null and p_rating not between 1 and 5) or p_comment is null or length(p_comment)>1000 then raise exception 'Invalid progress';end if;
 insert into public.plan_progress(plan_id,client_id,done,favorite,rating,comment) values(p_id,auth.uid(),p_done,p_favorite,p_rating,p_comment) on conflict(plan_id) do update set done=excluded.done,favorite=excluded.favorite,rating=excluded.rating,comment=excluded.comment,updated_at=now();end $$;
create function private.save_client_note(p_client uuid,p_note text) returns void language plpgsql security definer set search_path='' as $$begin
 if not private.training_link(auth.uid(),p_client) then raise exception 'Coach relationship required' using errcode='42501';end if;
 if p_note is null or length(p_note)>2000 then raise exception 'Invalid note';end if;
 insert into public.coach_client_notes(coach_id,client_id,note) values(auth.uid(),p_client,p_note) on conflict(coach_id,client_id) do update set note=excluded.note,updated_at=now();end $$;
create function public.submit_checkin(p_coach uuid,p_goal text,p_note text,p_days integer,p_energy integer) returns void language sql security invoker set search_path='' as $$select private.submit_checkin(p_coach,p_goal,p_note,p_days,p_energy)$$;
create function public.reply_checkin(p_id uuid,p_reply text,p_version integer) returns void language sql security invoker set search_path='' as $$select private.reply_checkin(p_id,p_reply,p_version)$$;
create function public.save_plan_progress(p_id uuid,p_done boolean,p_favorite boolean,p_rating integer,p_comment text) returns void language sql security invoker set search_path='' as $$select private.save_plan_progress(p_id,p_done,p_favorite,p_rating,p_comment)$$;
create function public.save_client_note(p_client uuid,p_note text) returns void language sql security invoker set search_path='' as $$select private.save_client_note(p_client,p_note)$$;
revoke all on function private.submit_checkin(uuid,text,text,integer,integer),public.submit_checkin(uuid,text,text,integer,integer),private.reply_checkin(uuid,text,integer),public.reply_checkin(uuid,text,integer),private.save_plan_progress(uuid,boolean,boolean,integer,text),public.save_plan_progress(uuid,boolean,boolean,integer,text),private.save_client_note(uuid,text),public.save_client_note(uuid,text) from public,anon;
grant execute on function private.submit_checkin(uuid,text,text,integer,integer),public.submit_checkin(uuid,text,text,integer,integer),private.reply_checkin(uuid,text,integer),public.reply_checkin(uuid,text,integer),private.save_plan_progress(uuid,boolean,boolean,integer,text),public.save_plan_progress(uuid,boolean,boolean,integer,text),private.save_client_note(uuid,text),public.save_client_note(uuid,text) to authenticated;
