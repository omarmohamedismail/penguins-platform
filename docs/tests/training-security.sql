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
update public.member_profiles set status='approved' where role='coach' and id='88888888-1111-4111-8111-111111111101';
insert into public.coaching_links(coach_id,client_id,status) values('88888888-1111-4111-8111-111111111101','88888888-1111-4111-8111-111111111102','accepted');
insert into public.shared_plans(id,coach_id,client_id,title) values('88888888-1111-4111-8111-111111111199','88888888-1111-4111-8111-111111111101','88888888-1111-4111-8111-111111111102','QA training');
set local role authenticated;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
select public.submit_checkin('88888888-1111-4111-8111-111111111101','QA goal','QA note',3,4);
select public.save_plan_progress('88888888-1111-4111-8111-111111111199',true,true,5,'QA comment');
do $$begin
 if not exists(select 1 from public.plan_progress where done and favorite and rating=5) then raise exception 'client progress missing';end if;
 begin perform public.save_client_note('88888888-1111-4111-8111-111111111102','attack');raise exception 'client coach note write';exception when insufficient_privilege then null;end;
 begin update public.weekly_checkins set reply='attack';raise exception 'direct reply update';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
select public.reply_checkin((select id from public.weekly_checkins where client_id='88888888-1111-4111-8111-111111111102'),'QA reply',1);
select public.save_client_note('88888888-1111-4111-8111-111111111102','Coach private note');
do $$begin
 if not exists(select 1 from public.plan_progress where rating=5) then raise exception 'coach cannot see client progress';end if;
 begin perform public.save_plan_progress('88888888-1111-4111-8111-111111111199',false,false,null,'spoof');raise exception 'coach spoofed completion';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if exists(select 1 from public.coach_client_notes) then raise exception 'client saw private notes';end if;
 if not exists(select 1 from public.weekly_checkins where reply='QA reply') then raise exception 'client missing reply';end if;
end $$;
select public.submit_checkin('88888888-1111-4111-8111-111111111101','Updated goal','QA revision',4,3);
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111101',true);
do $$begin
 begin perform public.reply_checkin((select id from public.weekly_checkins where client_id='88888888-1111-4111-8111-111111111102'),'stale',1);raise exception 'stale reply allowed';exception when serialization_failure then null;end;
 if not exists(select 1 from public.weekly_checkins where version=2 and reply_version=1) then raise exception 'revision state wrong';end if;
end $$;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111103',true);
do $$begin
 if exists(select 1 from public.weekly_checkins) or exists(select 1 from public.plan_progress) or exists(select 1 from public.coach_client_notes) then raise exception 'cross-client leak';end if;
 begin perform public.submit_checkin('88888888-1111-4111-8111-111111111101','attack','',1,1);raise exception 'unlinked checkin';exception when insufficient_privilege then null;end;
 begin perform public.save_plan_progress('88888888-1111-4111-8111-111111111199',true,true,5,'attack');raise exception 'cross-client progress write';exception when insufficient_privilege then null;end;
end $$;
reset role;
update public.shared_plans set moderation_hidden=true where id='88888888-1111-4111-8111-111111111199';
insert into private.account_controls(user_id,paused,note) values('88888888-1111-4111-8111-111111111102',true,'QA paused');
set local role authenticated;
select set_config('request.jwt.claim.sub','88888888-1111-4111-8111-111111111102',true);
do $$begin
 if exists(select 1 from public.weekly_checkins) then raise exception 'paused checkin read';end if;
 begin perform public.submit_checkin('88888888-1111-4111-8111-111111111101','attack','',1,1);raise exception 'paused checkin write';exception when insufficient_privilege then null;end;
end $$;
reset role;
update private.account_controls set paused=false where user_id='88888888-1111-4111-8111-111111111102';
set local role authenticated;
do $$begin
 if exists(select 1 from public.plan_progress) then raise exception 'hidden plan progress read';end if;
 begin perform public.save_plan_progress('88888888-1111-4111-8111-111111111199',false,false,null,'');raise exception 'hidden plan write';exception when insufficient_privilege then null;end;
end $$;
set local role anon;
do $$begin begin select * from public.weekly_checkins;raise exception 'anon read';exception when insufficient_privilege then null;end;end $$;
select 'PASS: checkin sharing, private notes, client isolation, stale replies, moderation and suspension' as result;
rollback;
