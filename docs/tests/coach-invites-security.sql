begin;
insert into auth.users(id,aud,role,email,email_confirmed_at,created_at) values
('77777777-2222-4222-8222-222222222210','authenticated','authenticated','invite-a@example.invalid',now(),now()-interval '2 days'),
('77777777-2222-4222-8222-222222222211','authenticated','authenticated','invite-b@example.invalid',now(),now()-interval '2 days'),
('77777777-2222-4222-8222-222222222213','authenticated','authenticated','invite-unconfirmed@example.invalid',null,now()-interval '1 minute'),
('77777777-2222-4222-8222-222222222214','authenticated','authenticated','invite-old@example.invalid',now(),now()-interval '3 days');
insert into public.member_profiles(id,name,role,status) values
('77777777-2222-4222-8222-222222222210','Invite A','client','active'),('77777777-2222-4222-8222-222222222211','Invite B','client','active'),('77777777-2222-4222-8222-222222222213','Unconfirmed coach','coach','pending'),('77777777-2222-4222-8222-222222222214','Old coach','coach','pending');
insert into private.coach_invites(id,inviter_id,created_at) values('77777777-3333-4333-8333-333333333310','77777777-2222-4222-8222-222222222210',now()-interval '5 minutes'),('77777777-3333-4333-8333-333333333311','77777777-2222-4222-8222-222222222211',now()-interval '5 minutes');
insert into auth.users(id,aud,role,email,email_confirmed_at,created_at,raw_user_meta_data) values('77777777-2222-4222-8222-222222222212','authenticated','authenticated','invite-coach@example.invalid',now(),now()-interval '1 minute','{"requested_role":"coach","invitation_code":"77777777-3333-4333-8333-333333333310"}');
reset role;
insert into public.member_profiles(id,name,role,status) values('77777777-2222-4222-8222-222222222212','Invited coach','coach','pending');
update auth.users set raw_user_meta_data='{"requested_role":"coach","invitation_code":"77777777-3333-4333-8333-333333333310"}' where id='77777777-2222-4222-8222-222222222214';
set local role authenticated;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222210',true);
do $$declare x jsonb;begin
 x:=public.coach_invite_link();if x->>'code'<>'77777777-3333-4333-8333-333333333310' then raise exception 'wrong owner token';end if;
 if (public.coach_invite_summary()->>'points')::int<>0 then raise exception 'unearned points';end if;
 begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333310');raise exception 'self referral allowed';exception when insufficient_privilege then null;end;
 begin perform 1 from private.coach_referrals;raise exception 'private rows exposed';exception when insufficient_privilege then null;end;
 begin insert into private.coach_referral_rewards(coach_id,client_id,points) values(auth.uid(),auth.uid(),50);raise exception 'direct reward write allowed';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222213',true);
do $$begin begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333310');raise exception 'unconfirmed referral allowed';exception when insufficient_privilege then null;end;end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222214',true);
do $$begin begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333310');raise exception 'old account referral allowed';exception when insufficient_privilege then null;end;end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222212',true);
select public.claim_coach_invite('77777777-3333-4333-8333-333333333310');
select public.claim_coach_invite('77777777-3333-4333-8333-333333333310');
do $$begin
 begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333311');raise exception 'referral stealing allowed';exception when insufficient_privilege then null;end;
 begin perform public.coach_invite_link();raise exception 'coach client invite allowed';exception when insufficient_privilege then null;end;
end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222210',true);
do $$begin if public.coach_invite_summary()<>'{"joined":1,"approved":0,"points":0}'::jsonb then raise exception 'pending reward credited';end if;end $$;
reset role;
update public.member_profiles set status='approved' where id='77777777-2222-4222-8222-222222222212';
update public.member_profiles set status='approved' where id='77777777-2222-4222-8222-222222222212';
set local role authenticated;
do $$begin if public.coach_invite_summary()<>'{"joined":1,"approved":1,"points":50}'::jsonb then raise exception 'approval reward not exactly 50';end if;end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222211',true);
do $$begin if (public.coach_invite_summary()->>'points')::int<>0 or (public.coach_invite_summary()->>'joined')::int<>0 then raise exception 'other client data leaked';end if;end $$;
reset role;
update public.member_profiles set status='rejected' where id='77777777-2222-4222-8222-222222222212';
set local role authenticated;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222210',true);
do $$begin if (public.coach_invite_summary()->>'points')::int<>0 then raise exception 'revoked coach still earns points';end if;end $$;
reset role;
update public.member_profiles set status='approved' where id='77777777-2222-4222-8222-222222222212';
update private.coach_invites set expires_at=now()-interval '1 minute' where id='77777777-3333-4333-8333-333333333311';
insert into auth.users(id,aud,role,email,email_confirmed_at,created_at,raw_user_meta_data) values('77777777-2222-4222-8222-222222222215','authenticated','authenticated','expired-signup@example.invalid',now(),now(),'{"requested_role":"coach","invitation_code":"77777777-3333-4333-8333-333333333311"}');
insert into public.member_profiles(id,name,role,status) values('77777777-2222-4222-8222-222222222215','Expired signup','coach','pending');
set local role authenticated;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222215',true);
do $$begin begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333311');raise exception 'expired signup captured';exception when insufficient_privilege then null;end;end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222212',true);
do $$begin begin perform public.claim_coach_invite('77777777-3333-4333-8333-333333333311');raise exception 'expired invite allowed';exception when insufficient_privilege then null;end;end $$;
select set_config('request.jwt.claim.sub','77777777-2222-4222-8222-222222222210',true);
do $$begin if (public.coach_invite_summary()->>'points')::int<>50 then raise exception 'repeat approval minted more points';end if;end $$;
reset role;
insert into private.account_controls(user_id,paused) values('77777777-2222-4222-8222-222222222212',true);
set local role authenticated;
do $$begin if (public.coach_invite_summary()->>'points')::int<>0 then raise exception 'paused coach still rewarded';end if;end $$;
reset role;
update private.account_controls set paused=false where user_id='77777777-2222-4222-8222-222222222212';
insert into private.account_controls(user_id,paused) values('77777777-2222-4222-8222-222222222210',true);
set local role authenticated;
do $$begin begin perform public.coach_invite_summary();raise exception 'paused client summary allowed';exception when insufficient_privilege then null;end;begin perform public.coach_invite_link();raise exception 'paused client invitation allowed';exception when insufficient_privilege then null;end;end $$;
reset role;
set local role anon;
do $$begin begin perform public.coach_invite_summary();raise exception 'anonymous summary allowed';exception when insufficient_privilege then null;end;begin perform public.coach_invite_link();raise exception 'anonymous creation allowed';exception when insufficient_privilege then null;end;end $$;
reset role;
rollback;
select 'PASS: immutable signup attribution, verified new coach only, no self referral, one owner per coach, exactly 50 after approval, duplicate protection, private ledger, revoked approval, expired token and anonymous denial' as result;
