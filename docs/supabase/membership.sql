-- Client portal, reviewed coaches and private attachments.
create schema if not exists private;
revoke all on schema private from public;
grant usage on schema private to authenticated;
create table private.platform_admins(email text primary key check(email=lower(email)));
alter table private.platform_admins enable row level security;
create table public.member_profiles(
 id uuid primary key references auth.users on delete cascade,
 name text not null check(length(name) between 1 and 80),
 role text not null check(role in ('client','coach')),
 status text not null check(status in ('active','pending','approved','rejected')),
 review_note text not null default '' check(length(review_note)<=1000),
 created_at timestamptz not null default now(),
 check((role='client' and status='active') or (role='coach' and status in ('pending','approved','rejected')))
);
create table public.coach_certificates(
 coach_id uuid references public.member_profiles on delete cascade,
 slot smallint check(slot between 1 and 3),
 filename text not null check(length(filename) between 1 and 180),
 primary key(coach_id,slot)
);
create table public.coaching_links(
 coach_id uuid references public.member_profiles on delete cascade,
 client_id uuid references public.member_profiles on delete cascade,
 status text not null default 'requested' check(status in ('requested','accepted','declined')),
 created_at timestamptz not null default now(),
 primary key(coach_id,client_id),check(coach_id<>client_id)
);
create index coaching_links_client on public.coaching_links(client_id);
create table public.shared_plans(
 id uuid primary key default gen_random_uuid(),coach_id uuid not null,client_id uuid not null,
 title text not null check(length(title) between 1 and 160),
 details text not null default '' check(length(details)<=20000),
 pdf_name text check(length(pdf_name)<=180),created_at timestamptz not null default now(),
 foreign key(coach_id,client_id) references public.coaching_links on delete cascade
);
create index shared_plans_client on public.shared_plans(client_id);
create index shared_plans_coach on public.shared_plans(coach_id);
create table public.coach_reviews(
 id bigint generated always as identity primary key,coach_id uuid references public.member_profiles on delete cascade,
 reviewer_id uuid references auth.users on delete set null,decision text not null check(decision in ('approved','rejected')),
 note text not null check(length(note)<=1000),created_at timestamptz not null default now()
);
create index coach_reviews_coach on public.coach_reviews(coach_id);
create index coach_reviews_reviewer on public.coach_reviews(reviewer_id);
create function private.is_admin() returns boolean language sql stable security definer set search_path='' as $$
 select exists(select 1 from private.platform_admins a join auth.users u on lower(u.email)=a.email where u.id=auth.uid() and u.email_confirmed_at is not null)
$$;
create function public.am_platform_admin() returns boolean language sql stable security invoker set search_path='' as $$select private.is_admin()$$;
create function private.approved_coach(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$
 select exists(select 1 from public.member_profiles where id=p_id and role='coach' and status='approved')
$$;
create function public.join_platform(p_role text,p_name text) returns void language plpgsql security definer set search_path='' as $$
begin
 if auth.uid() is null or p_role not in ('client','coach') or length(trim(p_name)) not between 1 and 80 then raise exception 'Invalid registration'; end if;
 insert into public.member_profiles(id,name,role,status) values(auth.uid(),trim(p_name),p_role,case when p_role='client' then 'active' else 'pending' end) on conflict(id) do nothing;
end $$;
create function public.review_coach(p_id uuid,p_decision text,p_note text) returns void language plpgsql security definer set search_path='' as $$
begin
 if not private.is_admin() then raise exception 'Administrator required' using errcode='42501'; end if;
 if p_decision not in ('approved','rejected') or p_note is null or length(p_note)>1000 then raise exception 'Invalid decision'; end if;
 update public.member_profiles set status=p_decision,review_note=p_note where id=p_id and role='coach';
 if not found then raise exception 'Coach not found';end if;
 insert into public.coach_reviews(coach_id,reviewer_id,decision,note) values(p_id,auth.uid(),p_decision,p_note);
end $$;
create function public.coach_directory() returns table(id uuid,name text) language sql stable security definer set search_path='' as $$
 select id,name from public.member_profiles where role='coach' and status='approved' and auth.uid() is not null order by name
$$;
alter table public.member_profiles enable row level security;
alter table public.coach_certificates enable row level security;
alter table public.coaching_links enable row level security;
alter table public.shared_plans enable row level security;
alter table public.coach_reviews enable row level security;
create policy profiles_read on public.member_profiles for select to authenticated using(
 id=(select auth.uid()) or (select private.is_admin()) or exists(select 1 from public.coaching_links l where l.status in ('requested','accepted') and ((l.coach_id=(select auth.uid()) and l.client_id=id) or (l.client_id=(select auth.uid()) and l.coach_id=id)))
);
create policy certificates_read on public.coach_certificates for select to authenticated using(coach_id=(select auth.uid()) or (select private.is_admin()));
create policy certificates_insert on public.coach_certificates for insert to authenticated with check(coach_id=(select auth.uid()) and exists(select 1 from public.member_profiles where id=(select auth.uid()) and role='coach'));
create policy certificates_update on public.coach_certificates for update to authenticated using(coach_id=(select auth.uid())) with check(coach_id=(select auth.uid()));
create policy certificates_delete on public.coach_certificates for delete to authenticated using(coach_id=(select auth.uid()));
create policy links_read on public.coaching_links for select to authenticated using(coach_id=(select auth.uid()) or client_id=(select auth.uid()));
create policy links_request on public.coaching_links for insert to authenticated with check(client_id=(select auth.uid()) and status='requested' and private.approved_coach(coach_id) and exists(select 1 from public.member_profiles where id=(select auth.uid()) and role='client'));
-- Only the status column can be updated; identities cannot be reassigned.
create policy links_decide on public.coaching_links for update to authenticated using(coach_id=(select auth.uid()) and private.approved_coach(coach_id)) with check(coach_id=(select auth.uid()) and status in ('accepted','declined') and private.approved_coach(coach_id));
create policy plans_read on public.shared_plans for select to authenticated using(coach_id=(select auth.uid()) or client_id=(select auth.uid()));
create policy plans_insert on public.shared_plans for insert to authenticated with check(coach_id=(select auth.uid()) and private.approved_coach(coach_id) and exists(select 1 from public.coaching_links l where l.coach_id=shared_plans.coach_id and l.client_id=shared_plans.client_id and l.status='accepted'));
create policy plans_delete on public.shared_plans for delete to authenticated using(coach_id=(select auth.uid()));
create policy reviews_read on public.coach_reviews for select to authenticated using((select private.is_admin()));
revoke all on public.member_profiles,public.coach_certificates,public.coaching_links,public.shared_plans,public.coach_reviews from anon,authenticated;
grant select on public.member_profiles,public.coach_reviews to authenticated;
grant select,insert,update,delete on public.coach_certificates to authenticated;
grant select,insert on public.coaching_links to authenticated;
grant update(status) on public.coaching_links to authenticated;
grant select,insert,delete on public.shared_plans to authenticated;
revoke all on function private.is_admin(),private.approved_coach(uuid),public.am_platform_admin(),public.join_platform(text,text),public.review_coach(uuid,text,text),public.coach_directory() from public,anon;
grant execute on function private.is_admin(),private.approved_coach(uuid),public.am_platform_admin(),public.join_platform(text,text),public.review_coach(uuid,text,text),public.coach_directory() to authenticated;
insert into storage.buckets(id,name,public,file_size_limit,allowed_mime_types) values('penguins-private','penguins-private',false,10485760,array['application/pdf','image/jpeg','image/png']);
-- Files use canonical paths: UID/certificates/1..3 or UID/plans/PLAN_UUID.
create function private.file_access(p_name text,p_write boolean) returns boolean language plpgsql stable security definer set search_path='' as $$
declare parts text[]:=string_to_array(p_name,'/');
begin
 if auth.uid() is null or array_length(parts,1)<>3 then return false;end if;
 if parts[2]='certificates' and parts[3] in ('1','2','3') then
 return exists(select 1 from public.coach_certificates c where c.coach_id::text=parts[1] and c.slot::text=parts[3] and (c.coach_id=auth.uid() or (not p_write and private.is_admin())));
 elsif parts[2]='plans' then
 return exists(select 1 from public.shared_plans p where p.coach_id::text=parts[1] and p.id::text=parts[3] and p.pdf_name is not null and ((p.coach_id=auth.uid() and (not p_write or private.approved_coach(p.coach_id))) or (not p_write and p.client_id=auth.uid())));
 end if;
 return false;
end $$;
revoke all on function private.file_access(text,boolean) from public,anon;
grant execute on function private.file_access(text,boolean) to authenticated;
create policy penguins_files_read on storage.objects for select to authenticated using(bucket_id='penguins-private' and private.file_access(name,false));
create policy penguins_files_insert on storage.objects for insert to authenticated with check(bucket_id='penguins-private' and private.file_access(name,true) and (split_part(name,'/',2)<>'plans' or metadata->>'mimetype'='application/pdf'));
create policy penguins_files_update on storage.objects for update to authenticated using(bucket_id='penguins-private' and private.file_access(name,true)) with check(bucket_id='penguins-private' and private.file_access(name,true) and (split_part(name,'/',2)<>'plans' or metadata->>'mimetype'='application/pdf'));
create policy penguins_files_delete on storage.objects for delete to authenticated using(bucket_id='penguins-private' and private.file_access(name,true));

create function private.is_client(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$select exists(select 1 from public.member_profiles where id=p_id and role='client')$$;
create function private.profile_visible(p_id uuid) returns boolean language sql stable security definer set search_path='' as $$select p_id=auth.uid() or private.is_admin() or exists(select 1 from public.coaching_links l where l.status in ('requested','accepted') and ((l.coach_id=auth.uid() and l.client_id=p_id) or (l.client_id=auth.uid() and l.coach_id=p_id)))$$;
revoke all on function private.is_client(uuid),private.profile_visible(uuid) from public,anon;
grant execute on function private.is_client(uuid),private.profile_visible(uuid) to authenticated;
drop policy profiles_read on public.member_profiles;
create policy profiles_read on public.member_profiles for select to authenticated using(private.profile_visible(id));
drop policy links_request on public.coaching_links;
create policy links_request on public.coaching_links for insert to authenticated with check(client_id=(select auth.uid()) and status='requested' and private.approved_coach(coach_id) and private.is_client((select auth.uid())));
create policy no_direct_admin_access on private.platform_admins for all to authenticated using(false) with check(false);
alter function public.join_platform(text,text) set schema private;
alter function public.review_coach(uuid,text,text) set schema private;
alter function public.coach_directory() set schema private;
create function public.join_platform(p_role text,p_name text) returns void language sql security invoker set search_path='' as $$select private.join_platform(p_role,p_name)$$;
create function public.review_coach(p_id uuid,p_decision text,p_note text) returns void language sql security invoker set search_path='' as $$select private.review_coach(p_id,p_decision,p_note)$$;
create function public.coach_directory() returns table(id uuid,name text) language sql stable security invoker set search_path='' as $$select * from private.coach_directory()$$;
revoke all on function public.join_platform(text,text),public.review_coach(uuid,text,text),public.coach_directory() from public,anon;
grant execute on function public.join_platform(text,text),public.review_coach(uuid,text,text),public.coach_directory() to authenticated;
create index shared_plans_membership on public.shared_plans(coach_id,client_id);
