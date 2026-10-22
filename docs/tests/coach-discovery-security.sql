begin;
insert into auth.users(id,aud,role,email,email_confirmed_at) values
('88888888-1111-4111-8111-111111111110','authenticated','authenticated','qa-discovery-coach@example.invalid',now()),
('88888888-1111-4111-8111-111111111111','authenticated','authenticated','qa-discovery-client@example.invalid',now()),
('88888888-1111-4111-8111-111111111112','authenticated','authenticated','qa-discovery-other@example.invalid',now());
insert into public.member_profiles(id,name,role,status) values
('88888888-1111-4111-8111-111111111110','QA discovery coach','coach','approved'),
('88888888-1111-4111-8111-111111111111','QA private client','client','active'),
('88888888-1111-4111-8111-111111111112','QA pending coach','coach','pending');
insert into public.coach_certificates values('88888888-1111-4111-8111-111111111110',1,'shared-certificate.pdf'),('88888888-1111-4111-8111-111111111110',2,'private-certificate.pdf');
insert into storage.objects(bucket_id,name,metadata) values
('penguins-private','88888888-1111-4111-8111-111111111110/certificates/1','{"mimetype":"application/pdf","size":80}'),
('penguins-private','88888888-1111-4111-8111-111111111110/certificates/2','{"mimetype":"application/pdf","size":80}');
set local role authenticated;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111110',true);
select public.save_coach_public_profile('{"headline":"QA headline","specialty":"QA strength","bio":"QA bio","qualifications":"QA qualification","experience_years":5,"published":true,"certificate_slots":[1]}',0);
do $$declare rid uuid;begin
 begin perform public.save_coach_public_profile('{"published":false,"certificate_slots":[]}',0);raise exception 'stale profile accepted';exception when serialization_failure then null;end;
 rid:=public.create_coach_resource('QA free PDF','qa-free.pdf');
 begin perform public.publish_coach_resource(rid,true);raise exception 'missing upload published';exception when raise_exception then if sqlerrm='missing upload published' then raise;end if;end;
end $$;
reset role;
insert into storage.objects(bucket_id,name,metadata) select 'penguins-coach-resources',coach_id::text||'/'||id::text,'{"mimetype":"application/pdf","size":80}'::jsonb from public.coach_free_resources where coach_id='88888888-1111-4111-8111-111111111110';
set local role authenticated;
select public.publish_coach_resource((select id from public.coach_free_resources where coach_id=auth.uid()),true);
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111111',true);
do $$declare d jsonb;begin
 d:=public.discover_coaches('QA discovery',0,null);
 if jsonb_array_length(d->'items')<>1 then raise exception 'directory role filter failed';end if;
 if d::text like '%client_count%' or d::text like '%@example.invalid%' or d::text like '%review_note%' then raise exception 'private fields leaked';end if;
 if jsonb_array_length(d->'items'->0->'certificates')<>1 or jsonb_array_length(d->'items'->0->'resources')<>1 then raise exception 'publication missing';end if;
 if (select count(*) from storage.objects where bucket_id='penguins-private' and name like '88888888-1111-4111-8111-111111111110/certificates/%')<>1 then raise exception 'certificate opt-in failed';end if;
 if (select count(*) from storage.objects where bucket_id='penguins-coach-resources' and name like '88888888-1111-4111-8111-111111111110/%')<>1 then raise exception 'free resource read failed';end if;
 begin perform public.save_coach_public_profile('{"published":true}',0);raise exception 'client edited coach';exception when insufficient_privilege then null;end;
 begin perform public.create_coach_resource('Attack','attack.pdf');raise exception 'client uploaded resource';exception when insufficient_privilege then null;end;
 begin update public.coach_public_profiles set bio='Attack';raise exception 'direct profile write';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111110',true);
select public.save_coach_public_profile('{"published":false,"certificate_slots":[1]}',1);
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111111',true);
do $$begin
 if exists(select 1 from public.coach_public_profiles where coach_id='88888888-1111-4111-8111-111111111110') then raise exception 'unpublished profile read';end if;
 if exists(select 1 from storage.objects where name like '88888888-1111-4111-8111-111111111110/%') then raise exception 'unpublished files read';end if;
end $$;
reset role;
update public.coach_public_profiles set published=true where coach_id='88888888-1111-4111-8111-111111111110';
update public.member_profiles set status='pending' where id='88888888-1111-4111-8111-111111111110';
do $$begin if (select cardinality(certificate_slots) from public.coach_public_profiles where coach_id='88888888-1111-4111-8111-111111111110')<>0 then raise exception 'certificate consent not reset after review';end if;end $$;
set local role authenticated;
do $$begin if jsonb_array_length(public.discover_coaches('QA discovery',0,null)->'items')<>0 then raise exception 'pending coach listed';end if;end $$;
set local role anon;
do $$begin begin perform public.discover_coaches('',0,null);raise exception 'anonymous directory';exception when insufficient_privilege then null;end;end $$;
rollback;
select 'PASS: search privacy, opt-in certificates, completed free uploads, owner-only writes, stale edits, unpublished files, review reset and anonymous denial' result;
