-- Synthetic records only. Everything rolls back, including object metadata.
begin;
insert into auth.users(id,aud,role,email,email_confirmed_at) values
('88888888-1111-4111-8111-111111111101','authenticated','authenticated','qa-coach@example.invalid',null),
('88888888-1111-4111-8111-111111111102','authenticated','authenticated','qa-client@example.invalid',null),
('88888888-1111-4111-8111-111111111103','authenticated','authenticated','qa-other@example.invalid',null),
('88888888-1111-4111-8111-111111111104','authenticated','authenticated','qa-admin@example.invalid',now());
insert into private.platform_admins values('qa-admin@example.invalid');
insert into public.member_profiles(id,name,role,status) values
('88888888-1111-4111-8111-111111111101','QA coach','coach','pending'),
('88888888-1111-4111-8111-111111111102','QA client','client','active'),
('88888888-1111-4111-8111-111111111103','QA other','client','active');
set local role authenticated;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
do $$begin
 begin perform public.review_coach_checked(auth.uid(),'approved','','pending');raise exception 'self approval allowed';exception when insufficient_privilege then null;end;
 begin update public.member_profiles set status='approved' where id=auth.uid();raise exception 'profile status write allowed';exception when insufficient_privilege then null;end;
 begin perform public.save_certificate(1,'missing.pdf');raise exception 'missing certificate accepted';exception when raise_exception then if sqlerrm='missing certificate accepted' then raise;end if;end;
end $$;
insert into storage.objects(bucket_id,name,metadata) values
('penguins-private',auth.uid()::text||'/certificates/1','{"mimetype":"application/pdf","size":20}'),
('penguins-private',auth.uid()::text||'/certificates/2','{"mimetype":"image/png","size":20}'),
('penguins-private',auth.uid()::text||'/certificates/3','{"mimetype":"image/jpeg","size":20}');
select public.save_certificate(1,'one.pdf'),public.save_certificate(2,'two.png'),public.save_certificate(3,'three.jpg');
do $$begin
 begin insert into storage.objects(bucket_id,name,metadata) values('penguins-private',auth.uid()::text||'/certificates/4','{"mimetype":"application/pdf","size":20}');raise exception 'fourth slot allowed';exception when insufficient_privilege then null;end;
 begin insert into public.coach_certificates values(auth.uid(),4,'four.pdf');raise exception 'direct certificate metadata accepted';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.review_coach_checked('88888888-1111-4111-8111-111111111101','approved','','pending');
do $$begin
 begin perform public.review_coach_checked('88888888-1111-4111-8111-111111111101','rejected','stale decision','pending');raise exception 'stale review accepted';exception when serialization_failure then null;end;
 if not exists(select 1 from public.coach_reviews where coach_id='88888888-1111-4111-8111-111111111101' and decision='approved') then raise exception 'review audit missing';end if;
 if not private.file_access('88888888-1111-4111-8111-111111111101/certificates/1',false) then raise exception 'admin certificate denied';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
do $$begin
 if private.file_access(auth.uid()::text||'/certificates/1',true) then raise exception 'approved certificate overwrite allowed';end if;
 begin perform public.save_certificate(1,'changed.pdf');raise exception 'approved certificate metadata changed';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
insert into public.coaching_links(coach_id,client_id) values('88888888-1111-4111-8111-111111111101',auth.uid());
do $$begin
 if exists(select 1 from public.coach_certificates) or private.file_access('88888888-1111-4111-8111-111111111101/certificates/1',false) then raise exception 'client sees certificates';end if;
 begin update public.coaching_links set status='accepted' where client_id=auth.uid();if found then raise exception 'client accepted own request';end if;exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
update public.coaching_links set status='accepted' where coach_id=auth.uid();
insert into public.shared_plans(id,coach_id,client_id,title,pdf_name) values('88888888-2222-4222-8222-222222222201',auth.uid(),'88888888-1111-4111-8111-111111111102','QA workout','workout.pdf');
do $$begin
 begin insert into public.shared_plans(coach_id,client_id,title,pdf_name,attachment_ready) values(auth.uid(),'88888888-1111-4111-8111-111111111102','Spoofed ready','fake.pdf',true);raise exception 'ready flag spoofed';exception when insufficient_privilege then null;end;
 begin perform public.publish_plan('88888888-2222-4222-8222-222222222201');raise exception 'missing PDF published';exception when raise_exception then if sqlerrm='missing PDF published' then raise;end if;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if exists(select 1 from public.shared_plans) or private.file_access('88888888-1111-4111-8111-111111111101/plans/88888888-2222-4222-8222-222222222201',false) then raise exception 'client sees unfinished PDF';end if;
 begin perform public.publish_plan('88888888-2222-4222-8222-222222222201');raise exception 'client published coach plan';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
insert into storage.objects(bucket_id,name,metadata) values('penguins-private',auth.uid()::text||'/plans/88888888-2222-4222-8222-222222222201','{"mimetype":"application/pdf","size":20}');
select public.publish_plan('88888888-2222-4222-8222-222222222201','workout.pdf');
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if (select count(*) from public.shared_plans)<>1 then raise exception 'published plan unavailable';end if;
 if not private.file_access('88888888-1111-4111-8111-111111111101/plans/88888888-2222-4222-8222-222222222201',false) then raise exception 'client PDF unavailable';end if;
 if private.file_access('88888888-1111-4111-8111-111111111101/plans/88888888-2222-4222-8222-222222222201',true) then raise exception 'client PDF writable';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111103',true);
do $$begin
 if exists(select 1 from public.shared_plans) or private.file_access('88888888-1111-4111-8111-111111111101/plans/88888888-2222-4222-8222-222222222201',false) then raise exception 'cross-client plan leak';end if;
 if public.am_platform_admin() then raise exception 'client gained admin';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
select public.request_coach_review();
do $$begin if not exists(select 1 from public.member_profiles where id=auth.uid() and status='pending') or not private.file_access(auth.uid()::text||'/certificates/1',true) then raise exception 'review reopening failed';end if;end $$;
reset role;
set local role anon;
do $$begin begin perform count(*) from public.member_profiles;raise exception 'anonymous profiles allowed';exception when insufficient_privilege then null;end;end $$;
rollback;
select 'PASS: review integrity, certificate limits, upload completion, role gates, file isolation and anonymous access' as result;
