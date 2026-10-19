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
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 begin perform public.owner_data('overview');raise exception 'client read owner data';exception when insufficient_privilege then null;end;
 begin perform public.owner_action('announcement','{"note":"attack","expected":""}');raise exception 'client wrote owner settings';exception when insufficient_privilege then null;end;
 begin select * from private.account_controls;raise exception 'client read controls';exception when insufficient_privilege then null;end;
 if not (public.platform_state()->>'enabled')::boolean then raise exception 'active client blocked';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.owner_data('overview');
select public.owner_action('rename','{"id":"88888888-1111-4111-8111-111111111102","name":"QA renamed","expected_name":"QA client"}');
do $$begin
 begin perform public.owner_action('rename','{"id":"88888888-1111-4111-8111-111111111102","name":"stale","expected_name":"QA client"}');raise exception 'stale rename allowed';exception when serialization_failure then null;end;
end $$;
select public.owner_action('pause','{"id":"88888888-1111-4111-8111-111111111102","paused":true,"expected_paused":false,"note":"QA pause"}');
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if (public.platform_state()->>'enabled')::boolean then raise exception 'paused state not returned';end if;
 if exists(select 1 from public.member_profiles) then raise exception 'paused client read profile';end if;
 begin perform public.join_platform('coach','bypass');raise exception 'paused join bypass';exception when insufficient_privilege then null;end;
 if private.file_access('88888888-1111-4111-8111-111111111102/certificates/1',false) then raise exception 'paused storage access';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.owner_action('pause','{"id":"88888888-1111-4111-8111-111111111102","paused":false,"expected_paused":true,"note":"QA restore"}');
select public.review_coach_checked('88888888-1111-4111-8111-111111111101','approved','','pending');
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
insert into public.coaching_links(coach_id,client_id) values('88888888-1111-4111-8111-111111111101',auth.uid());
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.owner_action('link','{"coach_id":"88888888-1111-4111-8111-111111111101","client_id":"88888888-1111-4111-8111-111111111102","status":"accepted","expected_status":"requested","note":"QA accepted"}');
reset role;
insert into public.shared_plans(id,coach_id,client_id,title,details,pdf_name,attachment_ready) values('88888888-1111-4111-8111-111111111199','88888888-1111-4111-8111-111111111101','88888888-1111-4111-8111-111111111102','QA plan','QA details','qa.pdf',true);
insert into storage.objects(bucket_id,name,metadata) values('penguins-private','88888888-1111-4111-8111-111111111101/plans/88888888-1111-4111-8111-111111111199','{"mimetype":"application/pdf","size":20}');
set local role authenticated;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.owner_action('visibility','{"id":"88888888-1111-4111-8111-111111111199","hidden":true,"expected_hidden":false,"note":"QA hide"}');
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if exists(select 1 from public.shared_plans where id='88888888-1111-4111-8111-111111111199') then raise exception 'hidden plan visible';end if;
 if private.file_access('88888888-1111-4111-8111-111111111101/plans/88888888-1111-4111-8111-111111111199',false) then raise exception 'hidden PDF accessible';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
select public.owner_action('visibility','{"id":"88888888-1111-4111-8111-111111111199","hidden":false,"expected_hidden":true,"note":"QA show"}');
select public.owner_action('pause','{"id":"88888888-1111-4111-8111-111111111101","paused":true,"expected_paused":false,"note":"QA coach pause"}');
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
do $$begin
 if private.approved_coach(auth.uid()) then raise exception 'paused approved coach active';end if;
 begin perform public.request_coach_review();raise exception 'paused coach review bypass';exception when insufficient_privilege then null;end;
 begin perform public.save_certificate(1,'test.pdf');raise exception 'paused coach certificate bypass';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111104',true);
reset role;
insert into public.member_profiles(id,name,role,status) values('88888888-1111-4111-8111-111111111104','QA owner','client','active');
set local role authenticated;
do $$begin
 begin perform public.owner_action('pause','{"id":"88888888-1111-4111-8111-111111111104","paused":true,"expected_paused":false,"note":"self lockout"}');raise exception 'owner lockout allowed';exception when insufficient_privilege then null;end;
 if (public.owner_data('activity')->>'total')::int<7 then raise exception 'audit actions missing';end if;
end $$;
set local role anon;
do $$begin begin perform public.owner_data('overview');raise exception 'anon owner access';exception when insufficient_privilege then null;end;end $$;
rollback;
