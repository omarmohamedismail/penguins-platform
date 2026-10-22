create table public.coach_public_profiles(
 coach_id uuid primary key references public.member_profiles(id) on delete cascade,
 headline text not null default '' check(length(headline)<=120),
 specialty text not null default '' check(length(specialty)<=120),
 bio text not null default '' check(length(bio)<=3000),
 qualifications text not null default '' check(length(qualifications)<=2000),
 experience_years smallint check(experience_years between 0 and 80),
 published boolean not null default false,
 certificate_slots smallint[] not null default '{}' check(certificate_slots <@ array[1,2,3]::smallint[] and cardinality(certificate_slots)<=3),
 version bigint not null default 1,updated_at timestamptz not null default now()
);
create table public.coach_free_resources(
 id uuid primary key default gen_random_uuid(),coach_id uuid not null references public.member_profiles(id) on delete cascade,
 title text not null check(length(trim(title)) between 1 and 160),
 filename text not null check(length(filename) between 1 and 180),published boolean not null default false,
 created_at timestamptz not null default now()
);
create index coach_free_resources_owner on public.coach_free_resources(coach_id,created_at desc);
alter table public.coach_public_profiles enable row level security;
alter table public.coach_free_resources enable row level security;
revoke all on public.coach_public_profiles,public.coach_free_resources from public,anon,authenticated;
grant select on public.coach_public_profiles,public.coach_free_resources to authenticated;
create function private.discovery_visible(p_coach uuid) returns boolean language sql stable security definer set search_path='' as $$
 select private.member_enabled(auth.uid()) and private.approved_coach(p_coach) and exists(select 1 from public.coach_public_profiles where coach_id=p_coach and published)
$$;
create policy profile_read on public.coach_public_profiles for select to authenticated using(private.member_enabled((select auth.uid())) and (coach_id=(select auth.uid()) or private.discovery_visible(coach_id)));
create policy resource_read on public.coach_free_resources for select to authenticated using(private.member_enabled((select auth.uid())) and (coach_id=(select auth.uid()) or (published and private.discovery_visible(coach_id))));
create function private.save_coach_public_profile(p_data jsonb,p_expected bigint) returns void language plpgsql security definer set search_path='' as $$
declare slots smallint[];v bigint;
begin
 if not private.approved_coach(auth.uid()) then raise exception 'Approved coach required' using errcode='42501';end if;
 perform 1 from public.member_profiles where id=auth.uid() for update;
 select version into v from public.coach_public_profiles where coach_id=auth.uid() for update;
 if coalesce(v,0) is distinct from p_expected then raise exception 'Profile changed' using errcode='40001';end if;
 if jsonb_typeof(p_data)<>'object' or jsonb_typeof(coalesce(p_data->'certificate_slots','[]'::jsonb))<>'array' then raise exception 'Invalid profile';end if;
 slots:=array(select distinct jsonb_array_elements_text(coalesce(p_data->'certificate_slots','[]'::jsonb))::smallint);
 if not slots <@ array[1,2,3]::smallint[] then raise exception 'Invalid certificates';end if;
 if exists(select 1 from unnest(slots) s where not exists(select 1 from public.coach_certificates c where c.coach_id=auth.uid() and c.slot=s)) then raise exception 'Certificate missing';end if;
 insert into public.coach_public_profiles(coach_id,headline,specialty,bio,qualifications,experience_years,published,certificate_slots)
 values(auth.uid(),trim(coalesce(p_data->>'headline','')),trim(coalesce(p_data->>'specialty','')),trim(coalesce(p_data->>'bio','')),trim(coalesce(p_data->>'qualifications','')),nullif(p_data->>'experience_years','')::smallint,coalesce((p_data->>'published')::boolean,false),slots)
 on conflict(coach_id) do update set headline=excluded.headline,specialty=excluded.specialty,bio=excluded.bio,qualifications=excluded.qualifications,experience_years=excluded.experience_years,published=excluded.published,certificate_slots=excluded.certificate_slots,version=public.coach_public_profiles.version+1,updated_at=now();
end $$;
create function public.save_coach_public_profile(p_data jsonb,p_expected bigint) returns void language sql security invoker set search_path='' as $$select private.save_coach_public_profile(p_data,p_expected)$$;
create function private.discover_coaches(p_query text,p_page integer,p_coach uuid) returns jsonb language plpgsql stable security definer set search_path='' as $$
declare result jsonb;
begin
 if not private.member_enabled(auth.uid()) then raise exception 'Sign in required' using errcode='42501';end if;
 if p_query is null or length(p_query)>80 or p_page is null or p_page not between 0 and 10000 then raise exception 'Invalid search';end if;
 select coalesce(jsonb_agg(row),'[]'::jsonb) into result from (
 select m.id,m.name,coalesce(p.headline,'') headline,coalesce(p.specialty,'') specialty,coalesce(p.bio,'') bio,coalesce(p.qualifications,'') qualifications,p.experience_years,
 coalesce((select jsonb_agg(jsonb_build_object('slot',c.slot,'filename',c.filename) order by c.slot) from public.coach_certificates c where c.coach_id=m.id and c.slot=any(p.certificate_slots)),'[]'::jsonb) certificates,
 coalesce((select jsonb_agg(jsonb_build_object('id',r.id,'title',r.title,'filename',r.filename) order by r.created_at desc) from public.coach_free_resources r where r.coach_id=m.id and r.published and p.published),'[]'::jsonb) resources
 from public.member_profiles m left join public.coach_public_profiles p on p.coach_id=m.id and p.published
 where m.role='coach' and private.approved_coach(m.id) and (p_coach is null or m.id=p_coach)
 and (trim(p_query)='' or strpos(lower(m.name||' '||coalesce(p.specialty,'')||' '||coalesce(p.headline,'')),lower(trim(p_query)))>0)
 order by lower(m.name),m.id limit 13 offset p_page*12
 ) row;
 return jsonb_build_object('items',result-12,'more',jsonb_array_length(result)>12);
end $$;
create function public.discover_coaches(p_query text default '',p_page integer default 0,p_coach uuid default null) returns jsonb language sql stable security invoker set search_path='' as $$select private.discover_coaches(p_query,p_page,p_coach)$$;
create function private.shared_certificate(p_name text) returns boolean language sql stable security definer set search_path='' as $$
 select private.member_enabled(auth.uid()) and exists(select 1 from public.coach_public_profiles p join public.coach_certificates c on c.coach_id=p.coach_id and c.slot=any(p.certificate_slots)
 where private.discovery_visible(p.coach_id) and p_name=p.coach_id::text||'/certificates/'||c.slot::text)
$$;
create policy shared_certificate_read on storage.objects for select to authenticated using(bucket_id='penguins-private' and private.shared_certificate(name));
create function private.reset_shared_certificates() returns trigger language plpgsql security definer set search_path='' as $$begin update public.coach_public_profiles set certificate_slots='{}',version=version+1 where coach_id=new.id;return new;end $$;
create trigger reset_shared_certificates after update of status on public.member_profiles for each row when(old.status='approved' and new.status<>'approved') execute function private.reset_shared_certificates();
insert into storage.buckets(id,name,public,file_size_limit,allowed_mime_types) values('penguins-coach-resources','penguins-coach-resources',false,10485760,array['application/pdf']);
create function private.coach_resource_access(p_name text,p_write boolean) returns boolean language sql stable security definer set search_path='' as $$
 select private.member_enabled(auth.uid()) and exists(select 1 from public.coach_free_resources r where p_name=r.coach_id::text||'/'||r.id::text and ((r.coach_id=auth.uid() and (not p_write or (private.approved_coach(r.coach_id) and not r.published))) or (not p_write and r.published and private.discovery_visible(r.coach_id))))
$$;
create policy coach_resource_read on storage.objects for select to authenticated using(bucket_id='penguins-coach-resources' and private.coach_resource_access(name,false));
create policy coach_resource_insert on storage.objects for insert to authenticated with check(bucket_id='penguins-coach-resources' and private.coach_resource_access(name,true));
create function private.create_coach_resource(p_title text,p_filename text) returns uuid language plpgsql security definer set search_path='' as $$declare rid uuid;begin
 if not private.approved_coach(auth.uid()) then raise exception 'Approved coach required' using errcode='42501';end if;
 perform 1 from public.member_profiles where id=auth.uid() for update;
 if (select count(*) from public.coach_free_resources where coach_id=auth.uid())>=20 then raise exception 'Maximum 20 resources';end if;
 insert into public.coach_free_resources(coach_id,title,filename) values(auth.uid(),trim(p_title),p_filename) returning id into rid;return rid;
end $$;
create function public.create_coach_resource(p_title text,p_filename text) returns uuid language sql security invoker set search_path='' as $$select private.create_coach_resource(p_title,p_filename)$$;
create function private.publish_coach_resource(p_id uuid,p_publish boolean) returns void language plpgsql security definer set search_path='' as $$declare r public.coach_free_resources;begin
 if not private.approved_coach(auth.uid()) or p_publish is null then raise exception 'Approved coach required' using errcode='42501';end if;
 select * into r from public.coach_free_resources where id=p_id and coach_id=auth.uid() for update;
 if not found then raise exception 'Resource missing' using errcode='42501';end if;
 if p_publish and not exists(select 1 from storage.objects where bucket_id='penguins-coach-resources' and name=r.coach_id::text||'/'||r.id::text and metadata->>'mimetype'='application/pdf' and (metadata->>'size')::bigint between 1 and 10485760) then raise exception 'Upload PDF first';end if;
 update public.coach_free_resources set published=p_publish where id=r.id;
end $$;
create function public.publish_coach_resource(p_id uuid,p_publish boolean) returns void language sql security invoker set search_path='' as $$select private.publish_coach_resource(p_id,p_publish)$$;
revoke all on function private.discovery_visible(uuid),private.shared_certificate(text),private.coach_resource_access(text,boolean),private.save_coach_public_profile(jsonb,bigint),private.discover_coaches(text,integer,uuid),private.create_coach_resource(text,text),private.publish_coach_resource(uuid,boolean),private.reset_shared_certificates() from public,anon;
grant execute on function private.discovery_visible(uuid),private.shared_certificate(text),private.coach_resource_access(text,boolean),private.save_coach_public_profile(jsonb,bigint),private.discover_coaches(text,integer,uuid),private.create_coach_resource(text,text),private.publish_coach_resource(uuid,boolean) to authenticated;
revoke all on function public.save_coach_public_profile(jsonb,bigint),public.discover_coaches(text,integer,uuid),public.create_coach_resource(text,text),public.publish_coach_resource(uuid,boolean) from public,anon;
grant execute on function public.save_coach_public_profile(jsonb,bigint),public.discover_coaches(text,integer,uuid),public.create_coach_resource(text,text),public.publish_coach_resource(uuid,boolean) to authenticated;
