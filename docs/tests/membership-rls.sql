begin;
insert into auth.users(id,aud,role,email) values ('11111111-1111-4111-8111-111111111101','authenticated','authenticated','fixture-coach@example.invalid'),('11111111-1111-4111-8111-111111111102','authenticated','authenticated','fixture-client@example.invalid'),('11111111-1111-4111-8111-111111111103','authenticated','authenticated','fixture-other@example.invalid');
insert into public.member_profiles(id,name,role,status) values ('11111111-1111-4111-8111-111111111101','Coach','coach','approved'),('11111111-1111-4111-8111-111111111102','Client','client','active'),('11111111-1111-4111-8111-111111111103','Other','client','active');
set local role authenticated;
select set_config('request.jwt.claim.sub','11111111-1111-4111-8111-111111111102',true);
insert into public.coaching_links(coach_id,client_id) values('11111111-1111-4111-8111-111111111101',auth.uid());
select set_config('request.jwt.claim.sub','11111111-1111-4111-8111-111111111101',true);
do $$begin
 if (select count(*) from public.member_profiles)<>2 then raise exception 'linked profiles failed';end if;
 begin perform public.review_coach(auth.uid(),'approved','');raise exception 'admin gate failed';exception when insufficient_privilege then null;end;
 begin update public.member_profiles set status='approved' where id=auth.uid();raise exception 'self approval allowed';exception when insufficient_privilege then null;end;
 insert into public.coach_certificates values(auth.uid(),1,'1.pdf'),(auth.uid(),2,'2.pdf'),(auth.uid(),3,'3.pdf');
 begin insert into public.coach_certificates values(auth.uid(),4,'4.pdf');raise exception 'fourth certificate allowed';exception when check_violation then null;end;
end $$;
update public.coaching_links set status='accepted' where coach_id=auth.uid();
insert into public.shared_plans(id,coach_id,client_id,title,pdf_name) values('22222222-2222-4222-8222-222222222201',auth.uid(),'11111111-1111-4111-8111-111111111102','Workout','plan.pdf');
select set_config('request.jwt.claim.sub','11111111-1111-4111-8111-111111111102',true);
do $$begin
 if (select count(*) from public.shared_plans)<>1 then raise exception 'client plan missing';end if;
 if exists(select 1 from public.coach_certificates) then raise exception 'client sees certificates';end if;
 if not private.file_access('11111111-1111-4111-8111-111111111101/plans/22222222-2222-4222-8222-222222222201',false) then raise exception 'client PDF denied';end if;
 if private.file_access('11111111-1111-4111-8111-111111111101/plans/22222222-2222-4222-8222-222222222201',true) then raise exception 'client PDF write allowed';end if;
end $$;
select set_config('request.jwt.claim.sub','11111111-1111-4111-8111-111111111103',true);
do $$begin
 if exists(select 1 from public.shared_plans) or private.file_access('11111111-1111-4111-8111-111111111101/plans/22222222-2222-4222-8222-222222222201',false) then raise exception 'cross client access';end if;
end $$;
reset role;
update public.member_profiles set status='pending' where id='11111111-1111-4111-8111-111111111101';
set local role authenticated;
select set_config('request.jwt.claim.sub','11111111-1111-4111-8111-111111111103',true);
do $$begin begin insert into public.coaching_links(coach_id,client_id) values('11111111-1111-4111-8111-111111111101',auth.uid());raise exception 'pending coach allowed';exception when insufficient_privilege then null;end;end $$;
rollback;
select 'PASS: role gates, private certificates, 3-slot limit, client plans and PDF access' as result;