-- Owner console: privileged functions stay private; public wrappers are invoker only.
create table private.account_controls(user_id uuid primary key references auth.users on delete cascade,paused boolean not null default false,note text not null default '' check(length(note)<=500));
create table private.owner_events(id bigint generated always as identity primary key,actor uuid references auth.users on delete set null,action text not null,target text not null,note text not null default '',created_at timestamptz not null default now());
create index owner_events_created on private.owner_events(created_at desc);
create table private.platform_settings(id boolean primary key default true check(id),announcement text not null default '' check(length(announcement)<=500));
insert into private.platform_settings values(true,'');
alter table private.account_controls enable row level security;
alter table private.owner_events enable row level security;
alter table private.platform_settings enable row level security;
create policy no_direct_access on private.account_controls for all to authenticated using(false) with check(false);
create policy no_direct_access on private.owner_events for all to authenticated using(false) with check(false);
create policy no_direct_access on private.platform_settings for all to authenticated using(false) with check(false);
revoke all on private.account_controls,private.owner_events,private.platform_settings from public,anon,authenticated;
create function private.member_enabled(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$select p_id is not null and not exists(select 1 from private.account_controls where user_id=p_id and paused)$$;
revoke all on function private.member_enabled(uuid) from public,anon;
grant execute on function private.member_enabled(uuid) to authenticated;
create function private.platform_state() returns jsonb language plpgsql stable security definer set search_path='' as $$begin
 if auth.uid() is null then raise exception 'Sign in required' using errcode='42501';end if;
 return jsonb_build_object('enabled',private.member_enabled(auth.uid()),'note',coalesce((select note from private.account_controls where user_id=auth.uid()),''),'announcement',(select announcement from private.platform_settings where id));
end $$;
create function public.platform_state() returns jsonb language sql stable security invoker set search_path='' as $$select private.platform_state()$$;
do $$declare t text;begin foreach t in array array['workspaces','workspace_history','member_profiles','coach_reviews','coaching_links','shared_plans','coach_certificates'] loop
 execute format('create policy enabled_member on public.%I as restrictive for all to authenticated using ((select private.member_enabled(auth.uid()))) with check ((select private.member_enabled(auth.uid())))',t);
end loop;end $$;
create policy enabled_member on storage.objects as restrictive for all to authenticated using(bucket_id<>'penguins-private' or (select private.member_enabled(auth.uid()))) with check(bucket_id<>'penguins-private' or (select private.member_enabled(auth.uid())));
create or replace function private.approved_coach(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$select private.member_enabled(p_id) and exists(select 1 from public.member_profiles where id=p_id and role='coach' and status='approved')$$;
create or replace function private.is_client(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$select private.member_enabled(p_id) and exists(select 1 from public.member_profiles where id=p_id and role='client')$$;
create or replace function private.coach_directory() returns table(id uuid,name text) language sql stable security definer set search_path='' as $$select id,name from public.member_profiles where role='coach' and status='approved' and private.member_enabled(id) and private.member_enabled(auth.uid()) order by name$$;
-- Add the same enabled-account guard to privileged mutation entry points.
do $$declare f text;d text;begin foreach f in array array['private.join_platform(text,text)','private.request_coach_review()','private.save_certificate(integer,text)'] loop
 d:=pg_get_functiondef(f::regprocedure);d:=regexp_replace(d,'\mbegin\M',E'begin\n if not private.member_enabled(auth.uid()) then raise exception ''Account paused'' using errcode=''42501'';end if;\n','i');execute d;
end loop;end $$;
alter table public.shared_plans add column moderation_hidden boolean not null default false;
create policy moderated_plans on public.shared_plans as restrictive for select to authenticated using(not moderation_hidden or coach_id=(select auth.uid()));
-- A hidden plan cannot be downloaded by its client, even through a direct API request.
create or replace function private.file_access(p_name text,p_write boolean) returns boolean language plpgsql volatile security definer set search_path='' as $$declare parts text[]:=string_to_array(p_name,'/');begin
 if not private.member_enabled(auth.uid()) or array_length(parts,1)<>3 then return false;end if;
 if parts[2]='certificates' and parts[3] in ('1','2','3') then
  if p_write then return private.certificates_editable(auth.uid()) and parts[1]=auth.uid()::text;end if;
  return exists(select 1 from public.member_profiles c where c.id::text=parts[1] and c.role='coach' and (c.id=auth.uid() or (private.is_admin() and exists(select 1 from public.coach_certificates f where f.coach_id=c.id and f.slot::text=parts[3]))));
 elsif parts[2]='plans' then
  return exists(select 1 from public.shared_plans p where p.coach_id::text=parts[1] and p.id::text=parts[3] and p.pdf_name is not null and ((p.coach_id=auth.uid() and (not p_write or (private.approved_coach(p.coach_id) and not p.attachment_ready))) or (not p_write and p.client_id=auth.uid() and p.attachment_ready and not p.moderation_hidden)));
 end if;return false;end $$;
create function private.owner_data(p_view text,p_page integer default 0,p_search text default '') returns jsonb language plpgsql stable security definer set search_path='' as $$declare rows jsonb;total bigint;off integer;term text;begin
 if not private.is_admin() then raise exception 'Owner required' using errcode='42501';end if;
 if p_page is null or p_page not between 0 and 100000 or p_search is null or length(p_search)>80 then raise exception 'Invalid filter';end if;off:=p_page*20;term:='%'||replace(replace(p_search,'%',''),'_','')||'%';
 if p_view='overview' then return jsonb_build_object('members',(select count(*) from public.member_profiles),'clients',(select count(*) from public.member_profiles where role='client'),'coaches',(select count(*) from public.member_profiles where role='coach'),'pending',(select count(*) from public.member_profiles where role='coach' and status='pending'),'paused',(select count(*) from private.account_controls where paused),'links',(select count(*) from public.coaching_links where status='accepted'),'plans',(select count(*) from public.shared_plans),'hidden',(select count(*) from public.shared_plans where moderation_hidden),'new_week',(select count(*) from public.member_profiles where created_at>=now()-interval '7 days'),'announcement',(select announcement from private.platform_settings where id),'days',(select jsonb_agg(x) from (select d::date as day,(select count(*) from public.member_profiles p where p.created_at>=d and p.created_at<d+interval '1 day') as members from generate_series(date_trunc('day',now())-interval '6 days',date_trunc('day',now()),interval '1 day')d)x));
 elsif p_view='members' then
 select count(*) into total from public.member_profiles p join auth.users u on u.id=p.id where p.name ilike term or u.email ilike term;
 select coalesce(jsonb_agg(x),'[]') into rows from (select p.*,u.email,coalesce(c.paused,false) paused,coalesce(c.note,'') control_note from public.member_profiles p join auth.users u on u.id=p.id left join private.account_controls c on c.user_id=p.id where p.name ilike term or u.email ilike term order by p.created_at desc,p.id limit 20 offset off)x;
 elsif p_view='links' then
 select count(*) into total from public.coaching_links l join public.member_profiles c on c.id=l.coach_id join public.member_profiles m on m.id=l.client_id where c.name ilike term or m.name ilike term;
 select coalesce(jsonb_agg(x),'[]') into rows from (select l.*,c.name coach_name,m.name client_name from public.coaching_links l join public.member_profiles c on c.id=l.coach_id join public.member_profiles m on m.id=l.client_id where c.name ilike term or m.name ilike term order by l.created_at desc,l.coach_id,l.client_id limit 20 offset off)x;
 elsif p_view='plans' then
 select count(*) into total from public.shared_plans p where p.title ilike term;
 select coalesce(jsonb_agg(x),'[]') into rows from (select p.*,c.name coach_name,m.name client_name from public.shared_plans p join public.member_profiles c on c.id=p.coach_id join public.member_profiles m on m.id=p.client_id where p.title ilike term order by p.created_at desc,p.id limit 20 offset off)x;
 elsif p_view='activity' then
 select count(*) into total from (select id from private.owner_events union all select id from public.coach_reviews)x;
 select coalesce(jsonb_agg(x),'[]') into rows from (select * from (select e.id,'control' as source,e.action,e.target,e.note,e.created_at from private.owner_events e union all select r.id,'review',r.decision,coalesce(p.name,r.coach_id::text),r.note,r.created_at from public.coach_reviews r left join public.member_profiles p on p.id=r.coach_id)y order by created_at desc,source,id desc limit 20 offset off)x;
 else raise exception 'Invalid view';end if;
 return jsonb_build_object('rows',rows,'total',total);end $$;
create function private.owner_action(p_action text,p_payload jsonb) returns void language plpgsql security definer set search_path='' as $$declare target_id uuid;m public.member_profiles;was boolean;txt text;begin
 if not private.is_admin() then raise exception 'Owner required' using errcode='42501';end if;
 if jsonb_typeof(p_payload) is distinct from 'object' then raise exception 'Invalid action';end if;
 txt:=coalesce(p_payload->>'note','');if length(txt)>500 then raise exception 'Note too long';end if;
 if p_action in ('rename','pause') then
 target_id:=(p_payload->>'id')::uuid;select * into m from public.member_profiles where id=target_id for update;if not found then raise exception 'Member missing';end if;
 if p_action='rename' then if length(trim(coalesce(p_payload->>'name',''))) not between 1 and 80 then raise exception 'Invalid name';end if;if m.name is distinct from p_payload->>'expected_name' then raise exception 'Changed' using errcode='40001';end if;update public.member_profiles set name=trim(p_payload->>'name') where id=target_id;
 else
 if exists(select 1 from auth.users u join private.platform_admins a on a.email=lower(u.email) where u.id=target_id) then raise exception 'Owner cannot be paused' using errcode='42501';end if;
 was:=coalesce((select paused from private.account_controls where user_id=target_id),false);
 if was is distinct from (p_payload->>'expected_paused')::boolean then raise exception 'Changed' using errcode='40001';end if;
 if jsonb_typeof(p_payload->'paused') is distinct from 'boolean' or length(trim(txt))=0 then raise exception 'Reason required';end if;
 insert into private.account_controls(user_id,paused,note) values(target_id,(p_payload->>'paused')::boolean,txt) on conflict(user_id) do update set paused=excluded.paused,note=excluded.note;end if;
 elsif p_action='visibility' then
 target_id:=(p_payload->>'id')::uuid;perform 1 from public.shared_plans where id=target_id and moderation_hidden=(p_payload->>'expected_hidden')::boolean for update;if not found then raise exception 'Changed' using errcode='40001';end if;
 if jsonb_typeof(p_payload->'hidden') is distinct from 'boolean' or length(trim(txt))=0 then raise exception 'Reason required';end if;update public.shared_plans set moderation_hidden=(p_payload->>'hidden')::boolean where id=target_id;
 elsif p_action='link' then
 if p_payload->>'status' not in ('accepted','declined') or length(trim(txt))=0 then raise exception 'Invalid link decision';end if;
 if p_payload->>'status'='accepted' and (not private.approved_coach((p_payload->>'coach_id')::uuid) or not private.is_client((p_payload->>'client_id')::uuid)) then raise exception 'Active approved members required' using errcode='42501';end if;
 update public.coaching_links set status=p_payload->>'status' where coach_id=(p_payload->>'coach_id')::uuid and client_id=(p_payload->>'client_id')::uuid and status=p_payload->>'expected_status';if not found then raise exception 'Changed' using errcode='40001';end if;
 elsif p_action='announcement' then
 perform 1 from private.platform_settings where id for update;
 if p_payload->>'expected' is distinct from (select announcement from private.platform_settings where id) then raise exception 'Changed' using errcode='40001';end if;update private.platform_settings set announcement=txt where id;
 else raise exception 'Invalid action';end if;
 insert into private.owner_events(actor,action,target,note) values(auth.uid(),p_action,coalesce(target_id::text,p_payload->>'coach_id','platform'),case when p_action='rename' then p_payload->>'name' else txt end);
end $$;
create function public.owner_data(p_view text,p_page integer default 0,p_search text default '') returns jsonb language sql stable security invoker set search_path='' as $$select private.owner_data(p_view,p_page,p_search)$$;
create function public.owner_action(p_action text,p_payload jsonb) returns void language sql security invoker set search_path='' as $$select private.owner_action(p_action,p_payload)$$;
revoke all on function private.platform_state(),public.platform_state(),private.owner_data(text,integer,text),public.owner_data(text,integer,text),private.owner_action(text,jsonb),public.owner_action(text,jsonb) from public,anon;
grant execute on function private.platform_state(),public.platform_state(),private.owner_data(text,integer,text),public.owner_data(text,integer,text),private.owner_action(text,jsonb),public.owner_action(text,jsonb) to authenticated;
